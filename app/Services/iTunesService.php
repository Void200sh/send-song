<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class iTunesService
{
    private const API_URL = 'https://itunes.apple.com/search';

    public function searchTracks(string $query): array
    {
        $cacheKey = 'itunes_search_' . md5($query);

        return Cache::remember($cacheKey, 3600, function () use ($query) {
            $response = Http::get(self::API_URL, [
                'term' => $query,
                'media' => 'music',
                'entity' => 'song',
                'limit' => 8,
            ]);

            if ($response->failed()) {
                return [];
            }

            return collect($response->json('results') ?? [])
                ->map(fn (array $item) => [
                    'spotify_id' => null,
                    'title' => $item['trackName'] ?? '',
                    'artist' => $item['artistName'] ?? '',
                    'cover_url' => $this->biggerArtwork($item['artworkUrl100'] ?? null),
                    'duration_ms' => $item['trackTimeMillis'] ?? 0,
                ])
                ->values()
                ->all();
        });
    }

    private function biggerArtwork(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        return str_replace('100x100bb', '300x300bb', $url);
    }
}
