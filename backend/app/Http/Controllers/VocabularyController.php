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

        $levels = VocabularyLevel::query()
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

        $vocabularyLevel->load('words');

        return response()->json([
            'success' => true,
            'data' => new VocabularyLevelResource($vocabularyLevel),
        ]);
    }

    public function storeLevel(StoreVocabularyLevelRequest $request)
    {
        $this->authorize('create', VocabularyLevel::class);

        $level = VocabularyLevel::create($request->validated());

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
            'data' => new VocabularyLevelResource($vocabularyLevel->fresh()),
        ]);
    }

    public function destroyLevel(VocabularyLevel $vocabularyLevel)
    {
        $this->authorize('delete', $vocabularyLevel);

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
