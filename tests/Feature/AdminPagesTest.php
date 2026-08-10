<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_and_messages_pages_render(): void
    {
        $user = User::factory()->create();

        Message::create([
            'sender_name' => 'anon',
            'recipient_name' => 'Rina',
            'kelas' => 'XI PPLG 1',
            'ip_address' => '192.168.1.10',
            'message' => 'halo',
            'song_title' => 'Sampai Akhir Waktu',
            'song_artist' => 'Yovie & Nuno',
            'cover_url' => 'https://example.com/c.jpg',
            'youtube_video_id' => null,
        ]);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Lagu Terpopuler')
            ->assertSee('Statistik Lagu')
            ->assertSee('Sampai Akhir Waktu');

        $this->actingAs($user)
            ->get(route('admin.messages'))
            ->assertOk()
            ->assertSee('Sampai Akhir Waktu')
            ->assertSee('192.168.1.10')
            ->assertSee('resolve');
    }

    public function test_admin_songs_page_shows_unique_songs_with_play_link(): void
    {
        $user = User::factory()->create();

        Message::create([
            'recipient_name' => 'Rina',
            'kelas' => 'XI PPLG 1',
            'message' => 'halo',
            'song_title' => 'Rungkad',
            'song_artist' => 'Happy Asmara',
            'youtube_video_id' => 'abc123',
        ]);
        Message::create([
            'recipient_name' => 'Dodi',
            'kelas' => 'XI PPLG 1',
            'message' => 'hai',
            'song_title' => 'Rungkad',
            'song_artist' => 'Happy Asmara',
            'youtube_video_id' => 'abc123',
        ]);
        Message::create([
            'recipient_name' => 'Sinta',
            'kelas' => 'X PPLG 1',
            'message' => 'yo',
            'song_title' => 'Fall In Love',
            'song_artist' => 'Kenny Chesney',
        ]);

        $this->actingAs($user)
            ->get(route('admin.songs'))
            ->assertOk()
            ->assertSee('Rungkad')
            ->assertSee('2x')
            ->assertSee('abc123')
            ->assertSee('Happy Asmara');
    }

    public function test_admin_kelas_page_shows_per_class_stats(): void
    {
        $user = User::factory()->create();

        Message::create([
            'recipient_name' => 'Rina',
            'kelas' => 'XI PPLG 1',
            'message' => 'halo',
            'song_title' => 'Rungkad',
            'song_artist' => 'Happy Asmara',
        ]);
        Message::create([
            'recipient_name' => 'Sinta',
            'kelas' => 'X PPLG 1',
            'message' => 'yo',
        ]);

        $this->actingAs($user)
            ->get(route('admin.kelas'))
            ->assertOk()
            ->assertSee('XI PPLG 1')
            ->assertSee('X PPLG 1')
            ->assertSee('Lihat Pesan Kelas Ini');
    }

    public function test_admin_export_page_and_csv_downloads(): void
    {
        $user = User::factory()->create();

        Message::create([
            'sender_name' => 'anon',
            'recipient_name' => 'Rina',
            'kelas' => 'XI PPLG 1',
            'message' => 'halo, ini pesan',
            'song_title' => 'Rungkad',
            'song_artist' => 'Happy Asmara',
        ]);

        $this->actingAs($user)
            ->get(route('admin.export'))
            ->assertOk()
            ->assertSee('Unduh CSV Pesan')
            ->assertSee('Unduh CSV Lagu');

        $this->actingAs($user)
            ->get(route('admin.export.messages'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertSee('Judul Lagu')
            ->assertSee('Rungkad');

        $this->actingAs($user)
            ->get(route('admin.export.songs'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertSee('Rungkad');
    }
}
