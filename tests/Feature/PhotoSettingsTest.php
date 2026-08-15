<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Message;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoSettingsTest extends TestCase
{
    use RefreshDatabase;

    private const PNG_1PX = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private function dataUrl(): string
    {
        return 'data:image/png;base64,' . self::PNG_1PX;
    }

    private function createMessageWithPhoto(): Message
    {
        Storage::fake('public');
        return Message::create([
            'recipient_name' => 'Rina',
            'kelas' => 'XI PPLG 1',
            'message' => 'halo',
            'photo_path' => Storage::disk('public')->put('photos/2026/01/cam_test.jpg', 'xx'),
        ]);
    }

    public function test_default_aktif_foto_tampil_di_browse_dan_menu_kamera_ada(): void
    {
        Settings::set('photos_enabled', '1');
        $msg = $this->createMessageWithPhoto();

        $this->get('/messages')
            ->assertOk()
            ->assertSee('data-photo-open', false)
            ->assertSee('pr-32', false);

        $this->get(route('story.create'))
            ->assertOk()
            ->assertSee('data-camera', false)
            ->assertSee('buka kamera');
    }

    public function test_nonaktif_foto_disembunyikan_dari_card_browse_dan_detail(): void
    {
        Settings::set('photos_enabled', '0');
        $msg = $this->createMessageWithPhoto();

        $this->get('/messages')
            ->assertOk()
            ->assertDontSee('data-photo-open', false)
            ->assertDontSee('pr-32', false)
            ->assertDontSee('/storage/' . $msg->photo_path, false);

        $this->get('/messages/' . $msg->id)
            ->assertOk()
            ->assertDontSee('data-photo-open', false);
    }

    public function test_nonaktif_menu_kamera_hilang_dari_form_kirim(): void
    {
        Settings::set('photos_enabled', '0');

        $this->get(route('story.create'))
            ->assertOk()
            ->assertDontSee('data-camera', false)
            ->assertDontSee('buka kamera');
    }

    public function test_nonaktif_store_mengabaikan_foto(): void
    {
        Settings::set('photos_enabled', '0');
        Storage::fake('public');

        $this->post('/messages', [
            'recipient_name' => 'Rina',
            'kelas' => 'XI PPLG 1',
            'message' => 'halo',
            'photo' => $this->dataUrl(),
        ])->assertRedirect();

        $this->assertDatabaseHas('messages', ['recipient_name' => 'Rina', 'photo_path' => null]);
        Storage::disk('public')->assertDirectoryEmpty('photos');
    }

    public function test_nonaktif_admin_panel_masih_menampilkan_thumbnail_foto(): void
    {
        Settings::set('photos_enabled', '0');
        $msg = $this->createMessageWithPhoto();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.messages'))
            ->assertOk()
            ->assertSee('/storage/' . $msg->photo_path, false);
    }

    public function test_admin_bisa_toggle_fitur_foto(): void
    {
        Settings::set('photos_enabled', '0');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.settings.photo-toggle'))
            ->assertRedirect();

        $this->assertTrue(Settings::photosEnabled());
        $this->assertDatabaseHas('settings', ['key' => 'photos_enabled', 'value' => '1']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'settings.photo-toggle']);

        // Toggle lagi → kembali nonaktif
        $this->actingAs($user)
            ->post(route('admin.settings.photo-toggle'))
            ->assertRedirect();

        $this->assertFalse(Settings::photosEnabled());
        $this->assertDatabaseHas('settings', ['key' => 'photos_enabled', 'value' => '0']);
        $this->assertSame(2, AuditLog::where('action', 'settings.photo-toggle')->count());
    }
}
