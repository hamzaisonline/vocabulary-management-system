<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Models\VocabularyLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassVocabularyAssignmentTest extends TestCase
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

    private function makeAdminUser(string $email = 'admin@example.com'): User
    {
        $role = Role::where('name', 'admin')->firstOrFail();

        return User::factory()->create([
            'email' => $email,
            'role_id' => $role->id,
        ]);
    }

    public function test_owning_teacher_can_assign_vocabulary_level(): void
    {
        $teacher = $this->makeTeacherUser('teacher-assign@example.com');
        $class = SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => 'Science']);
        $level = VocabularyLevel::create(['title' => 'Pets']);

        $response = $this->withToken($teacher->createToken('test')->plainTextToken)
            ->postJson('/api/classes/' . $class->id . '/vocabulary-levels', [
                'vocabulary_level_id' => $level->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.vocabulary_level_id', $level->id);
    }

    public function test_teacher_cannot_assign_level_to_another_teachers_class(): void
    {
        $teacherA = $this->makeTeacherUser('teacher-a@example.com');
        $teacherB = $this->makeTeacherUser('teacher-b@example.com');
        $class = SchoolClass::create(['teacher_id' => $teacherB->teacher->id, 'name' => 'Other']);
        $level = VocabularyLevel::create(['title' => 'Travel']);

        $response = $this->withToken($teacherA->createToken('test')->plainTextToken)
            ->postJson('/api/classes/' . $class->id . '/vocabulary-levels', [
                'vocabulary_level_id' => $level->id,
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_assign_level_to_any_class(): void
    {
        $admin = $this->makeAdminUser('admin-class-vocab@example.com');
        $teacher = $this->makeTeacherUser('teacher-admin-assign@example.com');
        $class = SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => 'History']);
        $level = VocabularyLevel::create(['title' => 'Food']);

        $response = $this->withToken($admin->createToken('test')->plainTextToken)
            ->postJson('/api/classes/' . $class->id . '/vocabulary-levels', [
                'vocabulary_level_id' => $level->id,
            ]);

        $response->assertStatus(201);
    }

    public function test_student_cannot_assign_level(): void
    {
        $student = $this->makeStudentUser('student-assign@example.com');
        $teacher = $this->makeTeacherUser('teacher-for-student-example@example.com');
        $class = SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => 'Math']);
        $level = VocabularyLevel::create(['title' => 'Nature']);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/classes/' . $class->id . '/vocabulary-levels', [
                'vocabulary_level_id' => $level->id,
            ]);

        $response->assertStatus(403);
    }

    public function test_duplicate_assignment_fails(): void
    {
        $teacher = $this->makeTeacherUser('teacher-duplicate-vocab@example.com');
        $class = SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => 'Art']);
        $level = VocabularyLevel::create(['title' => 'Colors']);

        $this->withToken($teacher->createToken('test')->plainTextToken)
            ->postJson('/api/classes/' . $class->id . '/vocabulary-levels', [
                'vocabulary_level_id' => $level->id,
            ])->assertStatus(201);

        $this->withToken($teacher->createToken('test')->plainTextToken)
            ->postJson('/api/classes/' . $class->id . '/vocabulary-levels', [
                'vocabulary_level_id' => $level->id,
            ])->assertStatus(422);
    }

    public function test_owning_teacher_can_remove_assignment(): void
    {
        $teacher = $this->makeTeacherUser('teacher-remove-vocab@example.com');
        $class = SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => 'Biology']);
        $level = VocabularyLevel::create(['title' => 'Animals']);
        $class->vocabularyLevels()->attach($level->id);

        $response = $this->withToken($teacher->createToken('test')->plainTextToken)
            ->deleteJson('/api/classes/' . $class->id . '/vocabulary-levels/' . $level->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('class_vocabulary_levels', [
            'class_id' => $class->id,
            'vocabulary_level_id' => $level->id,
        ]);
    }

    public function test_removing_assignment_does_not_delete_vocabulary_level(): void
    {
        $teacher = $this->makeTeacherUser('teacher-remove-vocab-keep-level@example.com');
        $class = SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => 'Language']);
        $level = VocabularyLevel::create(['title' => 'Greetings']);
        $class->vocabularyLevels()->attach($level->id);

        $this->withToken($teacher->createToken('test')->plainTextToken)
            ->deleteJson('/api/classes/' . $class->id . '/vocabulary-levels/' . $level->id)
            ->assertStatus(200);

        $this->assertDatabaseHas('vocabulary_levels', ['id' => $level->id]);
    }

    public function test_student_enrolled_in_class_can_view_assigned_levels(): void
    {
        $teacher = $this->makeTeacherUser('teacher-view-vocab@example.com');
        $student = $this->makeStudentUser('student-view-vocab@example.com');
        $class = SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => 'English']);
        $level = VocabularyLevel::create(['title' => 'School']);
        $class->vocabularyLevels()->attach($level->id);
        $class->students()->attach($student->student->id, ['status' => 'active', 'enrolled_at' => now()]);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->getJson('/api/classes/' . $class->id . '/vocabulary-levels');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'School');
    }

    public function test_student_cannot_view_assignments_for_unrelated_class(): void
    {
        $teacher = $this->makeTeacherUser('teacher-other-class@example.com');
        $student = $this->makeStudentUser('student-other-class@example.com');
        $class = SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => 'Other class']);
        $level = VocabularyLevel::create(['title' => 'Hobbies']);
        $class->vocabularyLevels()->attach($level->id);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->getJson('/api/classes/' . $class->id . '/vocabulary-levels');

        $response->assertStatus(403);
    }

    public function test_student_vocabulary_levels_endpoint_returns_levels_from_enrolled_classes_only(): void
    {
        $teacher = $this->makeTeacherUser('teacher-student-vocab@example.com');
        $student = $this->makeStudentUser('student-vocab-access@example.com');
        $classA = SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => 'Class A']);
        $classB = SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => 'Class B']);
        $levelA = VocabularyLevel::create(['title' => 'Family']);
        $levelB = VocabularyLevel::create(['title' => 'Work']);
        $classA->vocabularyLevels()->attach($levelA->id);
        $classB->vocabularyLevels()->attach($levelB->id);
        $classA->students()->attach($student->student->id, ['status' => 'active', 'enrolled_at' => now()]);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->getJson('/api/student/vocabulary-levels');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Family');
    }

    public function test_duplicate_vocabulary_levels_are_not_returned_when_multiple_classes_assign_the_same_level(): void
    {
        $teacher = $this->makeTeacherUser('teacher-multi-class-vocab@example.com');
        $student = $this->makeStudentUser('student-multi-class-vocab@example.com');
        $classA = SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => 'Class One']);
        $classB = SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => 'Class Two']);
        $level = VocabularyLevel::create(['title' => 'Weather']);
        $classA->vocabularyLevels()->attach($level->id);
        $classB->vocabularyLevels()->attach($level->id);
        $classA->students()->attach($student->student->id, ['status' => 'active', 'enrolled_at' => now()]);
        $classB->students()->attach($student->student->id, ['status' => 'active', 'enrolled_at' => now()]);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->getJson('/api/student/vocabulary-levels');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $level->id);
    }
}
