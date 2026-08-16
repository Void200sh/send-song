<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminMessageStoreTest extends TestCase
{
    use RefreshDatabase;

    private const PNG_1PX = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private function dataUrl(): string
    {
        return 'data:image/png;base64,' . self::PNG_1PX;
    }

    private function adminUser(): User
    {
        return User::factory()->create();
    }

    private function fakeOembed(): void
    {
        Http::fake([
            'youtube.com/oembed*' => Http::response([
                'title' => 'Rungkad - Happy Asmara (Official Music Video)',
                'author_name' => 'Happy Asmara Official',
                'thumbnail_url' => 'https://i.ytimg.com/vi/abcDEF12345/hqdefault.jpg',
            ]),
        ]);
    }

    public function test_admin_bisa_tambah_pesan_tanpa_lagu(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)
            ->post(route('admin.messages.store'), [
                'sender_name' => 'admin',
                'recipient_name' => 'Rina',
                'kelas' => 'XI PPLG 1',
                'message' => 'pesan dari admin',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('messages', [
            'recipient_name' => 'Rina',
            'kelas' => 'XI PPLG 1',
            'message' => 'pesan dari admin',
            'sender_name' => 'admin',
            'is_spam' => false,
            'youtube_video_id' => null,
        ]);

        // Audit log tercatat & pesan tampil di feed publik
        $this->assertDatabaseHas('audit_logs', ['action' => 'messages.store-admin']);
        $this->get('/messages')->assertOk()->assertSee('pesan dari admin', false);
    }

    public function test_link_youtube_valid_auto_fill_judul_penyanyi_dan_cover(): void
    {
        $this->fakeOembed();
        $user = $this->adminUser();

        $this->actingAs($user)
            ->post(route('admin.messages.store'), [
                'recipient_name' => 'Rina',
                'kelas' => 'XI PPLG 1',
                'message' => 'dedikasi lagu',
                'youtube_url' => 'https://youtu.be/abcDEF12345',
                'clip_start_seconds' => 10,
                'clip_end_seconds' => 30,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('messages', [
            'recipient_name' => 'Rina',
            'youtube_video_id' => 'abcDEF12345',
            'song_title' => 'Rungkad - Happy Asmara (Official Music Video)',
            'song_artist' => 'Happy Asmara Official',
            'cover_url' => 'https://i.ytimg.com/vi/abcDEF12345/hqdefault.jpg',
            'clip_start_seconds' => 10,
            'clip_end_seconds' => 30,
        ]);

        // oEmbed benar-benar dipanggil dengan URL video yang diekstrak
        // (query di-encode → urldecode dulu sebelum cek)
        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/oembed')
            && str_contains(urldecode($r->url()), 'watch?v=abcDEF12345'));
    }

    public function test_link_bukan_youtube_ditolak(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)
            ->post(route('admin.messages.store'), [
                'recipient_name' => 'Rina',
                'kelas' => 'XI PPLG 1',
                'message' => 'halo',
                'youtube_url' => 'https://example.com/video/abcDEF12345',
            ])
            ->assertSessionHasErrors('youtube_url');

        $this->assertDatabaseMissing('messages', ['recipient_name' => 'Rina']);
    }

    public function test_klip_dengan_end_kurang_dari_start_diabaikan(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)
            ->post(route('admin.messages.store'), [
                'recipient_name' => 'Rina',
                'kelas' => 'XI PPLG 1',
                'message' => 'halo',
                'clip_start_seconds' => 50,
                'clip_end_seconds' => 10,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('messages', [
            'recipient_name' => 'Rina',
            'clip_start_seconds' => null,
            'clip_end_seconds' => null,
        ]);
    }

    public function test_foto_kamera_tersimpan_dari_form_admin(): void
    {
        Storage::fake('public');
        $user = $this->adminUser();

        $this->actingAs($user)
            ->post(route('admin.messages.store'), [
                'recipient_name' => 'Rina',
                'kelas' => 'XI PPLG 1',
                'message' => 'halo',
                'photo' => $this->dataUrl(),
            ])
            ->assertRedirect();

        $msg = Message::where('recipient_name', 'Rina')->first();
        $this->assertNotNull($msg);
        $this->assertNotNull($msg->photo_path);
        Storage::disk('public')->assertExists($msg->photo_path);
    }

    public function test_foto_diabaikan_saat_fitur_foto_nonaktif(): void
    {
        Settings::set('photos_enabled', '0');
        Storage::fake('public');
        $user = $this->adminUser();

        $this->actingAs($user)
            ->post(route('admin.messages.store'), [
                'recipient_name' => 'Rina',
                'kelas' => 'XI PPLG 1',
                'message' => 'halo',
                'photo' => $this->dataUrl(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('messages', ['recipient_name' => 'Rina', 'photo_path' => null]);
        Storage::disk('public')->assertDirectoryEmpty('photos');
    }

    public function test_endpoint_youtube_info_mengembalikan_json(): void
    {
        $this->fakeOembed();
        $user = $this->adminUser();

        $this->actingAs($user)
            ->get(route('admin.songs.youtube-info', ['url' => 'https://www.youtube.com/watch?v=abcDEF12345']))
            ->assertOk()
            ->assertJson([
                'title' => 'Rungkad - Happy Asmara (Official Music Video)',
                'author' => 'Happy Asmara Official',
                'thumbnail' => 'https://i.ytimg.com/vi/abcDEF12345/hqdefault.jpg',
            ]);

        $this->actingAs($user)
            ->get(route('admin.songs.youtube-info', ['url' => 'https://example.com/abcDEF12345']))
            ->assertStatus(422);
    }

    public function test_field_wajib_divalidasi(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)
            ->post(route('admin.messages.store'), [])
            ->assertSessionHasErrors(['recipient_name', 'kelas', 'message']);
    }
}