<?php

namespace Tests\Feature;

use App\Services\YouTubeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_captures_ip_from_x_forwarded_for_header(): void
    {
        // Simulasi akses lewat proxy/CDN: IP asli client ada di header X-Forwarded-For
        // (bisa berisi daftar IP dipisah koma — yang diambil yang paling kiri).
        $this->post('/messages', [
            'recipient_name' => 'Rina',
            'kelas' => 'XI PPLG 1',
            'message' => 'halo',
        ], ['X-Forwarded-For' => '203.0.113.7, 10.0.0.1']);

        $this->assertDatabaseHas('messages', [
            'recipient_name' => 'Rina',
            'ip_address' => '203.0.113.7',
        ]);
    }

    public function test_store_captures_ip_without_proxy_header(): void
    {
        // Tanpa header proxy, IP diambil dari REMOTE_ADDR (default 127.0.0.1 di test).
        $this->post('/messages', [
            'recipient_name' => 'Rina',
            'kelas' => 'XI PPLG 1',
            'message' => 'halo',
        ]);

        $this->assertDatabaseHas('messages', [
            'recipient_name' => 'Rina',
            'ip_address' => '127.0.0.1',
        ]);
    }

    public function test_store_resolves_youtube_server_side_when_client_was_too_fast(): void
    {
        // Simulasi user submit SEBELUM resolve client selesai: kirim judul lagu
        // tanpa youtube_video_id → server harus resolve sendiri biar lagunya muncul.
        $this->mock(YouTubeService::class, function ($mock) {
            $mock->shouldReceive('searchAudio')
                ->once()
                ->with('Sempurna', 'Andra And The Backbone')
                ->andReturn(['youtube_id' => 'abc123yt', 'title' => 'x', 'score' => 7]);
        });

        $this->post('/messages', [
            'recipient_name' => 'Rina',
            'kelas' => 'XI PPLG 1',
            'message' => 'halo',
            'song_title' => 'Sempurna',
            'song_artist' => 'Andra And The Backbone',
        ]);

        $this->assertDatabaseHas('messages', [
            'recipient_name' => 'Rina',
            'song_title' => 'Sempurna',
            'youtube_video_id' => 'abc123yt',
        ]);
    }

    public function test_feed_shows_song_title_even_without_youtube_or_spotify_id(): void
    {
        // Pesan lama/aneh yang cuma punya judul lagu (tanpa id YouTube & Spotify)
        // tetap harus menampilkan judul + artis di feed, biar lagunya gak "hilang".
        \App\Models\Message::create([
            'recipient_name' => 'Rina',
            'kelas' => 'XI PPLG 1',
            'message' => 'halo',
            'song_title' => 'Lagu Tanpa ID',
            'song_artist' => 'Artis X',
        ]);

        $this->get(route('messages.index'))
            ->assertOk()
            ->assertSee('Lagu Tanpa ID')
            ->assertSee('Artis X');
    }
}
