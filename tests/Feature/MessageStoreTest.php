<?php

namespace Tests\Feature;

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
}
