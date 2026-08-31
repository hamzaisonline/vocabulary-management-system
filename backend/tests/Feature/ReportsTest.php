<?php

namespace Tests\Feature;

use App\Models\PracticeSession;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentWordProgress;
use App\Models\Teacher;
use App\Models\User;
use App\Models\VocabularyLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'teacher', 'student'] as $role) {
            Role::firstOrCreate(['name' => $role], ['description' => $role]);
        }
    }

    private function user(string $role, string $name): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'@example.com',
            'role_id' => Role::where('name', $role)->value('id'),
        ]);

        if ($role === 'teacher') {
            Teacher::create(['user_id' => $user->id]);
        } elseif ($role === 'student') {
            Student::create(['user_id' => $user->id]);
        }

        return $user->fresh();
    }

    private function enroll(User $teacher, User $student, VocabularyLevel $level, string $name): SchoolClass
    {
        $class = SchoolClass::create(['teacher_id' => $teacher->teacher->id, 'name' => $name]);
        $class->students()->attach($student->student->id, ['status' => 'active', 'enrolled_at' => now()]);
        $class->vocabularyLevels()->attach($level->id);

        return $class;
    }

    public function test_teacher_report_returns_deduplicated_levels_and_scoped_student_aggregates(): void
    {
        $teacher = $this->user('teacher', 'Owner Teacher');
        $otherTeacher = $this->user('teacher', 'Other Teacher');
        $student = $this->user('student', 'Owned Student');
        $otherStudent = $this->user('student', 'Hidden Student');
        $student->student->forceFill(['total_xp' => 80])->save();
        $otherStudent->student->forceFill(['total_xp' => 999])->save();

        $level = VocabularyLevel::create(['title' => 'Animals']);
        $wordOne = $level->words()->create(['word' => 'dog', 'translation' => 'perro']);
        $wordTwo = $level->words()->create(['word' => 'cat', 'translation' => 'gato']);
        $this->enroll($teacher, $student, $level, 'First Owned Class');
        $this->enroll($teacher, $student, $level, 'Second Owned Class');

        $hiddenLevel = VocabularyLevel::create(['title' => 'Hidden Level']);
        $hiddenWord = $hiddenLevel->words()->create(['word' => 'hidden', 'translation' => 'oculto']);
        $this->enroll($otherTeacher, $otherStudent, $hiddenLevel, 'Other Class');

        StudentWordProgress::create(['student_id' => $student->student->id, 'vocabulary_word_id' => $wordOne->id, 'mastery_percent' => 100]);
        StudentWordProgress::create(['student_id' => $student->student->id, 'vocabulary_word_id' => $wordTwo->id, 'mastery_percent' => 50]);
        StudentWordProgress::create(['student_id' => $otherStudent->student->id, 'vocabulary_word_id' => $hiddenWord->id, 'mastery_percent' => 100]);

        $response = $this->withToken($teacher->createToken('test')->plainTextToken)->getJson('/api/reports/teacher');

        $response->assertOk()
            ->assertJsonPath('data.vocabulary_levels_count', 1)
            ->assertJsonPath('data.top_students.0.student_id', $student->student->id)
            ->assertJsonPath('data.top_students.0.average_mastery', 75)
            ->assertJsonPath('data.top_students.0.total_xp', 80)
            ->assertJsonPath('data.vocabulary_level_stats.0.level_id', $level->id)
            ->assertJsonPath('data.vocabulary_level_stats.0.total_words', 2)
            ->assertJsonPath('data.vocabulary_level_stats.0.average_mastery', 75)
            ->assertJsonPath('data.vocabulary_level_stats.0.mastered_words', 1)
            ->assertJsonPath('data.vocabulary_level_stats.0.unmastered_words', 1)
            ->assertJsonMissing(['student_id' => $otherStudent->student->id])
            ->assertJsonMissing(['level_id' => $hiddenLevel->id]);
    }

    public function test_admin_report_returns_xp_duration_timeseries_and_feature_usage(): void
    {
        $admin = $this->user('admin', 'Report Admin');
        $student = $this->user('student', 'Duration Student');
        $student->student->forceFill(['total_xp' => 120])->save();
        $level = VocabularyLevel::create(['title' => 'Duration Level']);
        $word = $level->words()->create(['word' => 'one', 'translation' => 'uno']);
        StudentWordProgress::create([
            'student_id' => $student->student->id,
            'vocabulary_word_id' => $word->id,
            'mastery_percent' => 50,
            'attempts' => 3,
        ]);
        PracticeSession::create([
            'student_id' => $student->student->id,
            'vocabulary_level_id' => $level->id,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
            'score_percent' => 80,
        ]);

        $response = $this->withToken($admin->createToken('test')->plainTextToken)->getJson('/api/reports/admin');

        $response->assertOk()
            ->assertJsonPath('data.total_xp', 120)
            ->assertJsonPath('data.average_session_duration', 300)
            ->assertJsonPath('data.average_session_duration_unit', 'seconds')
            ->assertJsonCount(7, 'data.usage_timeseries')
            ->assertJsonFragment(['date' => now()->toDateString(), 'practice_sessions' => 1])
            ->assertJsonFragment(['feature' => 'learning_progress_attempts', 'count' => 3])
            ->assertJsonFragment(['feature' => 'practice_sessions', 'count' => 1]);
    }

    public function test_admin_report_returns_class_performance_and_teacher_rankings(): void
    {
        $admin = $this->user('admin', 'Metrics Admin');
        $teacher = $this->user('teacher', 'Ranked Teacher');
        $student = $this->user('student', 'Ranked Student');
        $level = VocabularyLevel::create(['title' => 'Rank Level']);
        $word = $level->words()->create(['word' => 'two', 'translation' => 'dos']);
        $class = $this->enroll($teacher, $student, $level, 'Rank Class');
        StudentWordProgress::create(['student_id' => $student->student->id, 'vocabulary_word_id' => $word->id, 'mastery_percent' => 80]);
        PracticeSession::create([
            'student_id' => $student->student->id,
            'vocabulary_level_id' => $level->id,
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
            'score_percent' => 90,
        ]);

        $response = $this->withToken($admin->createToken('test')->plainTextToken)->getJson('/api/reports/admin');

        $response->assertOk()
            ->assertJsonFragment([
                'class_id' => $class->id,
                'class_name' => 'Rank Class',
                'teacher_name' => 'Ranked Teacher',
                'enrolled_students' => 1,
                'average_mastery' => 80,
                'practice_sessions' => 1,
                'average_practice_score' => 90,
            ])
            ->assertJsonPath('data.teacher_rankings.0.teacher_id', $teacher->teacher->id)
            ->assertJsonPath('data.teacher_rankings.0.class_count', 1)
            ->assertJsonPath('data.teacher_rankings.0.student_count', 1)
            ->assertJsonPath('data.teacher_rankings.0.average_mastery', 80);
    }
}
