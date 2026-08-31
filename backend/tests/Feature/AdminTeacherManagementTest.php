<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminTeacherManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'teacher', 'student'] as $name) {
            Role::create(['name' => $name, 'description' => $name]);
        }
    }

    private function userFor(string $role, string $email): User
    {
        $user = User::factory()->create([
            'role_id' => Role::where('name', $role)->value('id'),
            'email' => $email,
        ]);

        if ($role === 'teacher') {
            Teacher::create(['user_id' => $user->id]);
        } elseif ($role === 'student') {
            Student::create(['user_id' => $user->id]);
        }

        return $user->fresh();
    }

    private function tokenFor(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    public function test_admin_can_list_and_view_teachers_only(): void
    {
        $admin = $this->userFor('admin', 'admin@example.com');
        $teacher = $this->userFor('teacher', 'teacher@example.com');
        $this->userFor('student', 'student@example.com');

        $this->withToken($this->tokenFor($admin))->getJson('/api/admin/teachers')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', $teacher->email)
            ->assertJsonMissing(['password' => $teacher->password]);

        $this->getJson('/api/admin/teachers/'.$teacher->teacher->id)
            ->assertOk()
            ->assertJsonPath('data.name', $teacher->name)
            ->assertJsonPath('data.classes_count', 0);
    }

    public function test_admin_creates_hashed_teacher_user_and_profile_with_server_role(): void
    {
        $admin = $this->userFor('admin', 'create-admin@example.com');

        $this->withToken($this->tokenFor($admin))->postJson('/api/admin/teachers', [
            'name' => 'Created Teacher',
            'email' => 'created@example.com',
            'password' => 'new-password',
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Created Teacher')
            ->assertJsonMissingPath('data.password');

        $user = User::where('email', 'created@example.com')->firstOrFail();
        $this->assertSame('teacher', $user->role->name);
        $this->assertNotNull($user->teacher);
        $this->assertTrue(Hash::check('new-password', $user->password));
        $this->assertNotSame('new-password', $user->password);
    }

    public function test_create_rejects_duplicate_email_short_password_and_role_spoofing(): void
    {
        $admin = $this->userFor('admin', 'validation-admin@example.com');
        $token = $this->tokenFor($admin);
        $this->userFor('teacher', 'duplicate@example.com');

        $this->withToken($token)->postJson('/api/admin/teachers', [
            'name' => 'Duplicate', 'email' => 'duplicate@example.com', 'password' => 'password',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->postJson('/api/admin/teachers', [
            'name' => 'Short', 'email' => 'short@example.com', 'password' => 'short',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');

        $this->postJson('/api/admin/teachers', [
            'name' => 'Spoof', 'email' => 'spoof@example.com', 'password' => 'password',
            'role_id' => Role::where('name', 'admin')->value('id'),
        ])->assertUnprocessable()->assertJsonValidationErrors('role_id');
        $this->assertDatabaseMissing('users', ['email' => 'spoof@example.com']);
    }

    public function test_admin_can_edit_teacher_without_changing_profile_or_role(): void
    {
        $admin = $this->userFor('admin', 'edit-admin@example.com');
        $teacher = $this->userFor('teacher', 'before@example.com');
        $profileId = $teacher->teacher->id;

        $this->withToken($this->tokenFor($admin))->patchJson('/api/admin/teachers/'.$profileId, [
            'name' => 'After Name', 'email' => 'after@example.com',
        ])->assertOk()->assertJsonPath('data.email', 'after@example.com');

        $teacher->refresh();
        $this->assertSame('After Name', $teacher->name);
        $this->assertSame('teacher', $teacher->role->name);
        $this->assertSame($profileId, $teacher->teacher->id);
    }

    public function test_edit_unique_email_ignores_current_teacher_but_rejects_another_user(): void
    {
        $admin = $this->userFor('admin', 'unique-admin@example.com');
        $teacher = $this->userFor('teacher', 'same@example.com');
        $other = $this->userFor('teacher', 'other@example.com');
        $token = $this->tokenFor($admin);

        $this->withToken($token)->patchJson('/api/admin/teachers/'.$teacher->teacher->id, [
            'name' => $teacher->name, 'email' => $teacher->email,
        ])->assertOk();

        $this->patchJson('/api/admin/teachers/'.$teacher->teacher->id, [
            'name' => $teacher->name, 'email' => $other->email,
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_admin_can_reset_password_and_teacher_can_authenticate_with_it(): void
    {
        $admin = $this->userFor('admin', 'password-admin@example.com');
        $teacher = $this->userFor('teacher', 'password-teacher@example.com');
        $oldHash = $teacher->password;

        $this->withToken($this->tokenFor($admin))->patchJson('/api/admin/teachers/'.$teacher->teacher->id.'/password', [
            'password' => 'changed-password',
        ])->assertOk()->assertJsonMissing(['password' => true]);

        $teacher->refresh();
        $this->assertNotSame($oldHash, $teacher->password);
        $this->assertTrue(Hash::check('changed-password', $teacher->password));
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');
        $this->withHeaders(['Authorization' => ''])->postJson('/api/auth/login', [
            'email' => $teacher->email, 'password' => 'changed-password',
        ])->assertOk()->assertJsonPath('data.user.role.name', 'teacher');
    }

    public function test_password_reset_rejects_short_password(): void
    {
        $admin = $this->userFor('admin', 'short-password-admin@example.com');
        $teacher = $this->userFor('teacher', 'short-password-teacher@example.com');

        $this->withToken($this->tokenFor($admin))->patchJson('/api/admin/teachers/'.$teacher->teacher->id.'/password', [
            'password' => 'short',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
    }

    public function test_teacher_and_student_are_forbidden_from_every_admin_teacher_endpoint(): void
    {
        $target = $this->userFor('teacher', 'target@example.com')->teacher;

        foreach (['teacher', 'student'] as $role) {
            $actor = $this->userFor($role, $role.'-actor@example.com');
            $this->withToken($this->tokenFor($actor));
            $this->getJson('/api/admin/teachers')->assertForbidden();
            $this->postJson('/api/admin/teachers', ['name' => 'X', 'email' => $role.'-x@example.com', 'password' => 'password'])->assertForbidden();
            $this->getJson('/api/admin/teachers/'.$target->id)->assertForbidden();
            $this->patchJson('/api/admin/teachers/'.$target->id, ['name' => 'X', 'email' => 'x@example.com'])->assertForbidden();
            $this->patchJson('/api/admin/teachers/'.$target->id.'/password', ['password' => 'password'])->assertForbidden();
            $this->deleteJson('/api/admin/teachers/'.$target->id)->assertForbidden();
        }
    }

    public function test_teacher_with_classes_cannot_be_deleted_and_class_remains(): void
    {
        $admin = $this->userFor('admin', 'delete-admin@example.com');
        $teacher = $this->userFor('teacher', 'owner@example.com');
        $class = SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => 'Owned Class']);

        $this->withToken($this->tokenFor($admin))->deleteJson('/api/admin/teachers/'.$teacher->teacher->id)
            ->assertStatus(409)
            ->assertJsonPath('message', 'Teacher cannot be deleted while they own classes.');

        $this->assertDatabaseHas('users', ['id' => $teacher->id]);
        $this->assertDatabaseHas('teachers', ['id' => $teacher->teacher->id]);
        $this->assertDatabaseHas('classes', ['id' => $class->id]);
    }

    public function test_teacher_without_classes_is_deleted_safely(): void
    {
        $admin = $this->userFor('admin', 'safe-delete-admin@example.com');
        $teacher = $this->userFor('teacher', 'safe-delete@example.com');
        $teacherId = $teacher->teacher->id;

        $this->withToken($this->tokenFor($admin))->deleteJson('/api/admin/teachers/'.$teacherId)->assertOk();

        $this->assertDatabaseMissing('teachers', ['id' => $teacherId]);
        $this->assertDatabaseMissing('users', ['id' => $teacher->id]);
    }
}
