<?php

namespace Tests\Feature;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_feedback_with_saran_and_kritik_is_stored(): void
    {
        $this->post('/feedback', [
            'saran' => 'Tambahin fitur dark mode.',
            'kritik' => 'Loading lagunya agak lama.',
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('feedbacks', [
            'saran' => 'Tambahin fitur dark mode.',
            'kritik' => 'Loading lagunya agak lama.',
            'ip_address' => '127.0.0.1',
        ]);
    }

    public function test_feedback_can_be_sent_with_only_one_column(): void
    {
        $this->post('/feedback', [
            'saran' => '',
            'kritik' => 'Suka banget sama tema kartunya!',
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('feedbacks', [
            'saran' => null,
            'kritik' => 'Suka banget sama tema kartunya!',
        ]);
    }

    public function test_feedback_with_both_empty_is_rejected(): void
    {
        $this->post('/feedback', [
            'saran' => '   ',
            'kritik' => '',
        ])->assertStatus(422);

        $this->assertDatabaseCount('feedbacks', 0);
    }

    public function test_feedback_rejects_too_long_input(): void
    {
        $this->post('/feedback', [
            'saran' => str_repeat('a', 2001),
            'kritik' => 'ok',
        ])->assertSessionHasErrors('saran');

        $this->assertDatabaseCount('feedbacks', 0);
    }

    public function test_story_store_redirects_with_feedback_flag(): void
    {
        // Kirim story → redirect ke /messages dengan ?feedback=1 (modal saran & kritik)
        $this->post('/messages', [
            'recipient_name' => 'Rina',
            'kelas' => 'XI PPLG 1',
            'message' => 'halo dari test',
        ])->assertRedirect(route('messages.index', ['feedback' => 1]));
    }

    public function test_browse_page_render_with_feedback_flag(): void
    {
        // Halaman browse dengan ?feedback=1 tetap render normal (modal dibuka via JS)
        $this->get(route('messages.index', ['feedback' => 1]))
            ->assertOk();
    }

    public function test_spam_story_redirects_without_feedback_flag(): void
    {
        // Pesan yang terdeteksi spam → redirect polos (tanpa ?feedback=1),
        // jadi modal saran & kritik TIDAK muncul untuk pengirim spam.
        $spam = app(\App\Services\SpamDetectionService::class);
        $fingerprint = $spam->fingerprint('pesan berulang banget');
        $identityKey = hash('sha256', 'bot|203.0.113.66');

        \App\Models\Message::factory()->count(5)->create([
            'sender_name' => 'Bot',
            'sender_key' => 'bot',
            'ip_address' => '203.0.113.66',
            'spam_identity_key' => $identityKey,
            'spam_fingerprint' => $fingerprint,
            'is_spam' => false,
            'message' => 'pesan berulang banget',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.66'])
            ->post(route('messages.store'), [
                'sender_name' => 'Bot',
                'recipient_name' => 'Rina',
                'kelas' => 'XI PPLG 1',
                'message' => 'pesan berulang banget',
            ])
            ->assertRedirect(route('messages.index'));

        $this->assertDatabaseHas('messages', [
            'ip_address' => '203.0.113.66',
            'is_spam' => 1,
        ]);
    }

    public function test_admin_page_lists_feedback_and_can_delete(): void
    {
        $admin = User::factory()->create();
        $fb = Feedback::create([
            'saran' => 'Tambah fitur voting lagu.',
            'kritik' => null,
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.feedbacks'))
            ->assertOk()
            ->assertSee('Saran &amp; Kritik 💌', false)
            ->assertSee('Tambah fitur voting lagu.');

        $this->actingAs($admin)
            ->delete(route('admin.feedbacks.destroy', $fb))
            ->assertRedirect();

        $this->assertDatabaseCount('feedbacks', 0);
    }

    public function test_admin_feedback_search_by_text(): void
    {
        $admin = User::factory()->create();
        Feedback::create(['saran' => 'Tambahkan tema neon.', 'kritik' => null, 'ip_address' => '127.0.0.1']);
        Feedback::create(['saran' => 'Perbaiki loading.', 'kritik' => null, 'ip_address' => '127.0.0.1']);

        $this->actingAs($admin)
            ->get(route('admin.feedbacks', ['search' => 'neon']))
            ->assertOk()
            ->assertSee('Tambahkan tema neon.')
            ->assertDontSee('Perbaiki loading.');
    }
}
