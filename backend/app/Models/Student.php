<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schoolClasses(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'class_enrollments', 'student_id', 'class_id')
            ->withPivot(['status', 'enrolled_at'])
            ->withTimestamps();
    }

    public function wordProgress(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StudentWordProgress::class);
    }
}
