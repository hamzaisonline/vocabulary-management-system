<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVocabularyLevelRequest;
use App\Http\Requests\StoreVocabularyWordRequest;
use App\Http\Requests\ImportVocabularyWordsRequest;
use App\Http\Requests\UpdateVocabularyLevelRequest;
use App\Http\Requests\UpdateVocabularyWordRequest;
use App\Http\Resources\VocabularyLevelResource;
use App\Http\Resources\VocabularyWordResource;
use App\Models\VocabularyLevel;
use App\Models\VocabularyWord;
use App\Services\VocabularyImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VocabularyController extends Controller
{
    public function indexLevels(Request $request)
    {
        $this->authorize('viewAny', VocabularyLevel::class);

        $user = $request->user();
        $scope = $request->query('scope', $user->role?->name === 'admin' ? 'all' : 'mine');

        $levels = VocabularyLevel::query()
            ->when($user->role?->name === 'teacher', function ($query) use ($user, $scope) {
                if ($scope === 'shared') {
                    $query->where('visibility', 'shared')->where('created_by_user_id', '!=', $user->id);
                } elseif ($scope === 'all') {
                    $query->where(fn ($accessible) => $accessible
                        ->where('created_by_user_id', $user->id)
                        ->orWhereNull('created_by_user_id')
                        ->orWhere('visibility', 'shared'));
                } else {
                    $query->where(fn ($mine) => $mine
                        ->where('created_by_user_id', $user->id)
                        ->orWhereNull('created_by_user_id'));
                }
            })
            ->when($user->role?->name === 'student', fn ($query) => $query->where(fn ($accessible) => $accessible
                ->whereNull('created_by_user_id')
                ->orWhereHas('schoolClasses.students', fn ($students) => $students
                    ->where('students.id', $user->student?->id))))
            ->with('owner:id,name')
            ->withCount('words')
            ->get();

        return response()->json([
            'success' => true,
            'data' => VocabularyLevelResource::collection($levels),
        ]);
    }

    public function showLevel(VocabularyLevel $vocabularyLevel)
    {
        $this->authorize('view', $vocabularyLevel);

        $vocabularyLevel->load(['words', 'owner:id,name']);

        return response()->json([
            'success' => true,
            'data' => new VocabularyLevelResource($vocabularyLevel),
        ]);
    }

    public function storeLevel(StoreVocabularyLevelRequest $request)
    {
        $this->authorize('create', VocabularyLevel::class);

        $data = $request->safe()->except('created_by_user_id');
        $data['created_by_user_id'] = $request->user()->id;
        $data['visibility'] ??= 'private';
        $level = VocabularyLevel::create($data);
        $level->load('owner:id,name');

        return response()->json([
            'success' => true,
            'message' => 'Vocabulary level created successfully.',
            'data' => new VocabularyLevelResource($level),
        ], 201);
    }

    public function updateLevel(UpdateVocabularyLevelRequest $request, VocabularyLevel $vocabularyLevel)
    {
        $this->authorize('update', $vocabularyLevel);

        $vocabularyLevel->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Vocabulary level updated successfully.',
            'data' => new VocabularyLevelResource($vocabularyLevel->fresh()->load('owner:id,name')),
        ]);
    }

    public function destroyLevel(VocabularyLevel $vocabularyLevel)
    {
        $this->authorize('delete', $vocabularyLevel);

        $usedByOtherClasses = $vocabularyLevel->schoolClasses()
            ->when(request()->user()->role?->name === 'teacher', fn ($query) => $query
                ->where('teacher_id', '!=', request()->user()->teacher?->id))
            ->exists();

        if ($usedByOtherClasses) {
            return response()->json([
                'success' => false,
                'message' => 'This vocabulary set is currently used by other classes and cannot be deleted.',
            ], 409);
        }

        $vocabularyLevel->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vocabulary level deleted successfully.',
        ]);
    }

    public function storeWord(StoreVocabularyWordRequest $request, VocabularyLevel $vocabularyLevel)
    {
        $this->authorize('createWord', $vocabularyLevel);

        $data = $request->safe()->except('audio');

        if ($request->hasFile('audio')) {
            $data['audio_path'] = $request->file('audio')->store('vocabulary/audio', 'public');
        }

        $word = $vocabularyLevel->words()->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Vocabulary word created successfully.',
            'data' => new VocabularyWordResource($word),
        ], 201);
    }

    public function importWords(
        ImportVocabularyWordsRequest $request,
        VocabularyLevel $vocabularyLevel,
        VocabularyImportService $importService
    ) {
        $this->authorize('createWord', $vocabularyLevel);

        $summary = $importService->import($vocabularyLevel, $request->file('file'));

        return response()->json([
            'success' => true,
            'message' => 'Vocabulary import completed.',
            'data' => $summary,
        ]);
    }

    public function updateWord(UpdateVocabularyWordRequest $request, VocabularyWord $vocabularyWord)
    {
        $this->authorize('updateWord', $vocabularyWord);

        $data = $request->safe()->except('audio');
        $previousAudioPath = $vocabularyWord->audio_path;

        if ($request->hasFile('audio')) {
            $data['audio_path'] = $request->file('audio')->store('vocabulary/audio', 'public');
        }

        $vocabularyWord->update($data);

        if ($request->hasFile('audio')) {
            $this->deleteManagedAudio($previousAudioPath);
        }

        return response()->json([
            'success' => true,
            'message' => 'Vocabulary word updated successfully.',
            'data' => new VocabularyWordResource($vocabularyWord->fresh()),
        ]);
    }

    public function destroyWord(VocabularyWord $vocabularyWord)
    {
        $this->authorize('deleteWord', $vocabularyWord);

        $audioPath = $vocabularyWord->audio_path;
        $vocabularyWord->delete();
        $this->deleteManagedAudio($audioPath);

        return response()->json([
            'success' => true,
            'message' => 'Vocabulary word deleted successfully.',
        ]);
    }

    private function deleteManagedAudio(?string $path): void
    {
        if ($path && str_starts_with($path, 'vocabulary/audio/')) {
            Storage::disk('public')->delete($path);
        }
    }
}
