<?php

namespace Tests\Unit;

use App\Support\EmojiText;
use PHPUnit\Framework\TestCase;

class EmojiTextTest extends TestCase
{
    public function test_emoji_single_dibungkus_span_kecil(): void
    {
        $out = EmojiText::small('halo 😀 semua');

        $this->assertStringContainsString('halo ', $out);
        $this->assertStringContainsString('<span style="font-size:.65em">😀</span>', $out);
        $this->assertStringContainsString(' semua', $out);
    }

    public function test_cluster_zwj_keluarga_tetap_satu_span(): void
    {
        $family = "👨\u{200D}👩\u{200D}👧";
        $out = EmojiText::small("keluarga $family");

        $this->assertSame(1, substr_count($out, 'font-size:.65em'));
        $this->assertStringContainsString('<span style="font-size:.65em">' . $family . '</span>', $out);
    }

    public function test_skin_tone_ikut_dalam_span(): void
    {
        $out = EmojiText::small('oke 👍🏽');

        $this->assertStringContainsString('<span style="font-size:.65em">👍🏽</span>', $out);
    }

    public function test_html_diekscape_tidak_bisa_xss(): void
    {
        $out = EmojiText::small('<script>alert(1)</script>');

        $this->assertStringNotContainsString('<script>', $out);
        $this->assertStringContainsString('&lt;script&gt;', $out);
    }

    public function test_teks_tanpa_emoji_tidak_berubah(): void
    {
        $out = EmojiText::small('pesan biasa aja');

        $this->assertSame('pesan biasa aja', $out);
    }
}