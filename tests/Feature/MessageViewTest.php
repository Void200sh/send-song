<?php

namespace Tests\Feature;

use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageViewTest extends TestCase
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

    public function test_card_shown_in_feed_counts_as_view(): void
    {
        $msg = $this->createMessage();

        // Buka feed → kartu tampil → views bertambah (total + unik)
        $this->get(route('messages.index'))->assertOk();

        $this->assertDatabaseHas('messages', [
            'id' => $msg->id,
            'views' => 1,
            'unique_views' => 1,
        ]);
        $this->assertDatabaseHas('message_views', [
            'message_id' => $msg->id,
            'ip_address' => '127.0.0.1',
        ]);
    }

    public function test_opening_detail_page_counts_as_view(): void
    {
        $msg = $this->createMessage();

        $this->get(route('messages.show', $msg))->assertOk();

        $this->assertDatabaseHas('messages', [
            'id' => $msg->id,
            'views' => 1,
            'unique_views' => 1,
        ]);
    }

    public function test_same_ip_counts_once_as_unique_but_total_keeps_growing(): void
    {
        $msg = $this->createMessage();

        // 3× dibuka dari IP yang sama (pakai X-Forwarded-For biar konsisten)
        foreach (range(1, 3) as $i) {
            $this->get(route('messages.show', $msg), [
                'X-Forwarded-For' => '203.0.113.7, 10.0.0.1',
            ])->assertOk();
        }

        // Total views 3, tapi pengunjung unik cuma 1
        $this->assertDatabaseHas('messages', [
            'id' => $msg->id,
            'views' => 3,
            'unique_views' => 1,
        ]);
    }

    public function test_different_ips_count_as_unique_views(): void
    {
        $msg = $this->createMessage();

        $this->get(route('messages.show', $msg), [
            'X-Forwarded-For' => '203.0.113.7, 10.0.0.1',
        ])->assertOk();

        $this->get(route('messages.show', $msg), [
            'X-Forwarded-For' => '198.51.100.9, 10.0.0.1',
        ])->assertOk();

        $this->assertDatabaseHas('messages', [
            'id' => $msg->id,
            'views' => 2,
            'unique_views' => 2,
        ]);
    }

    public function test_detail_page_shows_view_and_unique_view_count(): void
    {
        $msg = $this->createMessage();

        $this->get(route('messages.show', $msg))
            ->assertOk()
            ->assertSee('pengunjung')
            ->assertSee((string) $msg->id);
    }
}
