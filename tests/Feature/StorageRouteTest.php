<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorageRouteTest extends TestCase
{
    use RefreshDatabase;

    // Route /storage/{path} di routes/web.php melayani file public (foto/stiker)
    // sebagai fallback saat symlink public/storage tidak tersedia di hosting.
    // Framework punya route serupa (storage.local) untuk disk PRIVATE — route itu
    // menimpa punya kita (RouteCollection berkunci URI+method) dan butuh signed
    // URL → karena itu disk local di config/filesystems.php diset serve=false.
    private function putFile(string $relative, string $content): void
    {
        $dir = dirname(storage_path('app/public/' . $relative));
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents(storage_path('app/public/' . $relative), $content);
    }

    public function test_storage_route_melayani_file_public_yang_ada(): void
    {
        $this->putFile('photos/2026/01/cam_test.jpg', 'fake-jpeg-bytes');

        // response()->file() berbody streamed → assert lewat status + header.
        $this->get('/storage/photos/2026/01/cam_test.jpg')
            ->assertOk()
            ->assertHeader('Content-Length', (string) strlen('fake-jpeg-bytes'));
    }

    public function test_storage_route_404_untuk_file_yang_tidak_ada(): void
    {
        $this->get('/storage/photos/1999/01/tidak-ada.jpg')->assertNotFound();
    }

    public function test_storage_route_menolak_path_traversal(): void
    {
        file_put_contents(storage_path('app/public/rahasia.txt'), 'rahasia');

        $this->get('/storage/rahasia.txt/../../.env')
            ->assertNotFound();
    }
}
