<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class YouTubeService
{
    private const API_URL = 'https://www.googleapis.com/youtube/v3';

    /**
     * ─── CARI AUDIO YOUTUBE ───
     * Prioritas:
     *  1. YouTube Data API v3 (butuh API key — kalau key valid)
     *  2. Fallback scrape halaman hasil pencarian youtube.com (tanpa API key)
     * Jadi lagu tetap bisa di-resolve walaupun API key belum diisi / tidak valid.
     */
    public function searchAudio(string $title, string $artist): ?array
    {
        if ($key = config('services.youtube.api_key')) {
            $match = $this->searchViaApi($key, $title, $artist);
            if ($match) {
                return $match;
            }
        }

        return $this->searchViaScrape($title, $artist);
    }

    /**
     * ─── PATH 1: API v3 ───
     * Coba 2 variasi query, ambil hasil yang paling mirip.
     * Return null kalau API gagal / hasil kosong (biar jatuh ke fallback).
     */
    private function searchViaApi(string $key, string $title, string $artist): ?array
    {
        $queries = [
            "\"{$artist}\" {$title} official audio",
            "{$title} {$artist} audio",
        ];

        foreach ($queries as $query) {
            $response = Http::get(self::API_URL . '/search', [
                'part' => 'snippet',
                'q' => $query,
                'type' => 'video',
                'videoCategoryId' => 10,
                'maxResults' => 5,
                'key' => $key,
            ]);

            if ($response->failed()) {
                return null;
            }

            if ($match = $this->pickBest($response->json('items') ?? [], $artist, $title)) {
                return $match;
            }
        }

        return null;
    }

    /**
     * ─── PATH 2: SCRAPE youtube.com/results (tanpa API key) ───
     * Parsing JSON `ytInitialData` yang tertanam di HTML halaman hasil pencarian.
     * Rapi-rapi saja: kalau gagal di query pertama, lanjut query kedua.
     */
    private function searchViaScrape(string $title, string $artist): ?array
    {
        $queries = [
            "\"{$artist}\" {$title} official audio",
            "{$title} {$artist} audio",
        ];

        foreach ($queries as $query) {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36',
                'Accept-Language' => 'en-US,en;q=0.9',
            ])->get('https://www.youtube.com/results', ['search_query' => $query]);

            if ($response->failed()) {
                continue;
            }

            $items = $this->extractVideoItemsFromHtml($response->body());
            if ($items && ($match = $this->pickBest($items, $artist, $title))) {
                return $match;
            }
        }

        return null;
    }

    /**
     * ─── EKSTRAK VIDEO DARI HTML HASIL PENCARIAN ───
     * Ambil blok `ytInitialData = {...};` lalu telusuri struktur renderer YouTube.
     * Normalisasi hasil ke bentuk yang sama dengan response API v3
     * (id.videoId, snippet.title, snippet.channelTitle) biar bisa dipakai ulang di pickBest().
     */
    private function extractVideoItemsFromHtml(string $html): array
    {
        preg_match('/ytInitialData\s*=\s*({.+?});<\/script>/s', $html, $matches);

        if (! isset($matches[1])) {
            return [];
        }

        $data = json_decode($matches[1], true);
        if (! is_array($data)) {
            return [];
        }

        $sections = $data['contents']['twoColumnSearchResultsRenderer']['primaryContents']['sectionListRenderer']['contents'] ?? [];

        $items = [];
        foreach ($sections as $section) {
            $contents = $section['itemSectionRenderer']['contents'] ?? [];
            foreach ($contents as $content) {
                $video = $content['videoRenderer'] ?? null;
                if (! $video || empty($video['videoId'])) {
                    continue;
                }

                $items[] = [
                    'id' => ['videoId' => $video['videoId']],
                    'snippet' => [
                        'title' => $video['title']['runs'][0]['text']
                            ?? $video['title']['simpleText']
                            ?? '',
                        'channelTitle' => $video['ownerText']['runs'][0]['text'] ?? '',
                    ],
                ];
            }
        }

        return $items;
    }

    private function pickBest(array $items, string $artist, string $title): ?array
    {
        $artist = strtolower($artist);
        $title = strtolower($title);

        $candidates = array_map(function (array $item) use ($artist, $title) {
            $videoTitle = strtolower($item['snippet']['title'] ?? '');
            $channel = strtolower($item['snippet']['channelTitle'] ?? '');
            $score = 0;

            if ($artist !== '' && str_contains($videoTitle, $artist)) {
                $score += 3;
            }
            if ($title !== '' && str_contains($videoTitle, $title)) {
                $score += 2;
            }
            if (preg_match('/official\s+(audio|video|lyric)/', $videoTitle)) {
                $score += 2;
            }
            if ($artist !== '' && str_contains($channel, $artist)) {
                $score += 1;
            }
            if (preg_match('/(remix|reaction|cover|karaoke|instrumental|live\s+set)/', $videoTitle)) {
                $score -= 5;
            }

            return [
                'youtube_id' => $item['id']['videoId'] ?? null,
                'title' => $item['snippet']['title'] ?? null,
                'score' => $score,
            ];
        }, $items);

        usort($candidates, fn ($a, $b) => $b['score'] <=> $a['score']);

        foreach ($candidates as $candidate) {
            if ($candidate['youtube_id'] && $this->isReasonableDuration($candidate['youtube_id'])) {
                return $candidate;
            }
        }

        return $candidates[0]['youtube_id'] ? $candidates[0] : null;
    }

    private function isReasonableDuration(string $videoId): bool
    {
        $key = config('services.youtube.api_key');
        if (! $key) {
            return true;
        }

        $response = Http::get(self::API_URL . '/videos', [
            'part' => 'contentDetails',
            'id' => $videoId,
            'key' => $key,
        ]);

        $duration = $response->json('items.0.contentDetails.duration');

        return $duration !== null && $this->parseIsoDuration($duration) <= 900;
    }

    private function parseIsoDuration(string $iso): int
    {
        preg_match_all('/(\d+)([HMS])/', $iso, $m);
        $seconds = 0;

        foreach ($m[1] as $i => $value) {
            $seconds += match ($m[2][$i]) {
                'H' => (int) $value * 3600,
                'M' => (int) $value * 60,
                'S' => (int) $value,
                default => 0,
            };
        }

        return $seconds;
    }
}
