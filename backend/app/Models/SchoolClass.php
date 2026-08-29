<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SchoolClass extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'teacher_id',
        'name',
        'description',
        'language',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'class_enrollments', 'class_id', 'student_id')
            ->withPivot(['status', 'enrolled_at'])
            ->withTimestamps();
    }

    public function vocabularyLevels(): BelongsToMany
    {
        return $this->belongsToMany(VocabularyLevel::class, 'class_vocabulary_levels', 'class_id', 'vocabulary_level_id')
            ->withTimestamps();
    }
}
