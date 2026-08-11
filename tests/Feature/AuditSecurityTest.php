<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\LoginLog;
use App\Models\Message;
use App\Models\MessageReport;
use App\Models\SpamBan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['password' => bcrypt('rahasia')]);
    }

    public function test_admin_action_is_recorded_in_audit_log(): void
    {
        $admin = $this->admin();
        $message = Message::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.messages.destroy', $message))
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'messages.destroy',
            'target_type' => 'message',
            'user_id' => $admin->id,
            'user_name' => $admin->name,
            'ip_address' => '127.0.0.1',
        ]);
    }

    public function test_audit_page_lists_actions_with_search_and_tabs(): void
    {
        $admin = $this->admin();
        $message = Message::factory()->create();

        $this->actingAs($admin)->delete(route('admin.messages.destroy', $message));

        $this->actingAs($admin)
            ->get(route('admin.audit'))
            ->assertOk()
            ->assertSee('Audit Security')
            ->assertSee('messages.destroy')
            ->assertSee('Aktivitas Admin');

        // tab logins & bans juga render
        $this->actingAs($admin)
            ->get(route('admin.audit', ['tab' => 'logins']))
            ->assertOk()
            ->assertSee('Riwayat Login');

        $this->actingAs($admin)
            ->get(route('admin.audit', ['tab' => 'bans']))
            ->assertOk()
            ->assertSee('IP Ter-ban');

        // search
        $this->actingAs($admin)
            ->get(route('admin.audit', ['tab' => 'activity', 'search' => 'destroy']))
            ->assertOk()
            ->assertSee('messages.destroy');
    }

    public function test_first_login_is_not_flagged_suspicious(): void
    {
        $admin = $this->admin();

        // Login PERTAMA user ini — wajar, bukan mencurigakan (kemungkinan besar pemiliknya)
        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'rahasia',
        ])->assertRedirect();

        $this->assertDatabaseHas('login_logs', [
            'user_id' => $admin->id,
            'email' => $admin->email,
            'status' => 'success',
            'is_suspicious' => false,
            'is_new' => true,
        ]);
    }

    public function test_login_from_new_ip_after_prior_login_is_suspicious(): void
    {
        $admin = $this->admin();

        // Sudah pernah login sukses dari IP lama
        LoginLog::create([
            'user_id' => $admin->id,
            'email' => $admin->email,
            'status' => 'success',
            'ip_address' => '198.51.100.7',
            'is_suspicious' => false,
            'is_new' => false,
        ]);

        // Login dari IP baru → mencurigakan (akun diakses dari alamat yang belum dikenal)
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.88'])
            ->post('/login', [
                'email' => $admin->email,
                'password' => 'rahasia',
            ])->assertRedirect();

        $this->assertDatabaseHas('login_logs', [
            'user_id' => $admin->id,
            'status' => 'success',
            'ip_address' => '203.0.113.88',
            'is_suspicious' => true,
        ]);
    }

    public function test_same_ip_login_is_not_suspicious_anymore(): void
    {
        $admin = $this->admin();

        // 1x login sukses dulu (tandai IP ini dikenal)
        LoginLog::create([
            'user_id' => $admin->id,
            'email' => $admin->email,
            'status' => 'success',
            'ip_address' => '127.0.0.1',
            'is_suspicious' => false,
            'is_new' => false,
        ]);

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'rahasia',
        ])->assertRedirect();

        $latest = LoginLog::latest()->first();
        $this->assertSame('success', $latest->status);
        $this->assertFalse($latest->is_suspicious);
    }

    public function test_failed_login_is_logged(): void
    {
        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'salah',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseHas('login_logs', [
            'email' => 'admin@example.com',
            'status' => 'failed',
        ]);
    }

    public function test_mark_logins_read_clears_badge_and_is_audited(): void
    {
        $admin = $this->admin();

        $this->post('/login', ['email' => $admin->email, 'password' => 'salah']);

        $this->assertSame(1, LoginLog::where('is_new', true)->count());

        $this->actingAs($admin)
            ->post(route('admin.audit.logins-read'))
            ->assertRedirect();

        $this->assertSame(0, LoginLog::where('is_new', true)->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'logins.read-all']);
    }

    public function test_login_status_filter_on_audit_page(): void
    {
        $admin = $this->admin();

        $this->post('/login', ['email' => $admin->email, 'password' => 'salah']);

        $this->actingAs($admin)
            ->get(route('admin.audit', ['tab' => 'logins', 'login_status' => 'failed']))
            ->assertOk()
            ->assertSee($admin->email);

        $this->actingAs($admin)
            ->get(route('admin.audit', ['tab' => 'logins', 'login_status' => 'success']))
            ->assertOk();
    }

    public function test_unban_allows_ip_to_send_again(): void
    {
        $admin = $this->admin();

        $ban = SpamBan::create([
            'sender_key' => '*',
            'ip_address' => '203.0.113.9',
            'reason' => 'test',
            'banned_at' => now(),
            'ban_source' => 'manual',
            'banned_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.audit.unban', $ban))
            ->assertRedirect();

        $this->assertDatabaseCount('spam_bans', 0);
        $this->assertDatabaseHas('audit_logs', ['action' => 'bans.unban']);
    }

    public function test_export_audit_csv(): void
    {
        $admin = $this->admin();
        $message = Message::factory()->create();

        $this->actingAs($admin)->delete(route('admin.messages.destroy', $message));

        $response = $this->actingAs($admin)->get(route('admin.export.audit'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('messages.destroy', $response->getContent());
    }

    public function test_export_logins_and_hack_csv(): void
    {
        $admin = $this->admin();

        $this->post('/login', ['email' => $admin->email, 'password' => 'salah']);

        $response = $this->actingAs($admin)->get(route('admin.export.logins'));
        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $hack = $this->actingAs($admin)->get(route('admin.export.hack'));
        $hack->assertOk();
        $this->assertStringContainsString('text/csv', $hack->headers->get('Content-Type'));

        // Export adalah aksi sensitif → harus tercatat di audit log
        $this->assertDatabaseHas('audit_logs', ['action' => 'export.logins']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'export.hack']);
    }

    public function test_sidebar_shows_audit_link_and_badge(): void
    {
        $admin = $this->admin();

        $this->post('/login', ['email' => $admin->email, 'password' => 'salah']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Audit Security');
    }

    public function test_report_ban_ip_records_audit_with_ban_source(): void
    {
        $admin = $this->admin();
        $message = Message::factory()->create(['ip_address' => '203.0.113.55']);
        $report = MessageReport::create([
            'message_id' => $message->id,
            'ip_address' => '127.0.0.1',
            'reason' => 'spam',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.reports.ban-ip', $report))
            ->assertRedirect();

        $this->assertDatabaseHas('spam_bans', [
            'ip_address' => '203.0.113.55',
            'ban_source' => 'manual',
            'banned_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'reports.ban-ip']);
    }
}
