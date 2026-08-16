<?php

namespace Tests\Feature;

use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MessagePhotoTest extends TestCase
{
    use RefreshDatabase;

    // PNG transparan 1x1 yang valid (base64) — cukup untuk getimagesizefromstring.
    private const PNG_1PX = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private function dataUrl(string $base64): string
    {
        return 'data:image/png;base64,' . $base64;
    }

    public function test_store_menyimpan_foto_kamera_ke_disk_public(): void
    {
        Storage::fake('public');

        $this->post('/messages', [
            'recipient_name' => 'Rina',
            'kelas' => 'XI PPLG 1',
            'message' => 'halo',
            'photo' => $this->dataUrl(self::PNG_1PX),
        ]);

        $msg = Message::where('recipient_name', 'Rina')->first();
        $this->assertNotNull($msg);
        $this->assertNotNull($msg->photo_path);
        $this->assertStringStartsWith('photos/', $msg->photo_path);
        Storage::disk('public')->assertExists($msg->photo_path);
        $this->assertStringContainsString('/storage/' . $msg->photo_path, $msg->photoUrl());
    }

    public function test_store_tanpa_foto_tetap_berhasil(): void
    {
        Storage::fake('public');

        $this->post('/messages', [
            'recipient_name' => 'Rina',
            'kelas' => 'XI PPLG 1',
            'message' => 'halo',
        ])->assertRedirect();

        $this->assertDatabaseHas('messages', ['recipient_name' => 'Rina', 'photo_path' => null]);
    }

    public function test_store_menolak_data_yang_bukan_gambar(): void
    {
        Storage::fake('public');

        // Base64 dari teks acak — bukan gambar → harus diabaikan, tapi kirim tetap sukses.
        $this->post('/messages', [
            'recipient_name' => 'Rina',
            'kelas' => 'XI PPLG 1',
            'message' => 'halo',
            'photo' => $this->dataUrl(base64_encode('ini bukan gambar')),
        ])->assertRedirect();

        $this->assertDatabaseHas('messages', ['recipient_name' => 'Rina', 'photo_path' => null]);
        Storage::disk('public')->assertDirectoryEmpty('photos');
    }

    public function test_store_menolak_string_tanpa_prefix_data_url(): void
    {
        Storage::fake('public');

        $this->post('/messages', [
            'recipient_name' => 'Rina',
            'kelas' => 'XI PPLG 1',
            'message' => 'halo',
            'photo' => self::PNG_1PX, // bukan data URL → harus diabaikan
        ])->assertRedirect();

        $this->assertDatabaseHas('messages', ['recipient_name' => 'Rina', 'photo_path' => null]);
    }

    public function test_card_browse_menampilkan_thumbnail_floating_bukan_full_width(): void
    {
        Storage::fake('public');
        $msg = Message::create([
            'recipient_name' => 'Rina',
            'kelas' => 'XI PPLG 1',
            'message' => 'halo',
            'photo_path' => Storage::disk('public')->put('photos/2026/01/cam_test.jpg', 'xx'),
        ]);

        $res = $this->get('/messages');

        $res->assertOk();
        // Thumbnail klik-ke-lightbox ada dengan marker scrapbook
        $res->assertSee('data-photo-open', false);
        $res->assertSee('data-photo-print', false);
        // Rotasi deterministik per message (gaya scrapbook) dirender inline
        $res->assertSee('style="rotate:', false);
        $res->assertSee('/storage/' . $msg->photo_path, false);
        // Banner full-width lama tidak ada lagi
        $res->assertDontSee('h-40');
        $res->assertDontSee('class="w-full h-40');
    }

    public function test_detail_page_foto_bisa_dibuka_lightbox(): void
    {
        Storage::fake('public');
        $msg = Message::create([
            'recipient_name' => 'Rina',
            'kelas' => 'XI PPLG 1',
            'message' => 'halo',
            'photo_path' => Storage::disk('public')->put('photos/2026/01/cam_test.jpg', 'xx'),
        ]);

        $this->get('/messages/' . $msg->id)
            ->assertOk()
            ->assertSee('data-photo-open', false)
            ->assertSee('/storage/' . $msg->photo_path, false);
    }
}
