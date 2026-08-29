<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $roles = [
            ['name' => 'admin', 'description' => 'System administrator'],
            ['name' => 'teacher', 'description' => 'Class teacher'],
            ['name' => 'student', 'description' => 'Learner user'],
        ];

        foreach ($roles as $role) {
            \App\Models\Role::firstOrCreate(
                ['name' => $role['name']],
                ['description' => $role['description']]
            );
        }
    }
}
