<?php

namespace Database\Seeders;

use App\Models\VocabularyLevel;
use App\Models\User;
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
                'stage' => 'S1',
                'created_by_user_id' => User::whereHas('role', fn ($query) => $query->where('name', 'admin'))->value('id'),
                'visibility' => 'private',
            ]
        );

        if ($level->stage === null) {
            $level->update(['stage' => 'S1']);
        }

        if ($level->created_by_user_id === null) {
            $ownerId = User::whereHas('role', fn ($query) => $query->where('name', 'admin'))->value('id');
            if ($ownerId) {
                $level->update(['created_by_user_id' => $ownerId]);
            }
        }

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
