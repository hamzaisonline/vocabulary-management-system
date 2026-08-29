<?php

namespace Database\Seeders;

use App\Models\VocabularyLevel;
use Illuminate\Database\Seeder;

class VocabularySeeder extends Seeder
{
    public function run(): void
    {
        $level = VocabularyLevel::firstOrCreate(
            ['title' => 'Pets'],
            [
                'description' => 'Common household pet vocabulary.',
                'difficulty' => 'beginner',
            ]
        );

        $words = [
            [
                'word' => 'dog',
                'translation' => 'perro',
                'example' => 'The dog is barking.',
                'notes' => 'A common pet animal.',
            ],
            [
                'word' => 'cat',
                'translation' => 'gato',
                'example' => 'The cat sleeps on the sofa.',
                'notes' => 'A common household pet.',
            ],
        ];

        foreach ($words as $word) {
            $level->words()->firstOrCreate(
                ['word' => $word['word']],
                [
                    'translation' => $word['translation'],
                    'example' => $word['example'],
                    'notes' => $word['notes'],
                ]
            );
        }
    }
}
