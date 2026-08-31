<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentWordProgress;
use App\Models\Teacher;
use App\Models\User;
use App\Models\VocabularyLevel;
use App\Services\StudentProgressService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasteryDecayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-10 12:00:00');

        foreach (['admin', 'teacher', 'student'] as $role) {
            Role::firstOrCreate(['name' => $role], ['description' => $role]);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function setupLearning(): array
    {
        $teacherUser = User::factory()->create(['role_id' => Role::where('name', 'teacher')->value('id')]);
        $teacher = Teacher::create(['user_id' => $teacherUser->id]);
        $studentUser = User::factory()->create(['role_id' => Role::where('name', 'student')->value('id')]);
        $student = Student::create(['user_id' => $studentUser->id]);
        $class = SchoolClass::create(['teacher_id' => $teacher->id, 'name' => 'Decay Class']);
        $level = VocabularyLevel::create(['title' => 'Decay Level']);
        $class->vocabularyLevels()->attach($level->id);
        $class->students()->attach($student->id, ['status' => 'active', 'enrolled_at' => now()]);

        return [$studentUser, $student, $level, $teacherUser];
    }

    public function test_effective_mastery_uses_full_days_and_never_goes_below_zero(): void
    {
        $service = app(StudentProgressService::class);
        $progress = new StudentWordProgress(['mastery_percent' => 100]);

        foreach ([
            [now()->subHours(23), 100],
            [now()->subHours(25), 98],
            [now()->subDays(5), 90],
            [now()->subDays(80), 0],
            [null, 100],
        ] as [$timestamp, $expected]) {
            $progress->last_practiced_at = $timestamp;
            $this->assertSame($expected, $service->effectiveMastery($progress));
        }
    }

    public function test_review_queue_uses_threshold_access_and_effective_order(): void
    {
        [$studentUser, $student, $level] = $this->setupLearning();
        $effective90 = $level->words()->create(['word' => 'ninety', 'translation' => '90']);
        $effective89 = $level->words()->create(['word' => 'eighty-nine', 'translation' => '89']);
        $effective70 = $level->words()->create(['word' => 'seventy', 'translation' => '70']);
        $noProgress = $level->words()->create(['word' => 'new', 'translation' => 'new']);
        $student->wordProgress()->create(['vocabulary_word_id' => $effective90->id, 'mastery_percent' => 100, 'last_practiced_at' => now()->subDays(5)]);
        $student->wordProgress()->create(['vocabulary_word_id' => $effective89->id, 'mastery_percent' => 99, 'last_practiced_at' => now()->subDays(5)]);
        $student->wordProgress()->create(['vocabulary_word_id' => $effective70->id, 'mastery_percent' => 80, 'last_practiced_at' => now()->subDays(5)]);

        $response = $this->actingAs($studentUser)->getJson('/api/student/review');

        $response->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.vocabulary_word_id', $effective70->id)
            ->assertJsonPath('data.0.effective_mastery_percent', 70)
            ->assertJsonPath('data.1.vocabulary_word_id', $effective89->id)
            ->assertJsonMissing(['vocabulary_word_id' => $effective90->id])
            ->assertJsonMissing(['vocabulary_word_id' => $noProgress->id]);
    }

    public function test_decayed_completed_word_can_be_restored_without_more_xp(): void
    {
        [$studentUser, $student, $level] = $this->setupLearning();
        $word = $level->words()->create(['word' => 'restore', 'translation' => 'restore']);
        $completedAt = now()->subDays(20);
        $progress = $student->wordProgress()->create([
            'vocabulary_word_id' => $word->id,
            'mastery_percent' => 100,
            'attempts' => 4,
            'correct_attempts' => 4,
            'last_practiced_at' => now()->subDays(6),
            'completed_at' => $completedAt,
        ]);

        $this->actingAs($studentUser)->postJson("/api/student/vocabulary-words/{$word->id}/review", ['correct' => true])
            ->assertOk()
            ->assertJsonPath('data.mastery_percent', 100)
            ->assertJsonPath('data.effective_mastery_percent', 100)
            ->assertJsonPath('data.xp_awarded', false);

        $progress->refresh();
        $this->assertTrue($progress->last_practiced_at->equalTo(now()));
        $this->assertTrue($progress->completed_at->equalTo($completedAt));
        $this->assertSame(0, (int) $student->fresh()->total_xp);
    }

    public function test_incorrect_review_updates_timestamp_without_increasing_mastery(): void
    {
        [$studentUser, $student, $level] = $this->setupLearning();
        $word = $level->words()->create(['word' => 'incorrect', 'translation' => 'incorrect']);
        $progress = $student->wordProgress()->create([
            'vocabulary_word_id' => $word->id,
            'mastery_percent' => 100,
            'attempts' => 4,
            'correct_attempts' => 4,
            'last_practiced_at' => now()->subDays(6),
            'completed_at' => now()->subDays(10),
        ]);

        $this->actingAs($studentUser)->postJson("/api/student/vocabulary-words/{$word->id}/review", ['correct' => false])->assertOk();

        $progress->refresh();
        $this->assertSame(88, (int) $progress->mastery_percent);
        $this->assertSame(5, (int) $progress->attempts);
        $this->assertSame(4, (int) $progress->correct_attempts);
        $this->assertTrue($progress->last_practiced_at->equalTo(now()));
    }

    public function test_level_progress_summary_uses_effective_mastery(): void
    {
        [$studentUser, $student, $level] = $this->setupLearning();
        foreach ([100, 100, 100] as $index => $mastery) {
            $word = $level->words()->create(['word' => "word-{$index}", 'translation' => "translation-{$index}"]);
            $student->wordProgress()->create([
                'vocabulary_word_id' => $word->id,
                'mastery_percent' => $mastery,
                'last_practiced_at' => $index === 0 ? now()->subDays(4) : ($index === 1 ? now()->subDays(6) : now()),
                'completed_at' => now()->subDays(10),
            ]);
        }

        $this->actingAs($studentUser)->getJson("/api/student/vocabulary-levels/{$level->id}/progress")
            ->assertOk()
            ->assertJsonPath('data.words.0.mastery_percent', 100)
            ->assertJsonPath('data.words.0.effective_mastery_percent', 92)
            ->assertJsonPath('data.words.1.effective_mastery_percent', 88)
            ->assertJsonPath('data.summary.progress_percent', 93)
            ->assertJsonPath('data.summary.completed', false);
    }

    public function test_dashboard_and_report_current_mastery_aggregates_use_effective_values(): void
    {
        [$studentUser, $student, $level, $teacherUser] = $this->setupLearning();
        $word = $level->words()->create(['word' => 'aggregate', 'translation' => 'aggregate']);
        $progress = $student->wordProgress()->create([
            'vocabulary_word_id' => $word->id,
            'mastery_percent' => 100,
            'last_practiced_at' => now()->subDays(4),
            'completed_at' => now()->subDays(10),
        ]);
        $admin = User::factory()->create(['role_id' => Role::where('name', 'admin')->value('id')]);

        $this->actingAs($studentUser)->getJson('/api/dashboard/student')
            ->assertOk()
            ->assertJsonPath('data.average_mastery_across_accessible_vocabulary', 92)
            ->assertJsonPath('data.recent_reviewable_words_count', 0);

        $progress->update(['last_practiced_at' => now()->subDays(6)]);

        $this->actingAs($studentUser)->getJson('/api/dashboard/student')
            ->assertOk()
            ->assertJsonPath('data.average_mastery_across_accessible_vocabulary', 88)
            ->assertJsonPath('data.recent_reviewable_words_count', 1);
        $this->actingAs($studentUser)->getJson('/api/reports/student')
            ->assertOk()->assertJsonPath('data.average_mastery', 88);
        $this->actingAs($teacherUser)->getJson('/api/dashboard/teacher')
            ->assertOk()->assertJsonPath('data.average_student_progress', 88);
        $this->actingAs($teacherUser)->getJson('/api/reports/teacher')
            ->assertOk()->assertJsonPath('data.class_performance.0.average_mastery', 88);
        $this->actingAs($admin)->getJson('/api/dashboard/admin')
            ->assertOk()->assertJsonPath('data.average_student_mastery', 88);
        $this->actingAs($admin)->getJson('/api/reports/admin')
            ->assertOk()->assertJsonPath('data.average_mastery', 88);
    }
}
