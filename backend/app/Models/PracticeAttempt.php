<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticeAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'practice_session_id',
        'vocabulary_word_id',
        'submitted_answer',
        'is_correct',
        'attempted_at',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'attempted_at' => 'datetime',
    ];

    public function practiceSession(): BelongsTo
    {
        return $this->belongsTo(PracticeSession::class);
    }

    public function vocabularyWord(): BelongsTo
    {
        return $this->belongsTo(VocabularyWord::class);
    }
}
