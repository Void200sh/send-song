<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SpotifyService
{
    private const TOKEN_URL = 'https://accounts.spotify.com/api/token';
    private const API_URL = 'https://api.spotify.com/v1';

    public function searchTracks(string $query): array
    {
        $token = $this->getAccessToken();
        if (! $token) {
            return [];
        }

        $response = Http::withToken($token)
            ->get(self::API_URL . '/search', [
                'q' => $query,
                'type' => 'track',
                'limit' => 8,
            ]);

        if ($response->failed()) {
            return [];
        }

        return collect($response->json('tracks.items'))
            ->map(fn (array $item) => [
                'spotify_id' => $item['id'],
                'title' => $item['name'],
                'artist' => collect($item['artists'])->pluck('name')->join(', '),
                'cover_url' => $item['album']['images'][1]['url']
                    ?? $item['album']['images'][0]['url']
                    ?? null,
                'duration_ms' => $item['duration_ms'],
            ])
            ->values()
            ->all();
    }

    private function getAccessToken(): ?string
    {
        return Cache::remember('spotify_access_token', 3500, function () {
            $response = Http::asForm()->withBasicAuth(
                config('services.spotify.client_id'),
                config('services.spotify.client_secret'),
            )->post(self::TOKEN_URL, [
                'grant_type' => 'client_credentials',
            ]);

            return $response->successful() ? $response->json('access_token') : null;
        });
    }
}
