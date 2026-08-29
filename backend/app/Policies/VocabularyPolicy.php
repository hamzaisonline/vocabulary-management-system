<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VocabularyLevel;
use App\Models\VocabularyWord;
use Illuminate\Auth\Access\HandlesAuthorization;

class VocabularyPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->role && in_array($user->role->name, ['admin', 'teacher', 'student'], true);
    }

    public function view(User $user, VocabularyLevel $vocabularyLevel): bool
    {
        return $user->role && in_array($user->role->name, ['admin', 'teacher', 'student'], true);
    }

    public function create(User $user): bool
    {
        return $user->role && in_array($user->role->name, ['admin', 'teacher'], true);
    }

    public function update(User $user, VocabularyLevel $vocabularyLevel): bool
    {
        return $user->role && in_array($user->role->name, ['admin', 'teacher'], true);
    }

    public function delete(User $user, VocabularyLevel $vocabularyLevel): bool
    {
        return $user->role && in_array($user->role->name, ['admin', 'teacher'], true);
    }

    public function createWord(User $user, VocabularyLevel $vocabularyLevel): bool
    {
        return $user->role && in_array($user->role->name, ['admin', 'teacher'], true);
    }

    public function updateWord(User $user, VocabularyWord $vocabularyWord): bool
    {
        return $user->role && in_array($user->role->name, ['admin', 'teacher'], true);
    }

    public function deleteWord(User $user, VocabularyWord $vocabularyWord): bool
    {
        return $user->role && in_array($user->role->name, ['admin', 'teacher'], true);
    }
}
