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

class StudentPracticeSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'teacher', 'student'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName], ['description' => $roleName]);
        }
    }

    private function makeTeacherUser(string $email): User
    {
        $role = Role::where('name', 'teacher')->firstOrFail();
        $user = User::factory()->create([
            'email' => $email,
            'role_id' => $role->id,
        ]);

        Teacher::create(['user_id' => $user->id]);

        return $user->fresh();
    }

    private function makeStudentUser(string $email): User
    {
        $role = Role::where('name', 'student')->firstOrFail();
        $user = User::factory()->create([
            'email' => $email,
            'role_id' => $role->id,
        ]);

        Student::create(['user_id' => $user->id]);

        return $user->fresh();
    }

    private function makeAccessibleLevelSetup(string $teacherEmail, string $studentEmail, string $levelTitle = 'Animals'): array
    {
        $teacher = $this->makeTeacherUser($teacherEmail);
        $student = $this->makeStudentUser($studentEmail);
        $class = SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => 'Practice Class']);
        $level = VocabularyLevel::create(['title' => $levelTitle]);
        $word1 = $level->words()->create(['word' => 'dog', 'translation' => 'perro', 'example' => 'The dog barks.']);
        $word2 = $level->words()->create(['word' => 'cat', 'translation' => 'gato', 'example' => 'The cat sleeps.']);

        $class->vocabularyLevels()->attach($level->id);
        $class->students()->attach($student->student->id, ['status' => 'active', 'enrolled_at' => now()]);

        return [$teacher, $student, $class, $level, $word1, $word2];
    }

    public function test_student_can_start_accessible_level(): void
    {
        [, $student, , $level] = $this->makeAccessibleLevelSetup('teacher-practice-start@example.com', 'student-practice-start@example.com');

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/vocabulary-levels/' . $level->id . '/practice');

        $response->assertStatus(201)
            ->assertJsonPath('data.level.id', $level->id)
            ->assertJsonPath('data.session.student_id', $student->student->id);
    }

    public function test_student_cannot_start_inaccessible_level(): void
    {
        $teacher = $this->makeTeacherUser('teacher-practice-hidden@example.com');
        $student = $this->makeStudentUser('student-practice-hidden@example.com');
        $class = SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => 'Hidden Class']);
        $level = VocabularyLevel::create(['title' => 'Hidden Level']);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/vocabulary-levels/' . $level->id . '/practice');

        $response->assertStatus(403);
    }

    public function test_teacher_cannot_start_student_practice(): void
    {
        $teacher = $this->makeTeacherUser('teacher-practice-prohibit@example.com');
        $level = VocabularyLevel::create(['title' => 'Teacher Level']);

        $response = $this->withToken($teacher->createToken('test')->plainTextToken)
            ->postJson('/api/student/vocabulary-levels/' . $level->id . '/practice');

        $response->assertStatus(403);
    }

    public function test_student_can_submit_correct_answer(): void
    {
        [, $student, , $level, $word1] = $this->makeAccessibleLevelSetup('teacher-practice-correct@example.com', 'student-practice-correct@example.com');
        $session = $student->student->practiceSessions()->create([
            'vocabulary_level_id' => $level->id,
            'started_at' => now(),
        ]);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/practice-sessions/' . $session->id . '/attempts', [
                'vocabulary_word_id' => $word1->id,
                'submitted_answer' => 'perro',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.vocabulary_word_id', $word1->id)
            ->assertJsonPath('data.is_correct', true);
    }

    public function test_student_can_submit_incorrect_answer(): void
    {
        [, $student, , $level, $word1] = $this->makeAccessibleLevelSetup('teacher-practice-wrong@example.com', 'student-practice-wrong@example.com');
        $session = $student->student->practiceSessions()->create([
            'vocabulary_level_id' => $level->id,
            'started_at' => now(),
        ]);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/practice-sessions/' . $session->id . '/attempts', [
                'vocabulary_word_id' => $word1->id,
                'submitted_answer' => 'incorrect',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.is_correct', false);
    }

    public function test_backend_determines_correctness_from_word_translation(): void
    {
        [, $student, , $level, $word1] = $this->makeAccessibleLevelSetup('teacher-practice-logic@example.com', 'student-practice-logic@example.com');
        $session = $student->student->practiceSessions()->create([
            'vocabulary_level_id' => $level->id,
            'started_at' => now(),
        ]);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/practice-sessions/' . $session->id . '/attempts', [
                'vocabulary_word_id' => $word1->id,
                'submitted_answer' => 'PERRO',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.is_correct', true);
    }

    public function test_correct_attempt_updates_mastery(): void
    {
        [, $student, , $level, $word1] = $this->makeAccessibleLevelSetup('teacher-practice-mastery@example.com', 'student-practice-mastery@example.com');
        $session = $student->student->practiceSessions()->create([
            'vocabulary_level_id' => $level->id,
            'started_at' => now(),
        ]);

        $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/practice-sessions/' . $session->id . '/attempts', [
                'vocabulary_word_id' => $word1->id,
                'submitted_answer' => 'perro',
            ]);

        $this->assertDatabaseHas('student_word_progress', [
            'student_id' => $student->student->id,
            'vocabulary_word_id' => $word1->id,
            'mastery_percent' => 25,
        ]);
    }

    public function test_correct_attempt_awards_xp_according_to_existing_rules(): void
    {
        [, $student, , $level, $word1] = $this->makeAccessibleLevelSetup('teacher-practice-xp@example.com', 'student-practice-xp@example.com');
        $session = $student->student->practiceSessions()->create([
            'vocabulary_level_id' => $level->id,
            'started_at' => now(),
        ]);

        $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/practice-sessions/' . $session->id . '/attempts', [
                'vocabulary_word_id' => $word1->id,
                'submitted_answer' => 'perro',
            ]);

        $this->assertDatabaseHas('students', [
            'id' => $student->student->id,
            'total_xp' => 10,
        ]);
    }

    public function test_incorrect_attempt_does_not_increase_mastery(): void
    {
        [, $student, , $level, $word1] = $this->makeAccessibleLevelSetup('teacher-practice-incorrect-mastery@example.com', 'student-practice-incorrect-mastery@example.com');
        $session = $student->student->practiceSessions()->create([
            'vocabulary_level_id' => $level->id,
            'started_at' => now(),
        ]);

        $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/practice-sessions/' . $session->id . '/attempts', [
                'vocabulary_word_id' => $word1->id,
                'submitted_answer' => 'incorrect',
            ]);

        $this->assertDatabaseHas('student_word_progress', [
            'student_id' => $student->student->id,
            'vocabulary_word_id' => $word1->id,
            'mastery_percent' => 0,
        ]);
    }

    public function test_duplicate_word_attempt_in_same_session_fails(): void
    {
        [, $student, , $level, $word1] = $this->makeAccessibleLevelSetup('teacher-practice-duplicate@example.com', 'student-practice-duplicate@example.com');
        $session = $student->student->practiceSessions()->create([
            'vocabulary_level_id' => $level->id,
            'started_at' => now(),
        ]);

        $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/practice-sessions/' . $session->id . '/attempts', [
                'vocabulary_word_id' => $word1->id,
                'submitted_answer' => 'perro',
            ]);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/practice-sessions/' . $session->id . '/attempts', [
                'vocabulary_word_id' => $word1->id,
                'submitted_answer' => 'perro',
            ]);

        $response->assertStatus(409);
    }

    public function test_word_outside_session_level_fails(): void
    {
        $teacher = $this->makeTeacherUser('teacher-practice-outside@example.com');
        $student = $this->makeStudentUser('student-practice-outside@example.com');
        $class = SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => 'Outside Class']);
        $level = VocabularyLevel::create(['title' => 'Animals']);
        $otherLevel = VocabularyLevel::create(['title' => 'Food']);
        $word = $otherLevel->words()->create(['word' => 'apple', 'translation' => 'manzana']);

        $class->vocabularyLevels()->attach($level->id);
        $class->students()->attach($student->student->id, ['status' => 'active', 'enrolled_at' => now()]);
        $session = $student->student->practiceSessions()->create([
            'vocabulary_level_id' => $level->id,
            'started_at' => now(),
        ]);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/practice-sessions/' . $session->id . '/attempts', [
                'vocabulary_word_id' => $word->id,
                'submitted_answer' => 'manzana',
            ]);

        $response->assertStatus(422);
    }

    public function test_student_cannot_submit_to_another_students_session(): void
    {
        [, $studentA, , $level, $word1] = $this->makeAccessibleLevelSetup('teacher-practice-other-a@example.com', 'student-practice-other-a@example.com', 'Animals A');
        [, $studentB] = $this->makeAccessibleLevelSetup('teacher-practice-other-b@example.com', 'student-practice-other-b@example.com', 'Animals B');
        $session = $studentA->student->practiceSessions()->create([
            'vocabulary_level_id' => $level->id,
            'started_at' => now(),
        ]);

        $response = $this->withToken($studentB->createToken('test')->plainTextToken)
            ->postJson('/api/student/practice-sessions/' . $session->id . '/attempts', [
                'vocabulary_word_id' => $word1->id,
                'submitted_answer' => 'perro',
            ]);

        $response->assertStatus(403);
    }

    public function test_owning_student_can_complete_session(): void
    {
        [, $student, , $level, $word1, $word2] = $this->makeAccessibleLevelSetup('teacher-practice-complete@example.com', 'student-practice-complete@example.com');
        $session = $student->student->practiceSessions()->create([
            'vocabulary_level_id' => $level->id,
            'started_at' => now(),
        ]);

        $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/practice-sessions/' . $session->id . '/attempts', [
                'vocabulary_word_id' => $word1->id,
                'submitted_answer' => 'perro',
            ]);
        $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/practice-sessions/' . $session->id . '/attempts', [
                'vocabulary_word_id' => $word2->id,
                'submitted_answer' => 'incorrect',
            ]);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/practice-sessions/' . $session->id . '/complete');

        $response->assertStatus(200)
            ->assertJsonPath('data.total_questions', 2)
            ->assertJsonPath('data.correct_answers', 1)
            ->assertJsonPath('data.score_percent', 50);
    }

    public function test_completed_session_rejects_new_attempts(): void
    {
        [, $student, , $level, $word1, $word2] = $this->makeAccessibleLevelSetup('teacher-practice-finished@example.com', 'student-practice-finished@example.com');
        $session = $student->student->practiceSessions()->create([
            'vocabulary_level_id' => $level->id,
            'started_at' => now(),
        ]);

        $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/practice-sessions/' . $session->id . '/complete');

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/practice-sessions/' . $session->id . '/attempts', [
                'vocabulary_word_id' => $word1->id,
                'submitted_answer' => 'perro',
            ]);

        $response->assertStatus(422);
    }

    public function test_another_student_cannot_complete_session(): void
    {
        [, $studentA, , $level, $word1] = $this->makeAccessibleLevelSetup('teacher-practice-other-complete-a@example.com', 'student-practice-other-complete-a@example.com', 'Animals C');
        [, $studentB] = $this->makeAccessibleLevelSetup('teacher-practice-other-complete-b@example.com', 'student-practice-other-complete-b@example.com', 'Animals D');
        $session = $studentA->student->practiceSessions()->create([
            'vocabulary_level_id' => $level->id,
            'started_at' => now(),
        ]);

        $response = $this->withToken($studentB->createToken('test')->plainTextToken)
            ->postJson('/api/student/practice-sessions/' . $session->id . '/complete');

        $response->assertStatus(403);
    }

    public function test_student_sees_only_own_sessions(): void
    {
        [, $studentA, , $levelA] = $this->makeAccessibleLevelSetup('teacher-practice-list-a@example.com', 'student-practice-list-a@example.com', 'Animals E');
        [, $studentB, , $levelB] = $this->makeAccessibleLevelSetup('teacher-practice-list-b@example.com', 'student-practice-list-b@example.com', 'Animals F');

        $sessionA = $studentA->student->practiceSessions()->create([
            'vocabulary_level_id' => $levelA->id,
            'started_at' => now(),
        ]);
        $sessionB = $studentB->student->practiceSessions()->create([
            'vocabulary_level_id' => $levelB->id,
            'started_at' => now(),
        ]);

        $response = $this->withToken($studentA->createToken('test')->plainTextToken)
            ->getJson('/api/student/practice-sessions');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $sessionA->id)
            ->assertJsonMissing(['id' => $sessionB->id]);
    }

    public function test_student_can_view_own_session(): void
    {
        [, $student, , $level] = $this->makeAccessibleLevelSetup('teacher-practice-show@example.com', 'student-practice-show@example.com');
        $session = $student->student->practiceSessions()->create([
            'vocabulary_level_id' => $level->id,
            'started_at' => now(),
        ]);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->getJson('/api/student/practice-sessions/' . $session->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $session->id);
    }

    public function test_student_cannot_view_another_students_session(): void
    {
        [, $studentA, , $levelA] = $this->makeAccessibleLevelSetup('teacher-practice-view-other-a@example.com', 'student-practice-view-other-a@example.com', 'Animals G');
        [, $studentB, , $levelB] = $this->makeAccessibleLevelSetup('teacher-practice-view-other-b@example.com', 'student-practice-view-other-b@example.com', 'Animals H');
        $session = $studentA->student->practiceSessions()->create([
            'vocabulary_level_id' => $levelA->id,
            'started_at' => now(),
        ]);

        $response = $this->withToken($studentB->createToken('test')->plainTextToken)
            ->getJson('/api/student/practice-sessions/' . $session->id);

        $response->assertStatus(403);
    }
}
