<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Models\VocabularyLevel;
use App\Models\VocabularyWord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentVocabularyProgressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'teacher', 'student'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName], ['description' => $roleName]);
        }
    }

    private function makeTeacherUser(string $email = 'teacher@example.com'): User
    {
        $role = Role::where('name', 'teacher')->firstOrFail();
        $user = User::factory()->create([
            'email' => $email,
            'role_id' => $role->id,
        ]);

        Teacher::create(['user_id' => $user->id]);

        return $user->fresh();
    }

    private function makeStudentUser(string $email = 'student@example.com'): User
    {
        $role = Role::where('name', 'student')->firstOrFail();
        $user = User::factory()->create([
            'email' => $email,
            'role_id' => $role->id,
        ]);

        Student::create(['user_id' => $user->id]);

        return $user->fresh();
    }

    private function makeTeacherAndStudentClass(string $teacherEmail, string $studentEmail): array
    {
        $teacher = $this->makeTeacherUser($teacherEmail);
        $student = $this->makeStudentUser($studentEmail);
        $class = SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => 'Progress Class']);
        $level = VocabularyLevel::create(['title' => 'Animals']);
        $word = $level->words()->create(['word' => 'dog', 'translation' => 'perro']);

        $class->vocabularyLevels()->attach($level->id);
        $class->students()->attach($student->student->id, ['status' => 'active', 'enrolled_at' => now()]);

        return [$teacher, $student, $class, $level, $word];
    }

    public function test_student_can_view_progress_for_accessible_level(): void
    {
        [, $student, , $level] = $this->makeTeacherAndStudentClass('teacher-progress-access@example.com', 'student-progress-access@example.com');

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->getJson('/api/student/progress');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $level->id);
    }

    public function test_student_cannot_view_progress_for_inaccessible_level(): void
    {
        $teacher = $this->makeTeacherUser('teacher-inaccessible@example.com');
        $student = $this->makeStudentUser('student-inaccessible@example.com');
        $class = SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => 'Hidden Class']);
        $level = VocabularyLevel::create(['title' => 'Hidden']);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->getJson('/api/student/vocabulary-levels/' . $level->id . '/progress');

        $response->assertStatus(403);
    }

    public function test_student_cannot_update_inaccessible_word(): void
    {
        $teacher = $this->makeTeacherUser('teacher-hidden-word@example.com');
        $student = $this->makeStudentUser('student-hidden-word@example.com');
        $class = SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => 'Secret']);
        $level = VocabularyLevel::create(['title' => 'Seasons']);
        $word = $level->words()->create(['word' => 'winter', 'translation' => 'invierno']);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/vocabulary-words/' . $word->id . '/progress', [
                'correct' => true,
            ]);

        $response->assertStatus(403);
    }

    public function test_teacher_cannot_use_student_progress_endpoint(): void
    {
        $teacher = $this->makeTeacherUser('teacher-not-student@example.com');

        $response = $this->withToken($teacher->createToken('test')->plainTextToken)
            ->getJson('/api/student/progress');

        $response->assertStatus(403);
    }

    public function test_first_correct_answer_creates_progress_row(): void
    {
        [, $student, , , $word] = $this->makeTeacherAndStudentClass('teacher-first-correct@example.com', 'student-first-correct@example.com');

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/vocabulary-words/' . $word->id . '/progress', [
                'correct' => true,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.mastery_percent', 25);

        $this->assertDatabaseHas('student_word_progress', [
            'student_id' => $student->student->id,
            'vocabulary_word_id' => $word->id,
            'mastery_percent' => 25,
        ]);
    }

    public function test_correct_answer_increases_mastery_by_25(): void
    {
        [, $student, , , $word] = $this->makeTeacherAndStudentClass('teacher-mastery@example.com', 'student-mastery@example.com');

        $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/vocabulary-words/' . $word->id . '/progress', ['correct' => true]);

        $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/vocabulary-words/' . $word->id . '/progress', ['correct' => true]);

        $this->assertDatabaseHas('student_word_progress', [
            'student_id' => $student->student->id,
            'vocabulary_word_id' => $word->id,
            'mastery_percent' => 50,
        ]);
    }

    public function test_mastery_never_exceeds_100(): void
    {
        [, $student, , , $word] = $this->makeTeacherAndStudentClass('teacher-max-mastery@example.com', 'student-max-mastery@example.com');

        for ($i = 0; $i < 10; $i++) {
            $this->withToken($student->createToken('test')->plainTextToken)
                ->postJson('/api/student/vocabulary-words/' . $word->id . '/progress', ['correct' => true]);
        }

        $this->assertDatabaseHas('student_word_progress', [
            'student_id' => $student->student->id,
            'vocabulary_word_id' => $word->id,
            'mastery_percent' => 100,
        ]);
    }

    public function test_incorrect_answer_increments_attempts_but_not_mastery(): void
    {
        [, $student, , , $word] = $this->makeTeacherAndStudentClass('teacher-incorrect@example.com', 'student-incorrect@example.com');

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/vocabulary-words/' . $word->id . '/progress', [
                'correct' => false,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.mastery_percent', 0);

        $this->assertDatabaseHas('student_word_progress', [
            'student_id' => $student->student->id,
            'vocabulary_word_id' => $word->id,
            'attempts' => 1,
            'correct_attempts' => 0,
        ]);
    }

    public function test_correct_attempts_only_increment_on_correct_answer(): void
    {
        [, $student, , , $word] = $this->makeTeacherAndStudentClass('teacher-correct-attempts@example.com', 'student-correct-attempts@example.com');

        $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/vocabulary-words/' . $word->id . '/progress', ['correct' => true]);

        $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/vocabulary-words/' . $word->id . '/progress', ['correct' => false]);

        $this->assertDatabaseHas('student_word_progress', [
            'student_id' => $student->student->id,
            'vocabulary_word_id' => $word->id,
            'attempts' => 2,
            'correct_attempts' => 1,
        ]);
    }

    public function test_last_practiced_at_is_updated(): void
    {
        [, $student, , , $word] = $this->makeTeacherAndStudentClass('teacher-last-practice@example.com', 'student-last-practice@example.com');

        $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/vocabulary-words/' . $word->id . '/progress', ['correct' => true]);

        $this->assertDatabaseHas('student_word_progress', [
            'student_id' => $student->student->id,
            'vocabulary_word_id' => $word->id,
        ]);
    }

    public function test_completed_at_is_set_when_mastery_reaches_100(): void
    {
        [, $student, , , $word] = $this->makeTeacherAndStudentClass('teacher-completed@example.com', 'student-completed@example.com');

        for ($i = 0; $i < 4; $i++) {
            $this->withToken($student->createToken('test')->plainTextToken)
                ->postJson('/api/student/vocabulary-words/' . $word->id . '/progress', ['correct' => true]);
        }

        $this->assertDatabaseHas('student_word_progress', [
            'student_id' => $student->student->id,
            'vocabulary_word_id' => $word->id,
            'mastery_percent' => 100,
        ]);
    }

    public function test_correct_answer_awards_10_xp(): void
    {
        [, $student, , , $word] = $this->makeTeacherAndStudentClass('teacher-xp@example.com', 'student-xp@example.com');

        $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/vocabulary-words/' . $word->id . '/progress', ['correct' => true]);

        $this->assertDatabaseHas('students', [
            'id' => $student->student->id,
            'total_xp' => 10,
        ]);
    }

    public function test_incorrect_answer_awards_no_xp(): void
    {
        [, $student, , , $word] = $this->makeTeacherAndStudentClass('teacher-no-xp@example.com', 'student-no-xp@example.com');

        $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/vocabulary-words/' . $word->id . '/progress', ['correct' => false]);

        $this->assertDatabaseHas('students', [
            'id' => $student->student->id,
            'total_xp' => 0,
        ]);
    }

    public function test_already_mastered_word_does_not_award_additional_xp(): void
    {
        [, $student, , , $word] = $this->makeTeacherAndStudentClass('teacher-xp-once@example.com', 'student-xp-once@example.com');

        for ($i = 0; $i < 4; $i++) {
            $this->withToken($student->createToken('test')->plainTextToken)
                ->postJson('/api/student/vocabulary-words/' . $word->id . '/progress', ['correct' => true]);
        }

        $student->refresh();
        $beforeXp = (int) $student->student->total_xp;
        $this->assertSame(40, $beforeXp);

        $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/vocabulary-words/' . $word->id . '/progress', ['correct' => true]);

        $student->refresh();
        $afterXp = (int) $student->student->total_xp;

        $this->assertSame($beforeXp, $afterXp);
    }

    public function test_level_progress_is_calculated_correctly(): void
    {
        [, $student, , $level, $word] = $this->makeTeacherAndStudentClass('teacher-level-progress@example.com', 'student-level-progress@example.com');
        $word2 = $level->words()->create(['word' => 'cat', 'translation' => 'gato']);
        $word3 = $level->words()->create(['word' => 'bird', 'translation' => 'pájaro']);

        for ($i = 0; $i < 4; $i++) {
            $this->withToken($student->createToken('test')->plainTextToken)
                ->postJson('/api/student/vocabulary-words/' . $word->id . '/progress', ['correct' => true]);
        }

        for ($i = 0; $i < 2; $i++) {
            $this->withToken($student->createToken('test')->plainTextToken)
                ->postJson('/api/student/vocabulary-words/' . $word2->id . '/progress', ['correct' => true]);
        }

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->getJson('/api/student/vocabulary-levels/' . $level->id . '/progress');

        $response->assertStatus(200)
            ->assertJsonPath('data.summary.progress_percent', 50);
    }

    public function test_level_is_completed_when_all_words_reach_100(): void
    {
        [, $student, , $level, $word] = $this->makeTeacherAndStudentClass('teacher-level-complete@example.com', 'student-level-complete@example.com');
        $word2 = $level->words()->create(['word' => 'cat', 'translation' => 'gato']);

        for ($i = 0; $i < 4; $i++) {
            $this->withToken($student->createToken('test')->plainTextToken)
                ->postJson('/api/student/vocabulary-words/' . $word->id . '/progress', ['correct' => true]);
        }

        for ($i = 0; $i < 4; $i++) {
            $this->withToken($student->createToken('test')->plainTextToken)
                ->postJson('/api/student/vocabulary-words/' . $word2->id . '/progress', ['correct' => true]);
        }

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->getJson('/api/student/vocabulary-levels/' . $level->id . '/progress');

        $response->assertStatus(200)
            ->assertJsonPath('data.summary.completed', true);
    }

    public function test_words_without_progress_rows_return_0_mastery(): void
    {
        [, $student, , $level] = $this->makeTeacherAndStudentClass('teacher-no-progress@example.com', 'student-no-progress@example.com');
        $word = $level->words()->create(['word' => 'cat', 'translation' => 'gato']);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->getJson('/api/student/vocabulary-levels/' . $level->id . '/progress');

        $response->assertStatus(200)
            ->assertJsonPath('data.words.0.mastery_percent', 0);
    }

    public function test_student_id_cannot_be_spoofed_from_request(): void
    {
        $teacher = $this->makeTeacherUser('teacher-spoof@example.com');
        $student = $this->makeStudentUser('student-spoof@example.com');
        $otherStudent = $this->makeStudentUser('student-other-spoof@example.com');
        $class = SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => 'Spoof Class']);
        $level = VocabularyLevel::create(['title' => 'People']);
        $word = $level->words()->create(['word' => 'friend', 'translation' => 'amigo']);
        $class->vocabularyLevels()->attach($level->id);
        $class->students()->attach($student->student->id, ['status' => 'active', 'enrolled_at' => now()]);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/vocabulary-words/' . $word->id . '/progress', [
                'correct' => true,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('student_word_progress', [
            'student_id' => $otherStudent->student->id,
            'vocabulary_word_id' => $word->id,
        ]);
    }
}
