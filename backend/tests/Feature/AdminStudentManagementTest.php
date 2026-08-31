<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentWordProgress;
use App\Models\Teacher;
use App\Models\User;
use App\Models\VocabularyLevel;
use App\Models\VocabularyWord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminStudentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['admin', 'teacher', 'student'] as $role) Role::create(['name' => $role]);
    }

    private function make(string $role, string $email): User
    {
        $user = User::factory()->create(['role_id' => Role::where('name', $role)->value('id'), 'email' => $email]);
        if ($role === 'student') Student::create(['user_id' => $user->id]);
        if ($role === 'teacher') Teacher::create(['user_id' => $user->id]);
        return $user->fresh();
    }

    private function as(User $user): self
    {
        return $this->withToken($user->createToken('test')->plainTextToken);
    }

    public function test_admin_lists_and_views_students_with_enrollment_summary(): void
    {
        $admin = $this->make('admin', 'admin-list@example.com');
        $student = $this->make('student', 'listed@example.com');
        $teacher = $this->make('teacher', 'owner@example.com');
        $class = SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => 'Class']);
        $student->student->schoolClasses()->attach($class->id, ['status' => 'active', 'enrolled_at' => now()]);

        $this->as($admin)->getJson('/api/admin/students')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', $student->email)->assertJsonPath('data.0.enrolled_classes_count', 1);
        $this->getJson('/api/admin/students/'.$student->student->id)->assertOk()
            ->assertJsonPath('data.enrolled_classes.0.name', 'Class')->assertJsonPath('data.total_xp', 0);
    }

    public function test_admin_creates_student_profile_with_server_role_and_hashed_password(): void
    {
        $admin = $this->make('admin', 'admin-create@example.com');
        $this->as($admin)->postJson('/api/admin/students', [
            'name' => 'New Student', 'email' => 'new@example.com', 'password' => 'new-password',
        ])->assertCreated()->assertJsonMissingPath('data.password');
        $user = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertSame('student', $user->role->name);
        $this->assertNotNull($user->student);
        $this->assertTrue(Hash::check('new-password', $user->password));
    }

    public function test_create_validation_rejects_duplicate_short_password_and_spoofed_identity(): void
    {
        $admin = $this->make('admin', 'admin-validation@example.com');
        $existing = $this->make('student', 'duplicate@example.com');
        $this->as($admin)->postJson('/api/admin/students', ['name' => 'X', 'email' => $existing->email, 'password' => 'password'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->postJson('/api/admin/students', ['name' => 'X', 'email' => 'short@example.com', 'password' => 'short'])
            ->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->postJson('/api/admin/students', ['name' => 'X', 'email' => 'spoof@example.com', 'password' => 'password', 'role_id' => Role::where('name', 'admin')->value('id')])
            ->assertUnprocessable()->assertJsonValidationErrors('role_id');
    }

    public function test_admin_edits_student_without_changing_profile_role_or_xp(): void
    {
        $admin = $this->make('admin', 'admin-edit@example.com');
        $student = $this->make('student', 'before@example.com');
        $profileId = $student->student->id;
        $this->as($admin)->patchJson('/api/admin/students/'.$profileId, ['name' => 'After', 'email' => 'after@example.com'])
            ->assertOk()->assertJsonPath('data.name', 'After');
        $student->refresh();
        $this->assertSame('student', $student->role->name);
        $this->assertSame($profileId, $student->student->id);
        $this->assertSame(0, $student->student->total_xp);
    }

    public function test_admin_resets_password_and_new_credential_authenticates(): void
    {
        $admin = $this->make('admin', 'admin-password@example.com');
        $student = $this->make('student', 'reset@example.com');
        $this->as($admin)->patchJson('/api/admin/students/'.$student->student->id.'/password', ['password' => 'changed-password'])->assertOk();
        $student->refresh();
        $this->assertTrue(Hash::check('changed-password', $student->password));
        $this->app['auth']->forgetGuards(); $this->app['auth']->shouldUse('web');
        $this->withHeaders(['Authorization' => ''])->postJson('/api/auth/login', ['email' => $student->email, 'password' => 'changed-password'])
            ->assertOk()->assertJsonPath('data.user.role.name', 'student');
    }

    public function test_teacher_and_student_cannot_use_any_admin_student_endpoint(): void
    {
        $target = $this->make('student', 'target@example.com')->student;
        foreach (['teacher', 'student'] as $role) {
            $actor = $this->make($role, $role.'-actor@example.com');
            $this->as($actor);
            $this->getJson('/api/admin/students')->assertForbidden();
            $this->postJson('/api/admin/students', [])->assertForbidden();
            $this->getJson('/api/admin/students/'.$target->id)->assertForbidden();
            $this->patchJson('/api/admin/students/'.$target->id, [])->assertForbidden();
            $this->patchJson('/api/admin/students/'.$target->id.'/password', [])->assertForbidden();
            $this->deleteJson('/api/admin/students/'.$target->id)->assertForbidden();
        }
    }

    public function test_student_with_enrollment_and_history_cannot_be_deleted_and_data_remains(): void
    {
        $admin = $this->make('admin', 'admin-block@example.com');
        $student = $this->make('student', 'history@example.com');
        $teacher = $this->make('teacher', 'history-owner@example.com');
        $class = SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => 'History Class']);
        $student->student->schoolClasses()->attach($class->id, ['status' => 'active', 'enrolled_at' => now()]);
        $level = VocabularyLevel::create(['title' => 'Level', 'language' => 'es']);
        $word = VocabularyWord::create(['vocabulary_level_id' => $level->id, 'word' => 'hola', 'translation' => 'hello']);
        $progress = StudentWordProgress::create(['student_id' => $student->student->id, 'vocabulary_word_id' => $word->id, 'mastery_percent' => 25, 'attempts' => 1, 'correct_attempts' => 1]);

        $this->as($admin)->deleteJson('/api/admin/students/'.$student->student->id)->assertStatus(409);
        $this->assertDatabaseHas('class_enrollments', ['class_id' => $class->id, 'student_id' => $student->student->id]);
        $this->assertDatabaseHas('student_word_progress', ['id' => $progress->id]);
        $this->assertDatabaseHas('users', ['id' => $student->id]);
    }

    public function test_clean_student_can_be_deleted_safely(): void
    {
        $admin = $this->make('admin', 'admin-delete@example.com');
        $student = $this->make('student', 'clean@example.com');
        $profileId = $student->student->id;
        $this->as($admin)->deleteJson('/api/admin/students/'.$profileId)->assertOk();
        $this->assertDatabaseMissing('students', ['id' => $profileId]);
        $this->assertDatabaseMissing('users', ['id' => $student->id]);
    }
}
