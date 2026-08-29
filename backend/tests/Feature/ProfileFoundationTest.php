<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'teacher', 'student'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName], ['description' => $roleName]);
        }
    }

    public function test_student_registration_creates_student_profile(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Student Profile User',
            'email' => 'profile-student@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.role.name', 'student');

        $user = User::where('email', 'profile-student@example.com')->firstOrFail();
        $this->assertNotNull($user->student);
        $this->assertDatabaseHas('students', ['user_id' => $user->id]);
    }

    public function test_student_user_has_student_relation(): void
    {
        $role = Role::where('name', 'student')->firstOrFail();
        $user = User::factory()->create([
            'role_id' => $role->id,
            'email' => 'student-relation@example.com',
            'password' => Hash::make('password'),
        ]);

        $student = Student::create(['user_id' => $user->id]);

        $this->assertEquals($user->id, $student->user_id);
        $this->assertNotNull($user->fresh()->student);
    }

    public function test_seeded_teacher_has_teacher_profile(): void
    {
        $teacherRole = Role::where('name', 'teacher')->firstOrFail();
        $user = User::factory()->create([
            'role_id' => $teacherRole->id,
            'email' => 'seeded-teacher-profile@example.com',
            'password' => Hash::make('password'),
        ]);

        Teacher::firstOrCreate(['user_id' => $user->id]);

        $this->assertNotNull($user->fresh()->teacher);
        $this->assertDatabaseHas('teachers', ['user_id' => $user->id]);
    }

    public function test_admin_does_not_have_student_or_teacher_profile(): void
    {
        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $user = User::factory()->create([
            'role_id' => $adminRole->id,
            'email' => 'admin-profile@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->assertNull($user->fresh()->student);
        $this->assertNull($user->fresh()->teacher);
    }

    public function test_one_user_cannot_have_duplicate_student_profiles(): void
    {
        $role = Role::where('name', 'student')->firstOrFail();
        $user = User::factory()->create([
            'role_id' => $role->id,
            'email' => 'duplicate-student@example.com',
            'password' => Hash::make('password'),
        ]);

        Student::create(['user_id' => $user->id]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Student::create(['user_id' => $user->id]);
    }
}
