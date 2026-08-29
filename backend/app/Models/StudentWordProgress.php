<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentWordProgress extends Model
{
    use HasFactory;

    protected $table = 'student_word_progress';

    protected $fillable = [
        'student_id',
        'vocabulary_word_id',
        'mastery_percent',
        'attempts',
        'correct_attempts',
        'last_practiced_at',
        'completed_at',
    ];

    protected $casts = [
        'last_practiced_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function vocabularyWord(): BelongsTo
    {
        return $this->belongsTo(VocabularyWord::class);
    }
}
