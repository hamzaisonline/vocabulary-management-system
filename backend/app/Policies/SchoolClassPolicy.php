<?php

namespace App\Policies;

use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SchoolClassPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->role && in_array($user->role->name, ['admin', 'teacher', 'student'], true);
    }

    public function view(User $user, SchoolClass $schoolClass): bool
    {
        if ($user->role && $user->role->name === 'admin') {
            return true;
        }

        if ($user->role && $user->role->name === 'teacher') {
            return $schoolClass->teacher_id === $user->teacher?->id;
        }

        if ($user->role && $user->role->name === 'student') {
            return $schoolClass->students()->where('students.id', $user->student?->id)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->role && in_array($user->role->name, ['admin', 'teacher'], true);
    }

    public function update(User $user, SchoolClass $schoolClass): bool
    {
        if ($user->role && $user->role->name === 'admin') {
            return true;
        }

        return $user->role && $user->role->name === 'teacher'
            && $user->teacher?->id === $schoolClass->teacher_id;
    }

    public function delete(User $user, SchoolClass $schoolClass): bool
    {
        return $this->update($user, $schoolClass);
    }

    public function enrollStudent(User $user, SchoolClass $schoolClass): bool
    {
        return $this->update($user, $schoolClass);
    }

    public function removeStudent(User $user, SchoolClass $schoolClass): bool
    {
        return $this->update($user, $schoolClass);
    }
}
