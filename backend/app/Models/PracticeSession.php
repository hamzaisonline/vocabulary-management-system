<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PracticeSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'vocabulary_level_id',
        'started_at',
        'completed_at',
        'total_questions',
        'correct_answers',
        'score_percent',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function vocabularyLevel(): BelongsTo
    {
        return $this->belongsTo(VocabularyLevel::class);
    }

    public function practiceAttempts(): HasMany
    {
        return $this->hasMany(PracticeAttempt::class);
    }
}
