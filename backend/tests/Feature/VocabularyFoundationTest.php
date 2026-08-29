<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\VocabularyLevel;
use App\Models\VocabularyWord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VocabularyFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'teacher', 'student'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName], ['description' => $roleName]);
        }
    }

    private function makeUser(string $roleName, string $email): User
    {
        $role = Role::where('name', $roleName)->firstOrFail();

        return User::factory()->create([
            'email' => $email,
            'role_id' => $role->id,
        ]);
    }

    public function test_student_can_list_vocabulary_levels(): void
    {
        $student = $this->makeUser('student', 'student-list@example.com');
        $level = VocabularyLevel::create([
            'title' => 'Pets',
            'description' => 'Animals at home',
            'difficulty' => 'beginner',
        ]);
        $level->words()->create([
            'word' => 'dog',
            'translation' => 'perro',
        ]);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->getJson('/api/vocabulary/levels');

        $response->assertOk()
            ->assertJsonPath('data.0.title', 'Pets')
            ->assertJsonPath('data.0.word_count', 1);
    }

    public function test_student_can_view_level_and_words(): void
    {
        $student = $this->makeUser('student', 'student-view@example.com');
        $level = VocabularyLevel::create(['title' => 'Pets', 'difficulty' => 'beginner']);
        $level->words()->create([
            'word' => 'cat',
            'translation' => 'gato',
        ]);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->getJson('/api/vocabulary/levels/' . $level->id);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Pets')
            ->assertJsonPath('data.words.0.word', 'cat');
    }

    public function test_student_cannot_create_level(): void
    {
        $student = $this->makeUser('student', 'student-no-create@example.com');

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/vocabulary/levels', [
                'title' => 'Travel',
            ]);

        $response->assertStatus(403);
    }

    public function test_student_cannot_update_level(): void
    {
        $student = $this->makeUser('student', 'student-no-update@example.com');
        $level = VocabularyLevel::create(['title' => 'Pets']);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->patchJson('/api/vocabulary/levels/' . $level->id, [
                'title' => 'Animals',
            ]);

        $response->assertStatus(403);
    }

    public function test_student_cannot_delete_level(): void
    {
        $student = $this->makeUser('student', 'student-no-delete@example.com');
        $level = VocabularyLevel::create(['title' => 'Food']);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->deleteJson('/api/vocabulary/levels/' . $level->id);

        $response->assertStatus(403);
    }

    public function test_student_cannot_create_word(): void
    {
        $student = $this->makeUser('student', 'student-no-word-create@example.com');
        $level = VocabularyLevel::create(['title' => 'Pets']);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/vocabulary/levels/' . $level->id . '/words', [
                'word' => 'bird',
                'translation' => 'pájaro',
            ]);

        $response->assertStatus(403);
    }

    public function test_teacher_can_create_level(): void
    {
        $teacher = $this->makeUser('teacher', 'teacher-create-level@example.com');

        $response = $this->withToken($teacher->createToken('test')->plainTextToken)
            ->postJson('/api/vocabulary/levels', [
                'title' => 'Travel',
                'description' => 'Words for trips',
                'difficulty' => 'beginner',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Travel');
    }

    public function test_teacher_can_update_level(): void
    {
        $teacher = $this->makeUser('teacher', 'teacher-update-level@example.com');
        $level = VocabularyLevel::create(['title' => 'Pets']);

        $response = $this->withToken($teacher->createToken('test')->plainTextToken)
            ->patchJson('/api/vocabulary/levels/' . $level->id, [
                'title' => 'Animals',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Animals');
    }

    public function test_teacher_can_delete_level(): void
    {
        $teacher = $this->makeUser('teacher', 'teacher-delete-level@example.com');
        $level = VocabularyLevel::create(['title' => 'Food']);
        $level->words()->create(['word' => 'apple', 'translation' => 'manzana']);

        $response = $this->withToken($teacher->createToken('test')->plainTextToken)
            ->deleteJson('/api/vocabulary/levels/' . $level->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('vocabulary_levels', ['id' => $level->id]);
    }

    public function test_teacher_can_add_word(): void
    {
        $teacher = $this->makeUser('teacher', 'teacher-add-word@example.com');
        $level = VocabularyLevel::create(['title' => 'Pets']);

        $response = $this->withToken($teacher->createToken('test')->plainTextToken)
            ->postJson('/api/vocabulary/levels/' . $level->id . '/words', [
                'word' => 'bird',
                'translation' => 'pájaro',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.word', 'bird');
    }

    public function test_teacher_can_update_word(): void
    {
        $teacher = $this->makeUser('teacher', 'teacher-update-word@example.com');
        $level = VocabularyLevel::create(['title' => 'Pets']);
        $word = $level->words()->create(['word' => 'horse', 'translation' => 'caballo']);

        $response = $this->withToken($teacher->createToken('test')->plainTextToken)
            ->patchJson('/api/vocabulary/words/' . $word->id, [
                'word' => 'pony',
                'translation' => 'pony',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.word', 'pony');
    }

    public function test_teacher_can_delete_word(): void
    {
        $teacher = $this->makeUser('teacher', 'teacher-delete-word@example.com');
        $level = VocabularyLevel::create(['title' => 'Pets']);
        $word = $level->words()->create(['word' => 'fish', 'translation' => 'pez']);

        $response = $this->withToken($teacher->createToken('test')->plainTextToken)
            ->deleteJson('/api/vocabulary/words/' . $word->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('vocabulary_words', ['id' => $word->id]);
    }

    public function test_admin_can_manage_vocabulary(): void
    {
        $admin = $this->makeUser('admin', 'admin-vocab@example.com');

        $create = $this->withToken($admin->createToken('test')->plainTextToken)
            ->postJson('/api/vocabulary/levels', ['title' => 'Food']);

        $create->assertStatus(201);

        $level = VocabularyLevel::first();

        $addWord = $this->withToken($admin->createToken('test')->plainTextToken)
            ->postJson('/api/vocabulary/levels/' . $level->id . '/words', [
                'word' => 'bread',
                'translation' => 'pan',
            ]);

        $addWord->assertStatus(201);
    }

    public function test_duplicate_level_title_fails(): void
    {
        $teacher = $this->makeUser('teacher', 'teacher-duplicate-level@example.com');
        VocabularyLevel::create(['title' => 'Pets']);

        $response = $this->withToken($teacher->createToken('test')->plainTextToken)
            ->postJson('/api/vocabulary/levels', [
                'title' => 'Pets',
            ]);

        $response->assertStatus(422);
    }

    public function test_word_requires_word_and_translation(): void
    {
        $teacher = $this->makeUser('teacher', 'teacher-word-validation@example.com');
        $level = VocabularyLevel::create(['title' => 'Pets']);

        $response = $this->withToken($teacher->createToken('test')->plainTextToken)
            ->postJson('/api/vocabulary/levels/' . $level->id . '/words', [
                'word' => '',
            ]);

        $response->assertStatus(422);
    }

    public function test_deleting_vocabulary_level_removes_its_words(): void
    {
        $teacher = $this->makeUser('teacher', 'teacher-delete-level-cascade@example.com');
        $level = VocabularyLevel::create(['title' => 'Pets']);
        $word = $level->words()->create(['word' => 'fish', 'translation' => 'pez']);

        $this->withToken($teacher->createToken('test')->plainTextToken)
            ->deleteJson('/api/vocabulary/levels/' . $level->id)
            ->assertStatus(200);

        $this->assertDatabaseMissing('vocabulary_levels', ['id' => $level->id]);
        $this->assertDatabaseMissing('vocabulary_words', ['id' => $word->id]);
    }
}
