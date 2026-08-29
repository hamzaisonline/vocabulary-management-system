<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVocabularyLevelRequest;
use App\Http\Requests\StoreVocabularyWordRequest;
use App\Http\Requests\UpdateVocabularyLevelRequest;
use App\Http\Requests\UpdateVocabularyWordRequest;
use App\Http\Resources\VocabularyLevelResource;
use App\Http\Resources\VocabularyWordResource;
use App\Models\VocabularyLevel;
use App\Models\VocabularyWord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $word = $vocabularyLevel->words()->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Vocabulary word created successfully.',
            'data' => new VocabularyWordResource($word),
        ], 201);
    }

    public function updateWord(UpdateVocabularyWordRequest $request, VocabularyWord $vocabularyWord)
    {
        $this->authorize('updateWord', $vocabularyWord);

        $vocabularyWord->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Vocabulary word updated successfully.',
            'data' => new VocabularyWordResource($vocabularyWord->fresh()),
        ]);
    }

    public function destroyWord(VocabularyWord $vocabularyWord)
    {
        $this->authorize('deleteWord', $vocabularyWord);

        $vocabularyWord->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vocabulary word deleted successfully.',
        ]);
    }
}
