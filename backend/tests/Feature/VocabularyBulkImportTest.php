<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\VocabularyLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class VocabularyBulkImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['admin', 'teacher', 'student'] as $role) {
            Role::firstOrCreate(['name' => $role], ['description' => $role]);
        }
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role_id' => Role::where('name', $role)->value('id')]);
    }

    private function csv(string $contents): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('vocabulary.csv', $contents);
    }

    private function import(User $user, VocabularyLevel $level, ?UploadedFile $file = null)
    {
        $payload = $file ? ['file' => $file] : [];

        return $this->withToken($user->createToken('test')->plainTextToken)
            ->withHeader('Accept', 'application/json')
            ->post('/api/vocabulary/levels/' . $level->id . '/import', $payload);
    }

    public function test_teacher_can_import_valid_csv_without_audio_fields(): void
    {
        $level = VocabularyLevel::create(['title' => 'Animals']);

        $response = $this->import($this->user('teacher'), $level, $this->csv(
            "word,translation,example,notes\ndog,perro,The dog is friendly,Common pet\ncat,gato,,"
        ));

        $response->assertOk()
            ->assertJsonPath('data.imported', 2)
            ->assertJsonPath('data.skipped', 0)
            ->assertJsonPath('data.failed', 0);
        $this->assertDatabaseHas('vocabulary_words', ['vocabulary_level_id' => $level->id, 'word' => 'dog']);
        $this->assertDatabaseHas('vocabulary_words', ['vocabulary_level_id' => $level->id, 'word' => 'cat']);
    }

    public function test_admin_can_import_valid_csv(): void
    {
        $level = VocabularyLevel::create(['title' => 'Food']);

        $this->import($this->user('admin'), $level, $this->csv(
            "word,translation\nbread,pan"
        ))->assertOk()->assertJsonPath('data.imported', 1);
    }

    public function test_utf8_bom_and_foreign_characters_are_preserved(): void
    {
        $level = VocabularyLevel::create(['title' => 'Unicode']);
        $csv = "\xEF\xBB\xBFword,translation,example,notes\n"
            . "España,Spain,¿Qué tal?,mañana\n"
            . "niño,child,café,frère\n"
            . "schön,beautiful,Straße,";

        $this->import($this->user('teacher'), $level, $this->csv($csv))
            ->assertOk()
            ->assertJsonPath('data.imported', 3);

        $this->assertDatabaseHas('vocabulary_words', [
            'vocabulary_level_id' => $level->id,
            'word' => 'España',
            'translation' => 'Spain',
            'example' => '¿Qué tal?',
            'notes' => 'mañana',
        ]);
        $this->assertDatabaseHas('vocabulary_words', ['word' => 'niño', 'example' => 'café', 'notes' => 'frère']);
        $this->assertDatabaseHas('vocabulary_words', ['word' => 'schön', 'example' => 'Straße']);

        $this->withToken($this->user('student')->createToken('test')->plainTextToken)
            ->getJson('/api/vocabulary/levels/'.$level->id)
            ->assertOk()
            ->assertJsonFragment(['word' => 'España', 'example' => '¿Qué tal?', 'notes' => 'mañana']);
    }

    public function test_student_cannot_import(): void
    {
        $level = VocabularyLevel::create(['title' => 'Travel']);

        $this->import($this->user('student'), $level, $this->csv(
            "word,translation\ntrain,tren"
        ))->assertForbidden();
    }

    public function test_file_is_required(): void
    {
        $level = VocabularyLevel::create(['title' => 'Required file']);

        $this->import($this->user('teacher'), $level)
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');
    }

    public function test_invalid_rows_are_reported_while_valid_rows_are_imported(): void
    {
        $level = VocabularyLevel::create(['title' => 'Partial import']);

        $response = $this->import($this->user('teacher'), $level, $this->csv(
            "word,translation,example,notes\ndog,perro,,\ncat,,,Missing translation\n,bird,,Missing word"
        ));

        $response->assertOk()
            ->assertJsonPath('data.imported', 1)
            ->assertJsonPath('data.failed', 2)
            ->assertJsonPath('data.errors.0.row', 3)
            ->assertJsonPath('data.errors.1.row', 4);
        $this->assertDatabaseHas('vocabulary_words', ['vocabulary_level_id' => $level->id, 'word' => 'dog']);
    }

    public function test_existing_and_in_file_duplicates_are_skipped_case_insensitively(): void
    {
        $level = VocabularyLevel::create(['title' => 'Duplicates']);
        $level->words()->create(['word' => 'Dog', 'translation' => 'perro']);

        $response = $this->import($this->user('teacher'), $level, $this->csv(
            "word,translation\n dog ,canino\ncat,gato\nCAT,felino"
        ));

        $response->assertOk()
            ->assertJsonPath('data.imported', 1)
            ->assertJsonPath('data.skipped', 2)
            ->assertJsonPath('data.failed', 0);
        $this->assertSame(2, $level->words()->count());
    }

    public function test_empty_or_malformed_csv_is_rejected_cleanly(): void
    {
        $level = VocabularyLevel::create(['title' => 'Malformed']);
        $teacher = $this->user('teacher');

        $this->import($teacher, $level, $this->csv(''))
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');

        $this->import($teacher, $level, $this->csv("term,meaning\ndog,perro"))
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');
    }
}
