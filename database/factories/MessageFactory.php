<?php

namespace Database\Factories;

use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    private static array $kelasList = [
        'X PPLG 1', 'X PPLG 2', 'X AKL 1', 'X AKL 2',
        'X PM 1', 'X PM 2', 'X MPLB 1', 'X MPLB 2',
        'XI PPLG 1', 'XI PPLG 2', 'XI AKL 1', 'XI AKL 2',
        'XI PM 1', 'XI PM 2', 'XI MPLB 1', 'XI MPLB 2',
        'XII PPLG 1', 'XII PPLG 2', 'XII AKL 1', 'XII AKL 2',
        'XII PM 1', 'XII PM 2', 'XII MPLB 1', 'XII MPLB 2',
    ];

    private static array $spotifyTracks = [
        '4PTG3Z6ehG4W4Rr4r9XkX', '3bG2fLMEsQqRwYgKpQn9f', '7tF0x6QgL5XqC1dN6v2KJ',
        '2fXkWCVY7Vq7Ph6Q5f6L9M', '5sP9qFbK3aL5cF6v8JkL0N', '1qR6gK8wD3fH9jL0pO7iU',
        '8zF0gH5jK2lQ1wE4rT6yU9', '3mL5oN7pR9sT1vW4xY7zA', '6cF8gH0jK2lQ4wE6rT8yU',
        '9zB1nM3qW5eR7tY9uI0oP', '2xV4bN6mQ8wE0rT2yU4iO', '7pL9jK1dF3gH5jK7lQ9wE',
    ];

    public function definition(): array
    {
        return [
            'recipient_name' => $this->faker->firstName(),
            'kelas' => $this->faker->randomElement(self::$kelasList),
            'message' => $this->faker->realTextBetween(20, 120),
            'spotify_track_id' => $this->faker->randomElement(self::$spotifyTracks),
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'updated_at' => now(),
        ];
    }

    public function tanpaLagu(): static
    {
        return $this->state(fn (array $attributes) => [
            'spotify_track_id' => null,
        ]);
    }
}
