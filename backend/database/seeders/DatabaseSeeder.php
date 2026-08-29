<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'admin', 'description' => 'System administrator'],
            ['name' => 'teacher', 'description' => 'Class teacher'],
            ['name' => 'student', 'description' => 'Learner user'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name']],
                ['description' => $role['description']]
            );
        }

        $demoUsers = [
            ['name' => 'Admin Demo', 'email' => 'admin@example.com', 'role' => 'admin'],
            ['name' => 'Teacher Demo', 'email' => 'teacher@example.com', 'role' => 'teacher'],
            ['name' => 'Student Demo', 'email' => 'student@example.com', 'role' => 'student'],
        ];

        foreach ($demoUsers as $demoUser) {
            $role = Role::where('name', $demoUser['role'])->first();

            $user = User::firstOrCreate(
                ['email' => $demoUser['email']],
                [
                    'name' => $demoUser['name'],
                    'password' => Hash::make('password'),
                    'role_id' => $role->id,
                ]
            );

            if ($demoUser['role'] === 'student') {
                Student::firstOrCreate(['user_id' => $user->id]);
            }

            if ($demoUser['role'] === 'teacher') {
                Teacher::firstOrCreate(['user_id' => $user->id]);
            }
        }

        $teacher = User::where('email', 'teacher@example.com')->first();
        $student = User::where('email', 'student@example.com')->first();

        if ($teacher && $teacher->teacher) {
            $class = SchoolClass::firstOrCreate(
                ['teacher_id' => $teacher->teacher->id, 'name' => 'Welcome Class'],
                ['description' => 'Demo class', 'language' => 'en']
            );

            if ($student && $student->student) {
                $class->students()->syncWithoutDetaching([
                    $student->student->id => [
                        'status' => 'active',
                        'enrolled_at' => now(),
                    ],
                ]);
            }
        }

        $this->call(VocabularySeeder::class);

        $level = \App\Models\VocabularyLevel::where('title', 'Pets')->first();
        $class = SchoolClass::where('name', 'Welcome Class')->first();

        if ($level && $class) {
            $class->vocabularyLevels()->syncWithoutDetaching([$level->id]);
        }
    }
}
