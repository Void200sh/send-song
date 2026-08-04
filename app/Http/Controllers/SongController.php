<?php

namespace App\Http\Controllers;

use App\Services\iTunesService;
use App\Services\YouTubeService;
use Illuminate\Http\Request;

class SongController extends Controller
{
    public function __construct(
        private iTunesService $itunes,
        private YouTubeService $youtube,
    ) {}

    public function search(Request $request)
    {
        $query = trim((string) $request->query('q'));

        if (mb_strlen($query) < 2) {
            return response()->json(['tracks' => []]);
        }

        return response()->json(['tracks' => $this->itunes->searchTracks($query)]);
    }

    public function resolve(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'artist' => 'required|string|max:255',
        ]);

        $match = $this->youtube->searchAudio($validated['title'], $validated['artist']);

        return response()->json([
            'youtube_id' => $match['youtube_id'] ?? null,
            'youtube_title' => $match['title'] ?? null,
        ]);
    }
}
