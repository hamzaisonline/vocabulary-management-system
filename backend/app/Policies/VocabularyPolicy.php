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
        if ($user->role?->name === 'admin') {
            return true;
        }

        if ($user->role?->name === 'teacher') {
            return $vocabularyLevel->created_by_user_id === null
                || $vocabularyLevel->created_by_user_id === $user->id
                || $vocabularyLevel->visibility === 'shared';
        }

        return $user->role?->name === 'student'
            && ($vocabularyLevel->created_by_user_id === null || $vocabularyLevel->schoolClasses()
                ->whereHas('students', fn ($query) => $query->where('students.id', $user->student?->id))
                ->exists());
    }

    public function create(User $user): bool
    {
        return $user->role && in_array($user->role->name, ['admin', 'teacher'], true);
    }

    public function update(User $user, VocabularyLevel $vocabularyLevel): bool
    {
        return $user->role?->name === 'admin'
            || ($user->role?->name === 'teacher' && (
                $vocabularyLevel->created_by_user_id === null
                || $vocabularyLevel->created_by_user_id === $user->id
            ));
    }

    public function delete(User $user, VocabularyLevel $vocabularyLevel): bool
    {
        return $this->update($user, $vocabularyLevel);
    }

    public function createWord(User $user, VocabularyLevel $vocabularyLevel): bool
    {
        return $this->update($user, $vocabularyLevel);
    }

    public function updateWord(User $user, VocabularyWord $vocabularyWord): bool
    {
        return $this->update($user, $vocabularyWord->vocabularyLevel);
    }

    public function deleteWord(User $user, VocabularyWord $vocabularyWord): bool
    {
        return $this->update($user, $vocabularyWord->vocabularyLevel);
    }
}
