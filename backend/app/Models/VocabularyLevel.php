<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VocabularyLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'difficulty',
    ];

    public function words(): HasMany
    {
        return $this->hasMany(VocabularyWord::class);
    }

    public function schoolClasses(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'class_vocabulary_levels', 'vocabulary_level_id', 'class_id')
            ->withTimestamps();
    }
}
