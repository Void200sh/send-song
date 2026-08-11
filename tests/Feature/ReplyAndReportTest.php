<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\MessageReport;
use App\Models\MessageReply;
use App\Models\ReplyReaction;
use App\Models\SpamBan;
use App\Models\Sticker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReplyAndReportTest extends TestCase
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

    // ─── BALASAN ───

    public function test_visitor_can_reply_to_message(): void
    {
        $msg = $this->createMessage();

        $this->post(route('messages.reply', $msg), [
            'sender_name' => 'Budi',
            'body' => 'balasan untuk pesan ini',
        ])->assertRedirect();

        $this->assertDatabaseHas('message_replies', [
            'message_id' => $msg->id,
            'sender_name' => 'Budi',
            'body' => 'balasan untuk pesan ini',
        ]);
    }

    public function test_reply_captures_x_forwarded_for_ip(): void
    {
        $msg = $this->createMessage();

        $this->post(route('messages.reply', $msg), [
            'body' => 'halo',
        ], ['X-Forwarded-For' => '203.0.113.7, 10.0.0.1']);

        $this->assertDatabaseHas('message_replies', [
            'message_id' => $msg->id,
            'ip_address' => '203.0.113.7',
        ]);
    }

    public function test_reply_requires_body(): void
    {
        $msg = $this->createMessage();

        $this->post(route('messages.reply', $msg), ['body' => ''])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('message_replies', 0);
    }

    // ─── REPLY KOMENTAR (NESTED 1 TINGKAT) ───

    public function test_visitor_can_reply_to_a_comment(): void
    {
        $msg = $this->createMessage();
        $root = MessageReply::create([
            'message_id' => $msg->id,
            'sender_name' => 'Budi',
            'body' => 'komentar pertama',
            'ip_address' => '127.0.0.1',
        ]);

        $this->post(route('messages.reply', $msg), [
            'sender_name' => 'Siti',
            'body' => 'balasan untuk budi',
            'parent_id' => $root->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('message_replies', [
            'message_id' => $msg->id,
            'parent_id' => $root->id,
            'sender_name' => 'Siti',
            'body' => 'balasan untuk budi',
        ]);
    }

    public function test_reply_to_child_is_attached_to_root(): void
    {
        $msg = $this->createMessage();
        $root = MessageReply::create(['message_id' => $msg->id, 'sender_name' => 'Budi', 'body' => 'root', 'ip_address' => '1.1.1.1']);
        $child = MessageReply::create(['message_id' => $msg->id, 'parent_id' => $root->id, 'sender_name' => 'Siti', 'body' => 'anak', 'ip_address' => '2.2.2.2']);

        // Membalas ke ANAK tetap menempel ke komentar ROOT (depth maksimal 1 tingkat)
        $this->post(route('messages.reply', $msg), [
            'sender_name' => 'Andi',
            'body' => 'balasan lain',
            'parent_id' => $child->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('message_replies', [
            'parent_id' => $root->id,
            'sender_name' => 'Andi',
            'body' => 'balasan lain',
        ]);
    }

    public function test_reply_to_comment_from_other_message_is_rejected(): void
    {
        $msg = $this->createMessage();
        $other = $this->createMessage(['recipient_name' => 'Dewi']);
        $foreign = MessageReply::create(['message_id' => $other->id, 'sender_name' => 'Budi', 'body' => 'komentar pesan lain', 'ip_address' => '1.1.1.1']);

        $this->post(route('messages.reply', $msg), [
            'body' => 'nyasar',
            'parent_id' => $foreign->id,
        ])->assertSessionHasErrors('parent_id');

        $this->assertDatabaseCount('message_replies', 1);
    }

    public function test_child_replies_shown_under_parent_on_detail_page(): void
    {
        $msg = $this->createMessage();
        $root = MessageReply::create(['message_id' => $msg->id, 'sender_name' => 'Budi', 'body' => 'komentar pertama', 'ip_address' => '1.1.1.1']);
        MessageReply::create(['message_id' => $msg->id, 'parent_id' => $root->id, 'sender_name' => 'Siti', 'body' => 'balasan untuk budi', 'ip_address' => '2.2.2.2']);

        $this->get(route('messages.show', $msg))
            ->assertOk()
            ->assertSee('komentar pertama')
            ->assertSee('membalas @Budi')
            ->assertSee('balasan untuk budi')
            ->assertSee('data-reply-to');
    }

    public function test_reply_to_comment_supports_sticker(): void
    {
        $msg = $this->createMessage();
        $root = MessageReply::create(['message_id' => $msg->id, 'sender_name' => 'Budi', 'body' => 'komentar', 'ip_address' => '1.1.1.1']);
        $sticker = Sticker::create(['name' => 'love', 'path' => 'stickers/love.png']);

        $this->post(route('messages.reply', $msg), [
            'sticker_id' => $sticker->id,
            'parent_id' => $root->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('message_replies', [
            'parent_id' => $root->id,
            'sticker_path' => 'stickers/love.png',
            'body' => null,
        ]);
    }

    public function test_admin_replies_page_shows_parent_indicator(): void
    {
        $user = User::factory()->create();
        $msg = $this->createMessage();
        $root = MessageReply::create(['message_id' => $msg->id, 'sender_name' => 'Budi', 'body' => 'komentar', 'ip_address' => '1.1.1.1']);
        MessageReply::create(['message_id' => $msg->id, 'parent_id' => $root->id, 'sender_name' => 'Siti', 'body' => 'balasan', 'ip_address' => '2.2.2.2']);

        $this->actingAs($user)
            ->get(route('admin.replies'))
            ->assertOk()
            ->assertSee('membalas @Budi');
    }

    public function test_deleting_parent_comment_removes_its_children(): void
    {
        $user = User::factory()->create();
        $msg = $this->createMessage();
        $root = MessageReply::create(['message_id' => $msg->id, 'sender_name' => 'Budi', 'body' => 'root', 'ip_address' => '1.1.1.1']);
        $child = MessageReply::create(['message_id' => $msg->id, 'parent_id' => $root->id, 'sender_name' => 'Siti', 'body' => 'anak', 'ip_address' => '2.2.2.2']);

        $this->actingAs($user)
            ->delete(route('admin.replies.destroy', $root))
            ->assertRedirect();

        $this->assertDatabaseMissing('message_replies', ['id' => $root->id]);
        $this->assertDatabaseMissing('message_replies', ['id' => $child->id]);
    }

    // ─── BALASAN STIKER ───

    public function test_visitor_can_reply_with_sticker_only(): void
    {
        $msg = $this->createMessage();
        $sticker = Sticker::create(['name' => 'love', 'path' => 'stickers/love.png']);

        $this->post(route('messages.reply', $msg), [
            'sticker_id' => $sticker->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('message_replies', [
            'message_id' => $msg->id,
            'sticker_path' => 'stickers/love.png',
            'body' => null,
        ]);
    }

    public function test_visitor_can_reply_with_sticker_and_text(): void
    {
        $msg = $this->createMessage();
        $sticker = Sticker::create(['name' => 'senyum', 'path' => 'stickers/senyum.webp']);

        $this->post(route('messages.reply', $msg), [
            'sender_name' => 'Budi',
            'body' => 'love banget!',
            'sticker_id' => $sticker->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('message_replies', [
            'message_id' => $msg->id,
            'sender_name' => 'Budi',
            'body' => 'love banget!',
            'sticker_path' => 'stickers/senyum.webp',
        ]);
    }

    public function test_reply_with_unknown_sticker_is_rejected(): void
    {
        $msg = $this->createMessage();

        $this->post(route('messages.reply', $msg), ['sticker_id' => 9999])
            ->assertSessionHasErrors('sticker_id');

        $this->assertDatabaseCount('message_replies', 0);
    }

    public function test_reply_without_body_or_sticker_is_rejected(): void
    {
        $msg = $this->createMessage();

        $this->post(route('messages.reply', $msg), ['sender_name' => 'Budi', 'body' => '   '])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('message_replies', 0);
    }

    public function test_sticker_picker_rendered_on_detail_page_when_stickers_exist(): void
    {
        $msg = $this->createMessage();
        $sticker = Sticker::create(['name' => 'love', 'path' => 'stickers/love.png']);

        $this->get(route('messages.show', $msg))
            ->assertOk()
            ->assertSee('data-sticker-option')
            ->assertSee('tambah stiker')
            ->assertSee($sticker->url());
    }

    public function test_sticker_thumbnail_shown_in_reply(): void
    {
        $msg = $this->createMessage();
        MessageReply::create([
            'message_id' => $msg->id,
            'sender_name' => 'Budi',
            'body' => null,
            'sticker_path' => 'stickers/love.png',
            'ip_address' => '127.0.0.1',
        ]);

        $this->get(route('messages.show', $msg))
            ->assertOk()
            ->assertSee('stickers/love.png');
    }

    // ─── ADMIN: KELOLA STIKER ───

    public function test_admin_dashboard_renders_sticker_section(): void
    {
        $user = User::factory()->create();
        Sticker::create(['name' => 'love', 'path' => 'stickers/love.png']);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Kelola Stiker')
            ->assertSee('stickers/love.png');
    }

    public function test_admin_can_store_sticker(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.stickers.store'), [
                'name' => 'teriak',
                'sticker' => \Illuminate\Http\UploadedFile::fake()->image('teriak.png'),
            ])->assertRedirect();

        $this->assertDatabaseHas('stickers', ['name' => 'teriak']);
    }

    public function test_admin_can_delete_sticker(): void
    {
        $user = User::factory()->create();
        $sticker = Sticker::create(['name' => 'love', 'path' => 'stickers/love.png']);

        $this->actingAs($user)
            ->delete(route('admin.stickers.destroy', $sticker))
            ->assertRedirect();

        $this->assertDatabaseMissing('stickers', ['id' => $sticker->id]);
    }

    public function test_replies_shown_on_detail_page(): void
    {
        $msg = $this->createMessage();
        MessageReply::create([
            'message_id' => $msg->id,
            'sender_name' => 'Budi',
            'body' => 'salam kenal',
            'ip_address' => '127.0.0.1',
        ]);

        $this->get(route('messages.show', $msg))
            ->assertOk()
            ->assertSee('balasan')
            ->assertSee('Budi')
            ->assertSee('salam kenal');
    }

    public function test_reply_count_shown_in_feed(): void
    {
        $msg = $this->createMessage();
        MessageReply::create(['message_id' => $msg->id, 'sender_name' => 'A', 'body' => 'satu', 'ip_address' => '1.1.1.1']);
        MessageReply::create(['message_id' => $msg->id, 'sender_name' => 'B', 'body' => 'dua', 'ip_address' => '2.2.2.2']);

        $this->get(route('messages.index'))
            ->assertOk()
            ->assertSee('2 balasan');
    }

    // ─── REAKSI BALASAN ───

    public function test_visitor_can_react_to_reply(): void
    {
        $msg = $this->createMessage();
        $reply = MessageReply::create(['message_id' => $msg->id, 'sender_name' => 'Budi', 'body' => 'balasan', 'ip_address' => '127.0.0.1']);

        $this->postJson(route('replies.react', $reply), ['emoji' => '👍'])
            ->assertOk()
            ->assertJson(['active' => '👍']);

        $this->assertDatabaseHas('reply_reactions', [
            'reply_id' => $reply->id,
            'emoji' => '👍',
            'ip_address' => '127.0.0.1',
        ]);
    }

    public function test_visitor_can_toggle_reply_reaction_off(): void
    {
        $msg = $this->createMessage();
        $reply = MessageReply::create(['message_id' => $msg->id, 'sender_name' => 'Budi', 'body' => 'balasan', 'ip_address' => '127.0.0.1']);

        $this->postJson(route('replies.react', $reply), ['emoji' => '❤️'])->assertOk();
        $this->postJson(route('replies.react', $reply), ['emoji' => '❤️'])
            ->assertOk()
            ->assertJson(['active' => null]);

        $this->assertDatabaseMissing('reply_reactions', [
            'reply_id' => $reply->id,
            'emoji' => '❤️',
        ]);
    }

    public function test_reply_reaction_uses_x_forwarded_for_ip(): void
    {
        $msg = $this->createMessage();
        $reply = MessageReply::create(['message_id' => $msg->id, 'sender_name' => 'Budi', 'body' => 'balasan', 'ip_address' => '127.0.0.1']);

        $this->postJson(route('replies.react', $reply), ['emoji' => '👍'], [
            'X-Forwarded-For' => '203.0.113.7, 10.0.0.1',
        ])->assertOk();

        $this->assertDatabaseHas('reply_reactions', [
            'reply_id' => $reply->id,
            'emoji' => '👍',
            'ip_address' => '203.0.113.7',
        ]);
    }

    public function test_reply_reaction_count_shown_on_detail_page(): void
    {
        $msg = $this->createMessage();
        $reply = MessageReply::create(['message_id' => $msg->id, 'sender_name' => 'Budi', 'body' => 'balasan', 'ip_address' => '127.0.0.1']);
        ReplyReaction::create(['reply_id' => $reply->id, 'emoji' => '👍', 'ip_address' => '203.0.113.7']);
        ReplyReaction::create(['reply_id' => $reply->id, 'emoji' => '👍', 'ip_address' => '203.0.113.8']);

        $this->get(route('messages.show', $msg))
            ->assertOk()
            ->assertSee('data-reply-reactions')
            ->assertSee('👍');
    }

    // ─── LAPORAN ───

    public function test_visitor_can_report_message(): void
    {
        $msg = $this->createMessage();

        $this->postJson(route('messages.report', $msg), ['reason' => 'spam'])
            ->assertOk();

        $this->assertDatabaseHas('message_reports', [
            'message_id' => $msg->id,
            'reason' => 'spam',
            'ip_address' => '127.0.0.1',
        ]);
    }

    public function test_report_saves_custom_other_reason(): void
    {
        $msg = $this->createMessage();

        $this->postJson(route('messages.report', $msg), ['reason' => 'pesan ini menyinggung keluarga saya'])
            ->assertOk();

        $this->assertDatabaseHas('message_reports', [
            'message_id' => $msg->id,
            'reason' => 'pesan ini menyinggung keluarga saya',
        ]);
    }

    public function test_report_is_idempotent_per_visitor(): void
    {
        $msg = $this->createMessage();

        $this->postJson(route('messages.report', $msg))->assertOk();
        $this->postJson(route('messages.report', $msg))->assertOk();

        // Satu pengunjung (IP sama) hanya membuat 1 laporan per pesan
        $this->assertDatabaseCount('message_reports', 1);
    }

    public function test_report_captures_x_forwarded_for_ip(): void
    {
        $msg = $this->createMessage();

        $this->postJson(route('messages.report', $msg), [], [
            'X-Forwarded-For' => '203.0.113.7, 10.0.0.1',
        ])->assertOk();

        $this->assertDatabaseHas('message_reports', [
            'message_id' => $msg->id,
            'ip_address' => '203.0.113.7',
        ]);
    }

    public function test_spam_message_cannot_be_reported(): void
    {
        $msg = $this->createMessage(['is_spam' => true]);

        $this->postJson(route('messages.report', $msg))->assertNotFound();
    }

    // ─── ADMIN: HALAMAN & MODERASI BALASAN ───

    public function test_admin_replies_page_renders_with_reply_context(): void
    {
        $user = User::factory()->create();
        $msg = $this->createMessage(['message' => 'pesan asli', 'ip_address' => '198.51.100.7']);
        MessageReply::create([
            'message_id' => $msg->id,
            'sender_name' => 'Komentator Nakal',
            'body' => 'komentar nyeleneh',
            'ip_address' => '203.0.113.9',
        ]);

        $this->actingAs($user)
            ->get(route('admin.replies'))
            ->assertOk()
            ->assertSee('Komentator Nakal')
            ->assertSee('komentar nyeleneh')
            ->assertSee('buka pesan')
            ->assertSee('203.0.113.9');
    }

    public function test_admin_can_delete_reply(): void
    {
        $user = User::factory()->create();
        $msg = $this->createMessage();
        $reply = MessageReply::create([
            'message_id' => $msg->id,
            'sender_name' => 'Spammer',
            'body' => 'hapus aku',
            'ip_address' => '203.0.113.9',
        ]);

        $this->actingAs($user)
            ->delete(route('admin.replies.destroy', $reply))
            ->assertRedirect();

        $this->assertDatabaseMissing('message_replies', ['id' => $reply->id]);
        // Pesan induk tetap aman
        $this->assertDatabaseHas('messages', ['id' => $msg->id]);
    }

    public function test_admin_replies_search_filters_by_name(): void
    {
        $user = User::factory()->create();
        $msg = $this->createMessage();
        MessageReply::create(['message_id' => $msg->id, 'sender_name' => 'Budi', 'body' => 'satu', 'ip_address' => '1.1.1.1']);
        MessageReply::create(['message_id' => $msg->id, 'sender_name' => 'Siti', 'body' => 'dua', 'ip_address' => '2.2.2.2']);

        $this->actingAs($user)
            ->get(route('admin.replies', ['search' => 'Budi']))
            ->assertOk()
            ->assertSee('Budi')
            ->assertDontSee('Siti');
    }

    // ─── ADMIN: TINDAK LANJUT LAPORAN ───

    public function test_admin_reports_page_renders(): void
    {
        $user = User::factory()->create();
        $msg = $this->createMessage(['message' => 'pesan tidak pantas', 'ip_address' => '198.51.100.7']);
        MessageReport::create(['message_id' => $msg->id, 'ip_address' => '203.0.113.9', 'reason' => 'spam iklan']);

        $this->actingAs($user)
            ->get(route('admin.reports'))
            ->assertOk()
            ->assertSee('pesan tidak pantas')
            ->assertSee('198.51.100.7')
            ->assertSee('spam iklan')
            ->assertSee('Ban IP');
    }

    public function test_admin_can_ban_ip_from_report(): void
    {
        $user = User::factory()->create();
        $msg = $this->createMessage(['ip_address' => '198.51.100.7']);
        $report = MessageReport::create(['message_id' => $msg->id, 'ip_address' => '203.0.113.9']);

        $this->actingAs($user)
            ->post(route('admin.reports.ban-ip', $report))
            ->assertRedirect();

        $this->assertDatabaseHas('spam_bans', [
            'sender_key' => '*',
            'ip_address' => '198.51.100.7',
        ]);
        $this->assertDatabaseHas('message_reports', [
            'id' => $report->id,
            'is_resolved' => true,
        ]);
    }

    public function test_banned_ip_cannot_send_message(): void
    {
        SpamBan::create([
            'sender_key' => '*',
            'sender_name' => null,
            'ip_address' => '198.51.100.7',
            'spam_count' => 0,
            'reason' => 'Diblokir manual dari laporan pengunjung.',
            'banned_at' => now(),
        ]);

        $this->post('/messages', [
            'recipient_name' => 'Rina',
            'kelas' => 'XI PPLG 1',
            'message' => 'halo',
        ], ['X-Forwarded-For' => '198.51.100.7'])
            ->assertForbidden();
    }

    public function test_admin_can_delete_reported_message(): void
    {
        $user = User::factory()->create();
        $msg = $this->createMessage();
        $report = MessageReport::create(['message_id' => $msg->id, 'ip_address' => '203.0.113.9']);

        $this->actingAs($user)
            ->delete(route('admin.reports.delete-message', $report))
            ->assertRedirect();

        $this->assertDatabaseMissing('messages', ['id' => $msg->id]);
        $this->assertDatabaseMissing('message_reports', ['id' => $report->id]);
    }

    public function test_admin_can_resolve_and_delete_report(): void
    {
        $user = User::factory()->create();
        $msg = $this->createMessage();
        $report = MessageReport::create(['message_id' => $msg->id, 'ip_address' => '203.0.113.9']);

        $this->actingAs($user)
            ->post(route('admin.reports.resolve', $report))
            ->assertRedirect();

        $this->assertDatabaseHas('message_reports', ['id' => $report->id, 'is_resolved' => true]);

        $this->actingAs($user)
            ->delete(route('admin.reports.destroy', $report))
            ->assertRedirect();

        $this->assertDatabaseMissing('message_reports', ['id' => $report->id]);
        // Pesan tidak ikut terhapus kalau cuma laporannya yang dihapus
        $this->assertDatabaseHas('messages', ['id' => $msg->id]);
    }

    // ─── TEMA BARU ───

    public function test_new_themes_are_accepted_on_store(): void
    {
        foreach (['neon', 'film', 'pastel'] as $theme) {
            $this->post('/messages', [
                'recipient_name' => 'Rina',
                'kelas' => 'XI PPLG 1',
                'message' => 'pesan tema ' . $theme,
                'theme' => $theme,
            ])->assertRedirect();
        }

        $this->assertDatabaseHas('messages', ['theme' => 'neon']);
        $this->assertDatabaseHas('messages', ['theme' => 'film']);
        $this->assertDatabaseHas('messages', ['theme' => 'pastel']);
    }
}
