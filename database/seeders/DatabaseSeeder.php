<?php

namespace Database\Seeders;

use App\Models\Message;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    private array $kelasList = [
        'X PPLG 1', 'X PPLG 2', 'X AKL 1', 'X AKL 2',
        'X PM 1', 'X PM 2', 'X MPLB 1', 'X MPLB 2',
        'XI PPLG 1', 'XI PPLG 2', 'XI AKL 1', 'XI AKL 2',
        'XI PM 1', 'XI PM 2', 'XI MPLB 1', 'XI MPLB 2',
        'XII PPLG 1', 'XII PPLG 2', 'XII AKL 1', 'XII AKL 2',
        'XII PM 1', 'XII PM 2', 'XII MPLB 1', 'XII MPLB 2',
    ];

    private array $recipients = ['Alex', 'Bella', 'Charlie', 'Diana', 'Ethan', 'Fiona', 'George', 'Hannah', 'Ivan', 'Julia', 'Kevin', 'Lisa', 'Michael', 'Nina', 'Oliver', 'Putri', 'Rizky', 'Sari', 'Toni', 'Umi'];

    private array $messages = [
        'Aku suka sama kamu dari dulu, tapi gak pernah berani ngomong.',
        'Makasih ya udah jadi temen terbaik selama ini.',
        'Kelas kita seru banget, gak bakal lupain momen-momen ini.',
        'Semoga kamu suka lagu ini, mewakili perasaanku.',
        'Jangan pernah berubah ya, kamu sempurna apa adanya.',
        'Inget terus masa-masa kita di sekolah ini.',
        'Canda tawa kita bakal jadi kenangan terindah.',
        'Aku bangga jadi temen kamu.',bro kalau semsal 
        'Suatu hari nanti kita pasti kangen masa-masa ini.',
        'Makasih udah selalu ada buat aku.',
        'Kamu itu spesial, jangan pernah ragu sama dirimu sendiri.',
        'Semoga persahabatan kita langgeng sampai tua.',
        'Lagu ini cocok banget buat perasaan aku sekarang.',
        'Jangan lupain aku ya kalau udah lulus nanti.',
        'Kamu tau? Aku suka senyum kamu.',
        'Terima kasih untuk semua kenangan indahnya.',
        'Semoga kamu selalu bahagia di manapun berada.',
        'Aku akan selalu ingat kebaikan kamu.',
        'Kamu hebat! Jangan pernah menyerah.',
        'Hidup itu indah, jangan lupa tersenyum.',
        'Makasih atas semua motivasi yang kamu kasih.',
        'Suka duka kita lewatin bareng, makasih ya.',
        'Kamu itu bikin hariku jadi lebih baik.',
        'Semangat terus ya untuk masa depan.',
        'Aku percaya kamu bisa raih mimpi-mimpimu.',
    ];

    private array $spotifyTracks = [
        '4PTG3Z6ehG4W4Rr4r9XkX', '3bG2fLMEsQqRwYgKpQn9f', '7tF0x6QgL5XqC1dN6v2KJ',
        '2fXkWCVY7Vq7Ph6Q5f6L9M', '5sP9qFbK3aL5cF6v8JkL0N', '1qR6gK8wD3fH9jL0pO7iU',
        '8zF0gH5jK2lQ1wE4rT6yU9', '3mL5oN7pR9sT1vW4xY7zA', '6cF8gH0jK2lQ4wE6rT8yU',
        '9zB1nM3qW5eR7tY9uI0oP', null, null,
    ];

    public function run(): void
    {
        for ($i = 0; $i < 50; $i++) {
            Message::create([
                'recipient_name' => $this->recipients[array_rand($this->recipients)],
                'kelas' => $this->kelasList[array_rand($this->kelasList)],
                'message' => $this->messages[array_rand($this->messages)],
                'spotify_track_id' => $this->spotifyTracks[array_rand($this->spotifyTracks)],
                'created_at' => now()->subDays(rand(0, 30))->subHours(rand(0, 23)),
            ]);
        }
    }
}
