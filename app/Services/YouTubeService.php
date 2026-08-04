<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class YouTubeService
{
    private const API_URL = 'https://www.googleapis.com/youtube/v3';

    public function searchAudio(string $title, string $artist): ?array
    {
        $key = config('services.youtube.api_key');
        if (! $key) {
            return null;
        }

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
        $response = Http::get(self::API_URL . '/videos', [
            'part' => 'contentDetails',
            'id' => $videoId,
            'key' => config('services.youtube.api_key'),
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
