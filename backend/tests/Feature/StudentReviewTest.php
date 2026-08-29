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

class StudentReviewTest extends TestCase
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

    private function makeAccessibleReviewSetup(string $teacherEmail, string $studentEmail, string $levelTitle = 'Animals'): array
    {
        $teacher = $this->makeTeacherUser($teacherEmail);
        $student = $this->makeStudentUser($studentEmail);
        $class = SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => 'Review Class']);
        $level = VocabularyLevel::create(['title' => $levelTitle]);
        $word1 = $level->words()->create(['word' => 'dog', 'translation' => 'perro', 'example' => 'The dog barks.']);
        $word2 = $level->words()->create(['word' => 'cat', 'translation' => 'gato', 'example' => 'The cat sleeps.']);
        $word3 = $level->words()->create(['word' => 'bird', 'translation' => 'pájaro', 'example' => 'The bird sings.']);

        $class->vocabularyLevels()->attach($level->id);
        $class->students()->attach($student->student->id, ['status' => 'active', 'enrolled_at' => now()]);

        return [$teacher, $student, $class, $level, $word1, $word2, $word3];
    }

    public function test_student_sees_existing_unmastered_progress_in_review_queue(): void
    {
        [, $student, , $level, $word1, $word2, $word3] = $this->makeAccessibleReviewSetup('teacher-review-queue@example.com', 'student-review-queue@example.com');

        $student->student->wordProgress()->create(['vocabulary_word_id' => $word1->id, 'mastery_percent' => 25, 'attempts' => 2, 'correct_attempts' => 1, 'last_practiced_at' => now()->subDay()]);
        $student->student->wordProgress()->create(['vocabulary_word_id' => $word2->id, 'mastery_percent' => 75, 'attempts' => 4, 'correct_attempts' => 3, 'last_practiced_at' => now()->subHours(3)]);
        $student->student->wordProgress()->create(['vocabulary_word_id' => $word3->id, 'mastery_percent' => 100, 'attempts' => 4, 'correct_attempts' => 4, 'last_practiced_at' => now()->subHours(1)]);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->getJson('/api/student/review');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.vocabulary_word_id', $word1->id)
            ->assertJsonMissing(['vocabulary_word_id' => $word3->id]);
    }

    public function test_words_without_progress_are_not_included_in_review_queue(): void
    {
        [, $student, , $level, $word1] = $this->makeAccessibleReviewSetup('teacher-review-no-progress@example.com', 'student-review-no-progress@example.com');
        $student->student->wordProgress()->create(['vocabulary_word_id' => $word1->id, 'mastery_percent' => 25, 'attempts' => 1, 'correct_attempts' => 1, 'last_practiced_at' => now()]);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->getJson('/api/student/review');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_mastered_words_are_not_included_in_review_queue(): void
    {
        [, $student, , , $word1] = $this->makeAccessibleReviewSetup('teacher-review-mastered@example.com', 'student-review-mastered@example.com');
        $student->student->wordProgress()->create(['vocabulary_word_id' => $word1->id, 'mastery_percent' => 100, 'attempts' => 5, 'correct_attempts' => 5, 'last_practiced_at' => now()]);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->getJson('/api/student/review');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_inaccessible_words_are_not_included_in_review_queue(): void
    {
        $teacher = $this->makeTeacherUser('teacher-review-inaccessible@example.com');
        $student = $this->makeStudentUser('student-review-inaccessible@example.com');
        $class = SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => 'Hidden Class']);
        $level = VocabularyLevel::create(['title' => 'Hidden Review']);
        $word = $level->words()->create(['word' => 'secret', 'translation' => 'secreto']);
        $student->student->wordProgress()->create(['vocabulary_word_id' => $word->id, 'mastery_percent' => 25, 'attempts' => 1, 'correct_attempts' => 0, 'last_practiced_at' => now()]);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->getJson('/api/student/review');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_weakest_mastery_appears_first_in_review_queue(): void
    {
        [, $student, , $level, $word1, $word2] = $this->makeAccessibleReviewSetup('teacher-review-strength@example.com', 'student-review-strength@example.com', 'Animals Review Sort');
        $student->student->wordProgress()->create(['vocabulary_word_id' => $word1->id, 'mastery_percent' => 75, 'attempts' => 3, 'correct_attempts' => 2, 'last_practiced_at' => now()]);
        $student->student->wordProgress()->create(['vocabulary_word_id' => $word2->id, 'mastery_percent' => 25, 'attempts' => 1, 'correct_attempts' => 1, 'last_practiced_at' => now()]);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->getJson('/api/student/review');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.vocabulary_word_id', $word2->id);
    }

    public function test_student_can_review_accessible_level(): void
    {
        [, $student, , $level] = $this->makeAccessibleReviewSetup('teacher-review-level-access@example.com', 'student-review-level-access@example.com');
        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->getJson('/api/student/vocabulary-levels/' . $level->id . '/review');

        $response->assertStatus(200);
    }

    public function test_student_cannot_access_review_for_inaccessible_level(): void
    {
        $teacher = $this->makeTeacherUser('teacher-review-inaccessible-level@example.com');
        $student = $this->makeStudentUser('student-review-inaccessible-level@example.com');
        $level = VocabularyLevel::create(['title' => 'Hidden Review Level']);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->getJson('/api/student/vocabulary-levels/' . $level->id . '/review');

        $response->assertStatus(403);
    }

    public function test_only_unmastered_existing_progress_appears_in_level_review(): void
    {
        [, $student, , $level, $word1, $word2, $word3] = $this->makeAccessibleReviewSetup('teacher-review-level-progress@example.com', 'student-review-level-progress@example.com');
        $student->student->wordProgress()->create(['vocabulary_word_id' => $word1->id, 'mastery_percent' => 50, 'attempts' => 2, 'correct_attempts' => 1, 'last_practiced_at' => now()]);
        $student->student->wordProgress()->create(['vocabulary_word_id' => $word2->id, 'mastery_percent' => 100, 'attempts' => 5, 'correct_attempts' => 5, 'last_practiced_at' => now()]);
        $student->student->wordProgress()->create(['vocabulary_word_id' => $word3->id, 'mastery_percent' => 0, 'attempts' => 1, 'correct_attempts' => 0, 'last_practiced_at' => now()]);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->getJson('/api/student/vocabulary-levels/' . $level->id . '/review');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_student_can_submit_correct_review(): void
    {
        [, $student, , $level, $word1] = $this->makeAccessibleReviewSetup('teacher-review-correct@example.com', 'student-review-correct@example.com');
        $student->student->wordProgress()->create(['vocabulary_word_id' => $word1->id, 'mastery_percent' => 25, 'attempts' => 2, 'correct_attempts' => 1, 'last_practiced_at' => now()]);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/vocabulary-words/' . $word1->id . '/review', ['correct' => true]);

        $response->assertStatus(200)
            ->assertJsonPath('data.mastery_percent', 50);
    }

    public function test_correct_review_increases_mastery_using_existing_rule(): void
    {
        [, $student, , $level, $word1] = $this->makeAccessibleReviewSetup('teacher-review-correct-mastery@example.com', 'student-review-correct-mastery@example.com');
        $student->student->wordProgress()->create(['vocabulary_word_id' => $word1->id, 'mastery_percent' => 25, 'attempts' => 2, 'correct_attempts' => 1, 'last_practiced_at' => now()]);

        $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/vocabulary-words/' . $word1->id . '/review', ['correct' => true]);

        $this->assertDatabaseHas('student_word_progress', [
            'student_id' => $student->student->id,
            'vocabulary_word_id' => $word1->id,
            'mastery_percent' => 50,
        ]);
    }

    public function test_correct_review_awards_xp_according_to_existing_rule(): void
    {
        [, $student, , $level, $word1] = $this->makeAccessibleReviewSetup('teacher-review-xp@example.com', 'student-review-xp@example.com');
        $student->student->wordProgress()->create(['vocabulary_word_id' => $word1->id, 'mastery_percent' => 25, 'attempts' => 2, 'correct_attempts' => 1, 'last_practiced_at' => now()]);

        $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/vocabulary-words/' . $word1->id . '/review', ['correct' => true]);

        $this->assertDatabaseHas('students', [
            'id' => $student->student->id,
            'total_xp' => 10,
        ]);
    }

    public function test_incorrect_review_increments_attempts_but_not_mastery(): void
    {
        [, $student, , $level, $word1] = $this->makeAccessibleReviewSetup('teacher-review-incorrect@example.com', 'student-review-incorrect@example.com');
        $student->student->wordProgress()->create(['vocabulary_word_id' => $word1->id, 'mastery_percent' => 25, 'attempts' => 2, 'correct_attempts' => 1, 'last_practiced_at' => now()]);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/vocabulary-words/' . $word1->id . '/review', ['correct' => false]);

        $response->assertStatus(200)
            ->assertJsonPath('data.mastery_percent', 25);
    }

    public function test_review_can_reach_100_mastery(): void
    {
        [, $student, , $level, $word1] = $this->makeAccessibleReviewSetup('teacher-review-100@example.com', 'student-review-100@example.com');
        $student->student->wordProgress()->create(['vocabulary_word_id' => $word1->id, 'mastery_percent' => 75, 'attempts' => 3, 'correct_attempts' => 2, 'last_practiced_at' => now()]);

        $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/vocabulary-words/' . $word1->id . '/review', ['correct' => true]);

        $this->assertDatabaseHas('student_word_progress', [
            'student_id' => $student->student->id,
            'vocabulary_word_id' => $word1->id,
            'mastery_percent' => 100,
        ]);
    }

    public function test_already_mastered_word_is_not_reviewable(): void
    {
        [, $student, , , $word1] = $this->makeAccessibleReviewSetup('teacher-review-already-mastered@example.com', 'student-review-already-mastered@example.com');
        $student->student->wordProgress()->create(['vocabulary_word_id' => $word1->id, 'mastery_percent' => 100, 'attempts' => 10, 'correct_attempts' => 10, 'last_practiced_at' => now()]);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/vocabulary-words/' . $word1->id . '/review', ['correct' => true]);

        $response->assertStatus(422);
    }

    public function test_word_without_progress_cannot_use_review_endpoint(): void
    {
        [, $student, , $level, $word1] = $this->makeAccessibleReviewSetup('teacher-review-no-progress-endpoint@example.com', 'student-review-no-progress-endpoint@example.com');

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/vocabulary-words/' . $word1->id . '/review', ['correct' => true]);

        $response->assertStatus(422);
    }

    public function test_inaccessible_word_cannot_be_reviewed(): void
    {
        $teacher = $this->makeTeacherUser('teacher-review-inaccessible-word@example.com');
        $student = $this->makeStudentUser('student-review-inaccessible-word@example.com');
        $class = SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => 'Hidden Review Class']);
        $level = VocabularyLevel::create(['title' => 'Blocked Review Level']);
        $word = $level->words()->create(['word' => 'river', 'translation' => 'río']);
        $student->student->wordProgress()->create(['vocabulary_word_id' => $word->id, 'mastery_percent' => 25, 'attempts' => 1, 'correct_attempts' => 0, 'last_practiced_at' => now()]);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/vocabulary-words/' . $word->id . '/review', ['correct' => true]);

        $response->assertStatus(403);
    }

    public function test_teacher_cannot_use_student_review_endpoints(): void
    {
        $teacher = $this->makeTeacherUser('teacher-review-endpoint@example.com');
        $level = VocabularyLevel::create(['title' => 'Teacher Review Level']);
        $word = $level->words()->create(['word' => 'teacher-word', 'translation' => 'palabra-del-maestro']);

        $this->withToken($teacher->createToken('test')->plainTextToken)
            ->getJson('/api/student/review')
            ->assertStatus(403);

        $this->withToken($teacher->createToken('test')->plainTextToken)
            ->getJson('/api/student/vocabulary-levels/' . $level->id . '/review')
            ->assertStatus(403);

        $this->withToken($teacher->createToken('test')->plainTextToken)
            ->postJson('/api/student/vocabulary-words/' . $word->id . '/review', ['correct' => true])
            ->assertStatus(403);
    }

    public function test_student_cannot_manipulate_another_students_progress(): void
    {
        [, $studentA, , , $word1] = $this->makeAccessibleReviewSetup('teacher-review-other-student-a@example.com', 'student-review-other-student-a@example.com', 'Animals Other A');
        [, $studentB] = $this->makeAccessibleReviewSetup('teacher-review-other-student-b@example.com', 'student-review-other-student-b@example.com', 'Animals Other B');
        $studentA->student->wordProgress()->create(['vocabulary_word_id' => $word1->id, 'mastery_percent' => 25, 'attempts' => 2, 'correct_attempts' => 1, 'last_practiced_at' => now()]);

        $response = $this->withToken($studentB->createToken('test')->plainTextToken)
            ->postJson('/api/student/vocabulary-words/' . $word1->id . '/review', ['correct' => true, 'student_id' => $studentA->student->id]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['student_id']);

        $this->assertDatabaseHas('student_word_progress', [
            'student_id' => $studentA->student->id,
            'vocabulary_word_id' => $word1->id,
            'mastery_percent' => 25,
        ]);
    }

    public function test_student_id_from_request_is_ignored_or_rejected(): void
    {
        [, $student, , , $word1] = $this->makeAccessibleReviewSetup('teacher-review-student-id@example.com', 'student-review-student-id@example.com');
        $student->student->wordProgress()->create(['vocabulary_word_id' => $word1->id, 'mastery_percent' => 25, 'attempts' => 2, 'correct_attempts' => 1, 'last_practiced_at' => now()]);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/student/vocabulary-words/' . $word1->id . '/review', ['correct' => true, 'student_id' => 999]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['student_id']);
    }
}
