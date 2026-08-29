<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VocabularyWord extends Model
{
    use HasFactory;

    protected $fillable = [
        'vocabulary_level_id',
        'word',
        'translation',
        'example',
        'notes',
        'audio_path',
    ];

    public function vocabularyLevel(): BelongsTo
    {
        return $this->belongsTo(VocabularyLevel::class);
    }

    public function studentProgress(): HasMany
    {
        return $this->hasMany(StudentWordProgress::class);
    }
}
