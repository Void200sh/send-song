<?php

namespace Tests\Feature;

use App\Models\HackAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HackTraceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sql_injection_attempt_is_logged(): void
    {
        $this->post('/messages', [
            'sender_name' => 'Bot',
            'recipient_name' => 'Target',
            'message' => "abc' UNION SELECT username, password FROM users--",
        ]);

        $this->assertDatabaseHas('hack_attempts', [
            'signature' => 'sql-injection',
            'severity' => 'high',
            'ip_address' => '127.0.0.1',
        ]);
    }

    public function test_sensitive_file_probe_is_logged(): void
    {
        $this->get('/.env')->assertNotFound();

        $this->assertDatabaseHas('hack_attempts', [
            'signature' => 'sensitive-file',
            'path' => '/.env',
        ]);
    }

    public function test_path_traversal_is_logged(): void
    {
        $this->get('/messages?file=../../../../etc/passwd');

        $this->assertDatabaseHas('hack_attempts', ['signature' => 'path-traversal']);
    }

    public function test_xss_payload_in_query_is_logged(): void
    {
        $this->get('/?q=<script>alert(document.cookie)</script>');

        $this->assertDatabaseHas('hack_attempts', ['signature' => 'xss']);
    }

    public function test_command_injection_is_logged_as_critical(): void
    {
        $this->get('/messages?cmd=1;whoami');

        $this->assertDatabaseHas('hack_attempts', [
            'signature' => 'command-injection',
            'severity' => 'critical',
        ]);
    }

    public function test_normal_requests_are_not_logged(): void
    {
        $this->get('/')->assertOk();
        $this->get('/messages')->assertOk();
        $this->get('/story')->assertOk();

        $this->assertDatabaseCount('hack_attempts', 0);
    }

    public function test_authenticated_user_is_not_logged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/.env');

        $this->assertDatabaseCount('hack_attempts', 0);
    }

    public function test_failed_login_is_logged(): void
    {
        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'salah-password',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseHas('hack_attempts', [
            'signature' => 'failed-login',
            'severity' => 'low',
        ]);
    }

    public function test_repeated_attempts_are_deduplicated(): void
    {
        $this->get('/.env');
        $this->get('/.env');

        $this->assertDatabaseCount('hack_attempts', 1);
        $this->assertDatabaseHas('hack_attempts', [
            'signature' => 'sensitive-file',
            'count' => 2,
        ]);
    }

    public function test_admin_page_lists_attempts_and_mark_all_read(): void
    {
        $user = User::factory()->create();

        $this->get('/.env');
        $this->post('/login', ['email' => 'x@y.z', 'password' => 'salah']);

        $this->actingAs($user)
            ->get(route('admin.hack'))
            ->assertOk()
            ->assertSee('Jejak Hacking')
            ->assertSee('/.env')
            ->assertSee('Login gagal untuk email');

        $this->actingAs($user)
            ->post(route('admin.hack.read-all'))
            ->assertRedirect();

        $this->assertSame(0, HackAttempt::where('is_new', true)->count());
    }

    public function test_admin_sidebar_shows_hack_link_and_badge_count(): void
    {
        $user = User::factory()->create();

        $this->get('/.env');

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Jejak Hacking');
    }

    public function test_admin_can_delete_single_and_clear_all_attempts(): void
    {
        $user = User::factory()->create();

        $this->get('/.env');

        $attempt = HackAttempt::firstOrFail();

        $this->actingAs($user)
            ->delete(route('admin.hack.destroy', $attempt))
            ->assertRedirect();

        $this->assertDatabaseCount('hack_attempts', 0);

        $this->get('/messages?file=../../etc/passwd');

        $this->actingAs($user)
            ->post(route('admin.hack.clear'))
            ->assertRedirect();

        $this->assertDatabaseCount('hack_attempts', 0);
    }

    public function test_hack_page_filter_by_severity(): void
    {
        $user = User::factory()->create();

        $this->get('/.env'); // medium

        $this->actingAs($user)
            ->get(route('admin.hack', ['severity' => 'critical']))
            ->assertOk()
            ->assertDontSee('/.env');
    }
}
