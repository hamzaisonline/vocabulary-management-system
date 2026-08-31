<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignVocabularyLevelRequest;
use App\Models\SchoolClass;
use App\Models\User;
use App\Models\VocabularyLevel;
use Illuminate\Http\Request;

class ClassVocabularyController extends Controller
{
    public function index(SchoolClass $schoolClass, Request $request)
    {
        $user = $request->user();

        if ($user->role?->name === 'admin') {
            $classes = collect([$schoolClass]);
        } elseif ($user->role?->name === 'teacher') {
            abort_unless($user->teacher?->id === $schoolClass->teacher_id, 403);
            $classes = collect([$schoolClass]);
        } elseif ($user->role?->name === 'student') {
            abort_unless($schoolClass->students()->where('students.id', $user->student?->id)->exists(), 403);
            $classes = collect([$schoolClass]);
        } else {
            abort(403);
        }

        $levels = $schoolClass->vocabularyLevels()
            ->with('owner:id,name')
            ->withCount('words')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $levels->map(fn ($level) => [
                'id' => $level->id,
                'title' => $level->title,
                'description' => $level->description,
                'difficulty' => $level->difficulty,
                'stage' => $level->stage,
                'visibility' => $level->visibility,
                'created_by_user_id' => $level->created_by_user_id,
                'is_owner' => $level->created_by_user_id === $user->id,
                'owner' => $level->owner ? ['id' => $level->owner->id, 'name' => $level->owner->name] : null,
                'word_count' => $level->words_count,
            ]),
        ]);
    }

    public function attach(AssignVocabularyLevelRequest $request, SchoolClass $schoolClass)
    {
        $levelId = $request->validated('vocabulary_level_id');
        $level = VocabularyLevel::findOrFail($levelId);
        $user = $request->user();

        if ($user->role?->name !== 'admin') {
            abort_unless(
                $level->created_by_user_id === null
                    || $level->created_by_user_id === $user->id
                    || $level->visibility === 'shared',
                403
            );
        }

        if ($schoolClass->vocabularyLevels()->where('vocabulary_levels.id', $levelId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This vocabulary level is already assigned to the class.',
            ], 422);
        }

        $schoolClass->vocabularyLevels()->attach($levelId);

        $level->load('owner:id,name')->loadCount('words');

        return response()->json([
            'success' => true,
            'message' => 'Vocabulary level assigned to class.',
            'data' => [
                'class_id' => $schoolClass->id,
                'vocabulary_level_id' => $level->id,
                'title' => $level->title,
                'description' => $level->description,
                'difficulty' => $level->difficulty,
                'stage' => $level->stage,
                'visibility' => $level->visibility,
                'created_by_user_id' => $level->created_by_user_id,
                'is_owner' => $level->created_by_user_id === $user->id,
                'owner' => $level->owner ? ['id' => $level->owner->id, 'name' => $level->owner->name] : null,
                'word_count' => $level->words_count,
            ],
        ], 201);
    }

    public function detach(SchoolClass $schoolClass, VocabularyLevel $vocabularyLevel)
    {
        $user = request()->user();

        if ($user->role?->name === 'admin') {
            // allowed
        } elseif ($user->role?->name === 'teacher') {
            abort_unless($user->teacher?->id === $schoolClass->teacher_id, 403);
        } else {
            abort(403);
        }

        $schoolClass->vocabularyLevels()->detach($vocabularyLevel->id);

        return response()->json([
            'success' => true,
            'message' => 'Vocabulary level assignment removed.',
            'data' => [
                'class_id' => $schoolClass->id,
                'vocabulary_level_id' => $vocabularyLevel->id,
            ],
        ]);
    }

    public function studentVocabulary(Request $request)
    {
        $user = $request->user();

        abort_unless($user->role?->name === 'student', 403);

        $levels = VocabularyLevel::query()
            ->whereHas('schoolClasses.students', function ($query) use ($user) {
                $query->where('students.id', $user->student?->id);
            })
            ->withCount('words')
            ->distinct()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $levels->map(fn ($level) => [
                'id' => $level->id,
                'title' => $level->title,
                'description' => $level->description,
                'difficulty' => $level->difficulty,
                'stage' => $level->stage,
                'word_count' => $level->words_count,
            ]),
        ]);
    }
}
