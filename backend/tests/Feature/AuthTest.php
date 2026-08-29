<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $roles = [
            'admin' => 'System administrator',
            'teacher' => 'Class teacher',
            'student' => 'Learner user',
        ];

        foreach ($roles as $name => $description) {
            Role::firstOrCreate([
                'name' => $name,
            ], [
                'description' => $description,
            ]);
        }
    }

    public function test_student_registration_succeeds(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Example User',
            'email' => 'student@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.role.name', 'student');

        $this->assertDatabaseHas('users', ['email' => 'student@example.com']);
        $this->assertTrue(Hash::check('password', User::first()->password));
    }

    public function test_duplicate_email_registration_fails(): void
    {
        User::factory()->create([
            'email' => 'duplicate@example.com',
        ]);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Another User',
            'email' => 'duplicate@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_cannot_choose_admin_role(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Admin Attempt',
            'email' => 'admin-attempt@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    public function test_valid_login_succeeds(): void
    {
        $role = Role::where('name', 'student')->firstOrFail();

        $user = User::factory()->create([
            'name' => 'Student User',
            'email' => 'login@example.com',
            'role_id' => $role->id,
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', $user->email)
            ->assertJsonPath('data.user.role.name', 'student')
            ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_invalid_credentials_return_401(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'no-user@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
    }

    public function test_unauthenticated_me_request_fails(): void
    {
        $this->getJson('/api/auth/me')->assertStatus(401);
    }

    public function test_authenticated_me_succeeds(): void
    {
        $role = Role::where('name', 'student')->firstOrFail();
        $user = User::factory()->create([
            'role_id' => $role->id,
            'email' => 'me@example.com',
            'password' => Hash::make('password'),
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', $user->email)
            ->assertJsonPath('data.user.role.name', 'student');
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $role = Role::where('name', 'student')->firstOrFail();
        $user = User::factory()->create([
            'role_id' => $role->id,
            'email' => 'logout@example.com',
            'password' => Hash::make('password'),
        ]);

        $token = $user->createToken('logout-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertStatus(200);

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
            'name' => 'logout-token',
        ]);

        Auth::guard('sanctum')->setUser(null);

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertStatus(401);
    }
}
