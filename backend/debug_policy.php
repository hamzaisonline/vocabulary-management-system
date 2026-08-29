<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--seed' => false]);

$role = App\Models\Role::firstOrCreate(['name' => 'teacher'], ['description' => 'teacher']);
$user = App\Models\User::factory()->create(['role_id' => $role->id, 'email' => 'debug-policy@example.com']);
$teacher = App\Models\Teacher::create(['user_id' => $user->id]);
$class = App\Models\SchoolClass::create(['teacher_id' => $teacher->id, 'name' => 'Debug Class']);

var_dump([
    'user_id' => $user->id,
    'teacher_id' => $teacher->id,
    'class_teacher_id' => $class->teacher_id,
    'teacher_rel_id' => $user->teacher?->id,
    'role' => $user->role?->name,
    'policy_update' => (new App\Policies\SchoolClassPolicy)->update($user, $class),
    'policy_view' => (new App\Policies\SchoolClassPolicy)->view($user, $class),
]);
