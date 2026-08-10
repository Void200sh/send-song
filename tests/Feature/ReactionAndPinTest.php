<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReactionAndPinTest extends TestCase
{
    use RefreshDatabase;

    private function createMessage(array $overrides = []): Message
    {
        return Message::create(array_merge([
            'recipient_name' => 'Rina',
            'kelas' => 'XI PPLG 1',
            'message' => 'halo',
            'ip_address' => '127.0.0.1',
        ], $overrides));
    }

    // ─── REAKSI ───

    public function test_visitor_can_add_reaction(): void
    {
        $msg = $this->createMessage();

        $this->postJson(route('messages.react', $msg), ['emoji' => '👍'])
            ->assertOk()
            ->assertJson(['active' => '👍']);

        $this->assertDatabaseHas('message_reactions', [
            'message_id' => $msg->id,
            'emoji' => '👍',
            'ip_address' => '127.0.0.1',
        ]);
    }

    public function test_visitor_can_toggle_reaction_off(): void
    {
        $msg = $this->createMessage();

        $this->postJson(route('messages.react', $msg), ['emoji' => '👍'])->assertOk();
        // Klik lagi → reaksi batal (toggle)
        $this->postJson(route('messages.react', $msg), ['emoji' => '👍'])
            ->assertOk()
            ->assertJson(['active' => null]);

        $this->assertDatabaseMissing('message_reactions', [
            'message_id' => $msg->id,
            'emoji' => '👍',
        ]);
    }

    public function test_reaction_uses_x_forwarded_for_ip_like_message_store(): void
    {
        $msg = $this->createMessage();

        // Konsisten dengan store(): IP asli diambil dari header X-Forwarded-For,
        // bukan IP proxy — biar dua orang di belakang proxy yang sama gak keanggap 1 orang.
        $this->postJson(route('messages.react', $msg), ['emoji' => '👍'], [
            'X-Forwarded-For' => '203.0.113.7, 10.0.0.1',
        ])->assertOk();

        $this->assertDatabaseHas('message_reactions', [
            'message_id' => $msg->id,
            'emoji' => '👍',
            'ip_address' => '203.0.113.7',
        ]);
    }

    public function test_invalid_emoji_is_rejected(): void
    {
        $msg = $this->createMessage();

        $this->postJson(route('messages.react', $msg), ['emoji' => '💩'])
            ->assertUnprocessable();
    }

    public function test_reaction_count_shown_in_feed(): void
    {
        $msg = $this->createMessage();
        MessageReaction::create(['message_id' => $msg->id, 'emoji' => '👍', 'ip_address' => '203.0.113.7']);
        MessageReaction::create(['message_id' => $msg->id, 'emoji' => '👍', 'ip_address' => '203.0.113.8']);

        $this->get(route('messages.index'))
            ->assertOk()
            ->assertSee('data-reactions')
            ->assertSee('👍');
    }

    // ─── PIN ───

    public function test_admin_can_pin_and_unpin_message(): void
    {
        $admin = User::factory()->create();
        $msg = $this->createMessage();

        $this->actingAs($admin)
            ->post(route('admin.messages.pin-toggle', $msg))
            ->assertRedirect();

        $this->assertDatabaseHas('messages', ['id' => $msg->id, 'is_pinned' => true]);

        $this->actingAs($admin)
            ->post(route('admin.messages.pin-toggle', $msg))
            ->assertRedirect();

        $this->assertDatabaseHas('messages', ['id' => $msg->id, 'is_pinned' => false]);
    }

    public function test_pinned_message_appears_first_in_feed(): void
    {
        $older = $this->createMessage(['message' => 'pesan yang di-pin']);
        $newer = $this->createMessage(['message' => 'pesan terbaru']);

        $older->update(['is_pinned' => true, 'pinned_at' => now()]);

        // Pesan ter-pin (yang lebih lama) harus tampil DULUAN di feed,
        // walau ada pesan lain yang dibuat setelahnya.
        $this->get(route('messages.index'))
            ->assertOk()
            ->assertSeeInOrder(['pesan yang di-pin', 'pesan terbaru'])
            ->assertSee('pinned');
    }
}
