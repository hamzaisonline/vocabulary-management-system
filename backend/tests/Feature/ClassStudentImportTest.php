<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ClassStudentImportTest extends TestCase
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
        $user = User::factory()->create(['email' => $email, 'role_id' => Role::where('name', $role)->value('id')]);
        if ($role === 'teacher') Teacher::create(['user_id' => $user->id]);
        if ($role === 'student') Student::create(['user_id' => $user->id]);
        return $user->fresh();
    }

    private function schoolClass(User $teacher, string $name = 'Import Class'): SchoolClass
    {
        return SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => $name]);
    }

    private function csv(string $contents): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('students.csv', $contents);
    }

    private function import(User $user, SchoolClass $class, string $csv)
    {
        return $this->withToken($user->createToken('test')->plainTextToken)
            ->withHeader('Accept', 'application/json')
            ->post('/api/classes/'.$class->id.'/students/import', ['file' => $this->csv($csv)]);
    }

    public function test_teacher_can_create_and_enroll_students_in_own_class(): void
    {
        $teacher = $this->user('teacher', 'owner@example.com');
        $class = $this->schoolClass($teacher);

        $this->import($teacher, $class, "name,email,password\nNew Student,NEW@EXAMPLE.COM,student123")
            ->assertOk()
            ->assertJsonPath('data.created', 1);

        $user = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertSame('student', $user->role->name);
        $this->assertNotNull($user->student);
        $this->assertDatabaseHas('class_enrollments', ['class_id' => $class->id, 'student_id' => $user->student->id]);
    }

    public function test_teacher_cannot_import_into_another_teachers_class(): void
    {
        $teacher = $this->user('teacher', 'first@example.com');
        $other = $this->user('teacher', 'second@example.com');

        $this->import($teacher, $this->schoolClass($other), "name,email,password\nStudent,blocked@example.com,student123")
            ->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'blocked@example.com']);
    }

    public function test_admin_can_import_into_any_class(): void
    {
        $teacher = $this->user('teacher', 'class-owner@example.com');
        $admin = $this->user('admin', 'admin@example.com');
        $class = $this->schoolClass($teacher);

        $this->import($admin, $class, "name,email,password\nAdmin Added,admin-added@example.com,student123")
            ->assertOk()->assertJsonPath('data.created', 1);
    }

    public function test_student_cannot_import_students(): void
    {
        $teacher = $this->user('teacher', 'student-block-owner@example.com');
        $student = $this->user('student', 'requester@example.com');

        $this->import($student, $this->schoolClass($teacher), "name,email,password\nBlocked,student-blocked@example.com,student123")
            ->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'student-blocked@example.com']);
    }

    public function test_existing_student_is_enrolled_and_already_enrolled_is_skipped(): void
    {
        $teacher = $this->user('teacher', 'existing-owner@example.com');
        $student = $this->user('student', 'existing@example.com');
        $class = $this->schoolClass($teacher);

        $this->import($teacher, $class, "name,email,password\n,existing@example.com,")
            ->assertOk()->assertJsonPath('data.enrolled_existing', 1);
        $this->import($teacher, $class, "name,email,password\n,existing@example.com,")
            ->assertOk()->assertJsonPath('data.already_enrolled', 1);
    }

    public function test_duplicate_rows_non_student_accounts_and_malformed_rows_are_reported(): void
    {
        $teacher = $this->user('teacher', 'validation-owner@example.com');
        $this->user('admin', 'reserved@example.com');
        $class = $this->schoolClass($teacher);
        $csv = "name,email,password\n"
            . "Valid,valid@example.com,student123\n"
            . "Duplicate,valid@example.com,student123\n"
            . "Reserved,reserved@example.com,\n"
            . "Bad,bad-email,student123\n"
            . ",missing-name@example.com,";

        $this->import($teacher, $class, $csv)
            ->assertOk()
            ->assertJsonPath('data.created', 1)
            ->assertJsonPath('data.failed', 4)
            ->assertJsonCount(4, 'data.errors');
        $this->assertDatabaseMissing('users', ['email' => 'missing-name@example.com']);
    }

    public function test_generated_password_is_returned_once_for_distribution(): void
    {
        $teacher = $this->user('teacher', 'password-owner@example.com');

        $this->import($teacher, $this->schoolClass($teacher), "name,email,password\nPassword Student,password-student@example.com,")
            ->assertOk()
            ->assertJsonPath('data.created', 1)
            ->assertJsonPath('data.temporary_passwords.0.email', 'password-student@example.com')
            ->assertJsonStructure(['data' => ['temporary_passwords' => [['email', 'password']]]]);
    }

    public function test_utf8_bom_and_names_are_preserved(): void
    {
        $teacher = $this->user('teacher', 'unicode-owner@example.com');
        $csv = "\xEF\xBB\xBFname,email,password\nJosé García,jose@example.com,student123\nFrançois Dupont,francois@example.com,student123\nŁukasz Müller,lukasz@example.com,student123\nSofía,sofia@example.com,student123";

        $this->import($teacher, $this->schoolClass($teacher), $csv)
            ->assertOk()->assertJsonPath('data.created', 4);
        foreach (['José García', 'François Dupont', 'Łukasz Müller', 'Sofía'] as $name) {
            $this->assertDatabaseHas('users', ['name' => $name]);
        }
    }

    public function test_missing_headers_are_rejected_without_partial_state(): void
    {
        $teacher = $this->user('teacher', 'header-owner@example.com');

        $this->import($teacher, $this->schoolClass($teacher), "name,email\nPartial,partial@example.com")
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');
        $this->assertDatabaseMissing('users', ['email' => 'partial@example.com']);
    }
}
