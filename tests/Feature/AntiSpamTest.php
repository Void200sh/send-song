<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\SpamBan;
use App\Models\User;
use App\Services\SpamDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Tests\TestCase;

class AntiSpamTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_new_messages_store_ip_and_are_visible_when_not_spam(): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->post(route('messages.store'), [
                'sender_name' => 'Alya',
                'recipient_name' => 'Rina',
                'kelas' => 'XI PPLG 1',
                'message' => 'Pesan biasa',
            ]);

        $response->assertRedirect(route('messages.index'));
        $this->assertDatabaseHas('messages', [
            'sender_name' => 'Alya',
            'ip_address' => '203.0.113.10',
            'sender_key' => 'alya',
            'is_spam' => 0,
        ]);

        $this->get(route('messages.index'))
            ->assertOk()
            ->assertSee('Pesan biasa');
    }

    public function test_strict_detector_marks_repeated_fast_message_as_spam_and_hides_it(): void
    {
        $spam = app(SpamDetectionService::class);
        $fingerprint = $spam->fingerprint('pesan berulang');
        $identityKey = hash('sha256', 'alya|203.0.113.11');

        Message::factory()->count(5)->create([
            'sender_name' => 'Alya',
            'sender_key' => 'alya',
            'ip_address' => '203.0.113.11',
            'spam_identity_key' => $identityKey,
            'spam_fingerprint' => $fingerprint,
            'is_spam' => false,
            'message' => 'pesan berulang',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.11'])
            ->post(route('messages.store'), [
                'sender_name' => 'Alya',
                'recipient_name' => 'Rina',
                'kelas' => 'XI PPLG 1',
                'message' => 'pesan berulang',
            ])
            ->assertRedirect(route('messages.index'));

        $this->assertDatabaseHas('messages', [
            'ip_address' => '203.0.113.11',
            'is_spam' => 1,
        ]);

        $spamMessage = Message::query()
            ->where('ip_address', '203.0.113.11')
            ->where('is_spam', true)
            ->latest('id')
            ->firstOrFail();

        $this->get(route('messages.show', $spamMessage))
            ->assertNotFound();
    }

    public function test_tenth_spam_creates_name_and_ip_ban_and_blocks_future_submission(): void
    {
        $spam = app(SpamDetectionService::class);
        $identityKey = hash('sha256', 'alya|203.0.113.12');

        $messages = Message::factory()->count(10)->create([
            'sender_name' => 'Alya',
            'sender_key' => 'alya',
            'ip_address' => '203.0.113.12',
            'spam_identity_key' => $identityKey,
            'is_spam' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $spam->recordAndMaybeBan($messages->last());

        $this->assertDatabaseHas('spam_bans', [
            'sender_key' => 'alya',
            'ip_address' => '203.0.113.12',
            'spam_count' => 10,
            'sender_name' => 'Alya',
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.12'])
            ->post(route('messages.store'), [
                'sender_name' => 'Alya',
                'recipient_name' => 'Rina',
                'kelas' => 'XI PPLG 1',
                'message' => 'pesan baru',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_view_spam_notification_and_delete_all_messages_for_identity(): void
    {
        $user = User::factory()->create();
        Message::factory()->count(3)->create([
            'sender_name' => 'Alya',
            'sender_key' => 'alya',
            'ip_address' => '203.0.113.13',
            'is_spam' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Notifikasi Spam')
            ->assertSee('Lihat spam')
            ->assertSee('3');

        $this->actingAs($user)
            ->get(route('admin.spam'))
            ->assertOk()
            ->assertSee('alya')
            ->assertSee('203.0.113.13')
            ->assertSee('Hapus semua messages');

        $this->actingAs($user)
            ->post(route('admin.spam.destroy-group'), [
                'sender_key' => 'alya',
                'ip_address' => '203.0.113.13',
            ])
            ->assertRedirect(route('admin.spam'));

        $this->assertDatabaseMissing('messages', [
            'sender_key' => 'alya',
            'ip_address' => '203.0.113.13',
        ]);
    }

    public function test_ban_remains_after_messages_are_deleted(): void
    {
        SpamBan::create([
            'sender_key' => 'alya',
            'sender_name' => 'Alya',
            'ip_address' => '203.0.113.14',
            'spam_count' => 10,
            'reason' => '10 spam terdeteksi otomatis.',
            'banned_at' => now(),
        ]);

        Message::factory()->create([
            'sender_name' => 'Alya',
            'sender_key' => 'alya',
            'ip_address' => '203.0.113.14',
            'is_spam' => true,
        ]);

        Message::where('sender_key', 'alya')
            ->where('ip_address', '203.0.113.14')
            ->delete();

        $this->assertDatabaseHas('spam_bans', [
            'sender_key' => 'alya',
            'ip_address' => '203.0.113.14',
        ]);
    }
}
