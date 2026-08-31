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

class VocabularySharingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'teacher', 'student'] as $role) {
            Role::firstOrCreate(['name' => $role], ['description' => $role]);
        }
    }

    private function user(string $role, string $email): User
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

    private function token(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    public function test_creator_is_server_assigned_and_visibility_defaults_private(): void
    {
        $owner = $this->user('teacher', 'owner-create@example.com');
        $other = $this->user('teacher', 'spoofed@example.com');

        $this->withToken($this->token($owner))->postJson('/api/vocabulary/levels', [
            'title' => 'Owner Set',
            'created_by_user_id' => $other->id,
        ])->assertCreated()
            ->assertJsonPath('data.created_by_user_id', $owner->id)
            ->assertJsonPath('data.visibility', 'private')
            ->assertJsonPath('data.is_owner', true);
    }

    public function test_owner_can_share_and_unshare_set(): void
    {
        $owner = $this->user('teacher', 'owner-share@example.com');
        $level = VocabularyLevel::create(['title' => 'Shareable', 'created_by_user_id' => $owner->id]);

        $this->withToken($this->token($owner))->patchJson("/api/vocabulary/levels/{$level->id}", [
            'visibility' => 'shared',
        ])->assertOk()->assertJsonPath('data.visibility', 'shared');

        $this->withToken($this->token($owner))->patchJson("/api/vocabulary/levels/{$level->id}", [
            'visibility' => 'private',
        ])->assertOk()->assertJsonPath('data.visibility', 'private');
    }

    public function test_teacher_discovery_is_scoped_to_own_and_shared_sets(): void
    {
        $owner = $this->user('teacher', 'owner-discovery@example.com');
        $viewer = $this->user('teacher', 'viewer-discovery@example.com');
        VocabularyLevel::create(['title' => 'Own', 'created_by_user_id' => $viewer->id]);
        VocabularyLevel::create(['title' => 'Shared', 'created_by_user_id' => $owner->id, 'visibility' => 'shared']);
        VocabularyLevel::create(['title' => 'Hidden', 'created_by_user_id' => $owner->id, 'visibility' => 'private']);

        $this->withToken($this->token($viewer))->getJson('/api/vocabulary/levels?scope=all')
            ->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonFragment(['title' => 'Own'])->assertJsonFragment(['title' => 'Shared'])
            ->assertJsonMissing(['title' => 'Hidden']);
    }

    public function test_non_owner_cannot_mutate_shared_level_or_its_words(): void
    {
        $owner = $this->user('teacher', 'owner-mutation@example.com');
        $viewer = $this->user('teacher', 'viewer-mutation@example.com');
        $level = VocabularyLevel::create(['title' => 'Read Only', 'created_by_user_id' => $owner->id, 'visibility' => 'shared']);
        $word = $level->words()->create(['word' => 'dog', 'translation' => 'perro']);
        $token = $this->token($viewer);

        $this->withToken($token)->patchJson("/api/vocabulary/levels/{$level->id}", ['title' => 'Changed'])->assertForbidden();
        $this->withToken($token)->deleteJson("/api/vocabulary/levels/{$level->id}")->assertForbidden();
        $this->withToken($token)->postJson("/api/vocabulary/levels/{$level->id}/words", ['word' => 'cat', 'translation' => 'gato'])->assertForbidden();
        $this->withToken($token)->patchJson("/api/vocabulary/words/{$word->id}", ['word' => 'changed'])->assertForbidden();
        $this->withToken($token)->deleteJson("/api/vocabulary/words/{$word->id}")->assertForbidden();
    }

    public function test_shared_assignment_survives_unshare_and_new_private_assignment_is_blocked(): void
    {
        $owner = $this->user('teacher', 'owner-assignment@example.com');
        $consumer = $this->user('teacher', 'consumer-assignment@example.com');
        $third = $this->user('teacher', 'third-assignment@example.com');
        $consumerClass = SchoolClass::create(['teacher_id' => $consumer->teacher->id, 'name' => 'Consumer Class']);
        $thirdClass = SchoolClass::create(['teacher_id' => $third->teacher->id, 'name' => 'Third Class']);
        $level = VocabularyLevel::create(['title' => 'Reusable', 'created_by_user_id' => $owner->id, 'visibility' => 'shared']);

        $this->withToken($this->token($consumer))->postJson("/api/classes/{$consumerClass->id}/vocabulary-levels", [
            'vocabulary_level_id' => $level->id,
        ])->assertCreated();

        $this->actingAs($owner)->patchJson("/api/vocabulary/levels/{$level->id}", ['visibility' => 'private'])->assertOk();
        $this->assertDatabaseHas('class_vocabulary_levels', ['class_id' => $consumerClass->id, 'vocabulary_level_id' => $level->id]);

        $this->withToken($this->token($third))->postJson("/api/classes/{$thirdClass->id}/vocabulary-levels", [
            'vocabulary_level_id' => $level->id,
        ])->assertForbidden();
    }

    public function test_shared_set_requires_class_assignment_for_student_access(): void
    {
        $owner = $this->user('teacher', 'owner-student@example.com');
        $student = $this->user('student', 'student-sharing@example.com');
        $class = SchoolClass::create(['teacher_id' => $owner->teacher->id, 'name' => 'Assigned Class']);
        $level = VocabularyLevel::create(['title' => 'Student Shared', 'created_by_user_id' => $owner->id, 'visibility' => 'shared']);
        $token = $this->token($student);

        $this->withToken($token)->getJson("/api/vocabulary/levels/{$level->id}")->assertForbidden();

        $class->students()->attach($student->student->id, ['status' => 'active', 'enrolled_at' => now()]);
        $class->vocabularyLevels()->attach($level->id);

        $this->withToken($token)->getJson("/api/vocabulary/levels/{$level->id}")->assertOk();
    }

    public function test_owner_cannot_delete_set_used_by_another_teachers_class(): void
    {
        $owner = $this->user('teacher', 'owner-delete@example.com');
        $consumer = $this->user('teacher', 'consumer-delete@example.com');
        $class = SchoolClass::create(['teacher_id' => $consumer->teacher->id, 'name' => 'External Class']);
        $level = VocabularyLevel::create(['title' => 'In Use', 'created_by_user_id' => $owner->id, 'visibility' => 'shared']);
        $class->vocabularyLevels()->attach($level->id);

        $this->withToken($this->token($owner))->deleteJson("/api/vocabulary/levels/{$level->id}")
            ->assertStatus(409);
        $this->assertDatabaseHas('vocabulary_levels', ['id' => $level->id]);
    }

    public function test_admin_can_manage_teacher_owned_set(): void
    {
        $owner = $this->user('teacher', 'owner-admin@example.com');
        $admin = $this->user('admin', 'admin-sharing@example.com');
        $level = VocabularyLevel::create(['title' => 'Admin Managed', 'created_by_user_id' => $owner->id]);

        $this->withToken($this->token($admin))->patchJson("/api/vocabulary/levels/{$level->id}", [
            'visibility' => 'shared',
        ])->assertOk()->assertJsonPath('data.visibility', 'shared');

        $this->withToken($this->token($admin))->postJson("/api/vocabulary/levels/{$level->id}/words", [
            'word' => 'cat',
            'translation' => 'gato',
        ])->assertCreated();
    }
}
