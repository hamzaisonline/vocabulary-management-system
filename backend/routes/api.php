<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SchoolClassController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/classes', [SchoolClassController::class, 'index']);
    Route::post('/classes', [SchoolClassController::class, 'store']);
    Route::get('/classes/{schoolClass}', [SchoolClassController::class, 'show']);
    Route::match(['put', 'patch'], '/classes/{schoolClass}', [SchoolClassController::class, 'update']);
    Route::delete('/classes/{schoolClass}', [SchoolClassController::class, 'destroy']);
    Route::post('/classes/{schoolClass}/students', [SchoolClassController::class, 'enrollStudent']);
    Route::delete('/classes/{schoolClass}/students/{student}', [SchoolClassController::class, 'removeStudent']);

    Route::get('/vocabulary/levels', [\App\Http\Controllers\VocabularyController::class, 'indexLevels']);
    Route::get('/vocabulary/levels/{vocabularyLevel}', [\App\Http\Controllers\VocabularyController::class, 'showLevel']);
    Route::post('/vocabulary/levels', [\App\Http\Controllers\VocabularyController::class, 'storeLevel']);
    Route::match(['put', 'patch'], '/vocabulary/levels/{vocabularyLevel}', [\App\Http\Controllers\VocabularyController::class, 'updateLevel']);
    Route::delete('/vocabulary/levels/{vocabularyLevel}', [\App\Http\Controllers\VocabularyController::class, 'destroyLevel']);
    Route::post('/vocabulary/levels/{vocabularyLevel}/words', [\App\Http\Controllers\VocabularyController::class, 'storeWord']);
    Route::match(['put', 'patch'], '/vocabulary/words/{vocabularyWord}', [\App\Http\Controllers\VocabularyController::class, 'updateWord']);
    Route::delete('/vocabulary/words/{vocabularyWord}', [\App\Http\Controllers\VocabularyController::class, 'destroyWord']);

    Route::get('/classes/{schoolClass}/vocabulary-levels', [\App\Http\Controllers\ClassVocabularyController::class, 'index']);
    Route::post('/classes/{schoolClass}/vocabulary-levels', [\App\Http\Controllers\ClassVocabularyController::class, 'attach']);
    Route::delete('/classes/{schoolClass}/vocabulary-levels/{vocabularyLevel}', [\App\Http\Controllers\ClassVocabularyController::class, 'detach']);
    Route::get('/student/vocabulary-levels', [\App\Http\Controllers\ClassVocabularyController::class, 'studentVocabulary']);
    Route::get('/student/progress', [\App\Http\Controllers\StudentProgressController::class, 'index']);
    Route::get('/student/vocabulary-levels/{vocabularyLevel}/progress', [\App\Http\Controllers\StudentProgressController::class, 'levelProgress']);
    Route::post('/student/vocabulary-words/{vocabularyWord}/progress', [\App\Http\Controllers\StudentProgressController::class, 'updateWordProgress']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
