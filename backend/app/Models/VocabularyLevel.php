<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
}
