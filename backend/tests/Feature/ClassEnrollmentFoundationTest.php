<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassEnrollmentFoundationTest extends TestCase
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

    public function test_teacher_can_create_own_class(): void
    {
        $teacher = $this->makeTeacherUser('teacher-create@example.com');

        $response = $this->withToken($teacher->createToken('test')->plainTextToken)
            ->postJson('/api/classes', [
                'name' => 'Math Fundamentals',
                'description' => 'Basic math',
                'language' => 'en',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Math Fundamentals')
            ->assertJsonPath('data.teacher.id', $teacher->teacher->id);

        $this->assertDatabaseHas('classes', [
            'teacher_id' => $teacher->teacher->id,
            'name' => 'Math Fundamentals',
        ]);
    }

    public function test_teacher_cannot_create_class_for_another_teacher(): void
    {
        $teacher = $this->makeTeacherUser('teacher-a@example.com');
        $otherTeacher = $this->makeTeacherUser('teacher-b@example.com');

        $response = $this->withToken($teacher->createToken('test')->plainTextToken)
            ->postJson('/api/classes', [
                'name' => 'Not Allowed',
                'teacher_id' => $otherTeacher->teacher->id,
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_create_class_for_selected_teacher(): void
    {
        $admin = $this->makeAdminUser('admin-create@example.com');
        $teacher = $this->makeTeacherUser('teacher-selected@example.com');

        $response = $this->withToken($admin->createToken('test')->plainTextToken)
            ->postJson('/api/classes', [
                'name' => 'Science Club',
                'teacher_id' => $teacher->teacher->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.teacher.id', $teacher->teacher->id)
            ->assertJsonPath('data.name', 'Science Club');
    }

    public function test_student_cannot_create_class(): void
    {
        $student = $this->makeStudentUser('student-create@example.com');

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->postJson('/api/classes', [
                'name' => 'Forbidden Class',
            ]);

        $response->assertStatus(403);
    }

    public function test_teacher_sees_only_own_classes(): void
    {
        $teacherA = $this->makeTeacherUser('teacher-own-a@example.com');
        $teacherB = $this->makeTeacherUser('teacher-own-b@example.com');

        SchoolClass::create(['teacher_id' => $teacherA->teacher->id, 'name' => 'Teacher A class']);
        SchoolClass::create(['teacher_id' => $teacherB->teacher->id, 'name' => 'Teacher B class']);

        $response = $this->withToken($teacherA->createToken('test')->plainTextToken)
            ->getJson('/api/classes');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Teacher A class');
    }

    public function test_student_sees_only_enrolled_classes(): void
    {
        $teacher = $this->makeTeacherUser('teacher-enroll@example.com');
        $student = $this->makeStudentUser('student-enroll@example.com');

        $enrolled = SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => 'Enrolled class']);
        $other = SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => 'Other class']);

        $enrolled->students()->attach($student->student->id, ['status' => 'active', 'enrolled_at' => now()]);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->getJson('/api/classes');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Enrolled class');
    }

    public function test_student_cannot_access_unenrolled_class(): void
    {
        $teacher = $this->makeTeacherUser('teacher-restricted@example.com');
        $student = $this->makeStudentUser('student-restricted@example.com');

        $class = SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => 'Secret class']);

        $response = $this->withToken($student->createToken('test')->plainTextToken)
            ->getJson('/api/classes/' . $class->id);

        $response->assertStatus(403);
    }

    public function test_teacher_can_update_own_class(): void
    {
        $teacher = $this->makeTeacherUser('teacher-update@example.com');
        $class = SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => 'Original']);

        $response = $this->withToken($teacher->createToken('test')->plainTextToken)
            ->patchJson('/api/classes/' . $class->id, [
                'name' => 'Updated Name',
                'description' => 'Updated summary',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name');
        $this->assertDatabaseHas('classes', ['id' => $class->id, 'name' => 'Updated Name']);
    }

    public function test_teacher_cannot_update_another_teachers_class(): void
    {
        $teacherA = $this->makeTeacherUser('teacher-update-a@example.com');
        $teacherB = $this->makeTeacherUser('teacher-update-b@example.com');
        $class = SchoolClass::create(['teacher_id' => $teacherB->teacher->id, 'name' => 'Other class']);

        $response = $this->withToken($teacherA->createToken('test')->plainTextToken)
            ->patchJson('/api/classes/' . $class->id, [
                'name' => 'Hacked',
            ]);

        $response->assertStatus(403);
    }

    public function test_teacher_can_enroll_a_student(): void
    {
        $teacher = $this->makeTeacherUser('teacher-enroll-owner@example.com');
        $student = $this->makeStudentUser('student-enroll-target@example.com');
        $class = SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => 'Enrollment class']);

        $response = $this->withToken($teacher->createToken('test')->plainTextToken)
            ->postJson('/api/classes/' . $class->id . '/students', [
                'student_id' => $student->student->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.student_id', $student->student->id);
        $this->assertDatabaseHas('class_enrollments', [
            'class_id' => $class->id,
            'student_id' => $student->student->id,
            'status' => 'active',
        ]);
    }

    public function test_duplicate_enrollment_fails(): void
    {
        $teacher = $this->makeTeacherUser('teacher-duplicate@example.com');
        $student = $this->makeStudentUser('student-duplicate@example.com');
        $class = SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => 'Duplicate class']);

        $this->withToken($teacher->createToken('test')->plainTextToken)
            ->postJson('/api/classes/' . $class->id . '/students', [
                'student_id' => $student->student->id,
            ])->assertStatus(201);

        $this->withToken($teacher->createToken('test')->plainTextToken)
            ->postJson('/api/classes/' . $class->id . '/students', [
                'student_id' => $student->student->id,
            ])->assertStatus(422);
    }

    public function test_teacher_can_remove_student_enrollment(): void
    {
        $teacher = $this->makeTeacherUser('teacher-remove@example.com');
        $student = $this->makeStudentUser('student-remove@example.com');
        $class = SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => 'Removal class']);
        $class->students()->attach($student->student->id, ['status' => 'active', 'enrolled_at' => now()]);

        $response = $this->withToken($teacher->createToken('test')->plainTextToken)
            ->deleteJson('/api/classes/' . $class->id . '/students/' . $student->student->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('class_enrollments', [
            'class_id' => $class->id,
            'student_id' => $student->student->id,
        ]);
        $this->assertDatabaseHas('students', ['id' => $student->student->id]);
    }
}
