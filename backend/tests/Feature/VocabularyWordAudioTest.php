<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\VocabularyLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VocabularyWordAudioTest extends TestCase
{
    use RefreshDatabase;

    private function teacher(): User
    {
        $role = Role::firstOrCreate(['name' => 'teacher'], ['description' => 'teacher']);

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_audio_upload_succeeds_and_returns_public_url(): void
    {
        Storage::fake('public');
        $teacher = $this->teacher();
        $level = VocabularyLevel::create(['title' => 'Audio words']);

        $response = $this->withToken($teacher->createToken('test')->plainTextToken)
            ->post('/api/vocabulary/levels/' . $level->id . '/words', [
                'word' => 'dog',
                'translation' => 'perro',
                'audio' => UploadedFile::fake()->create('dog.mp3', 100, 'audio/mpeg'),
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.word', 'dog')
            ->assertJsonPath('data.audio_url', fn ($url) => str_contains($url, '/storage/vocabulary/audio/'));

        Storage::disk('public')->assertExists($response->json('data.audio_path'));
    }

    public function test_invalid_audio_is_rejected(): void
    {
        Storage::fake('public');
        $teacher = $this->teacher();
        $level = VocabularyLevel::create(['title' => 'Invalid audio']);

        $this->withToken($teacher->createToken('test')->plainTextToken)
            ->withHeader('Accept', 'application/json')
            ->post('/api/vocabulary/levels/' . $level->id . '/words', [
                'word' => 'dog',
                'translation' => 'perro',
                'audio' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('audio');
    }

    public function test_replacing_audio_updates_path_and_deletes_previous_file(): void
    {
        Storage::fake('public');
        $teacher = $this->teacher();
        $level = VocabularyLevel::create(['title' => 'Replace audio']);
        $word = $level->words()->create([
            'word' => 'dog',
            'translation' => 'perro',
            'audio_path' => 'vocabulary/audio/old.mp3',
        ]);
        Storage::disk('public')->put($word->audio_path, 'old');

        $response = $this->withToken($teacher->createToken('test')->plainTextToken)
            ->post('/api/vocabulary/words/' . $word->id, [
                '_method' => 'PATCH',
                'word' => 'dog',
                'translation' => 'perro',
                'audio' => UploadedFile::fake()->create('new.mp3', 100, 'audio/mpeg'),
            ]);

        $response->assertOk();
        $newPath = $response->json('data.audio_path');
        $this->assertNotSame('vocabulary/audio/old.mp3', $newPath);
        Storage::disk('public')->assertMissing('vocabulary/audio/old.mp3');
        Storage::disk('public')->assertExists($newPath);
    }

    public function test_deleting_word_cleans_managed_audio(): void
    {
        Storage::fake('public');
        $teacher = $this->teacher();
        $level = VocabularyLevel::create(['title' => 'Delete audio']);
        $word = $level->words()->create([
            'word' => 'dog',
            'translation' => 'perro',
            'audio_path' => 'vocabulary/audio/delete.mp3',
        ]);
        Storage::disk('public')->put($word->audio_path, 'audio');

        $this->withToken($teacher->createToken('test')->plainTextToken)
            ->deleteJson('/api/vocabulary/words/' . $word->id)
            ->assertOk();

        Storage::disk('public')->assertMissing('vocabulary/audio/delete.mp3');
    }
}
