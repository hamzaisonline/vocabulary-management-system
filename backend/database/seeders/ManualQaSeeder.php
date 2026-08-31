<?php

namespace Database\Seeders;

use App\Models\PracticeAttempt;
use App\Models\PracticeSession;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentWordProgress;
use App\Models\Teacher;
use App\Models\User;
use App\Models\VocabularyLevel;
use App\Models\VocabularyWord;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class ManualQaSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('ManualQaSeeder cannot run in production.');
        }

        DB::transaction(function () {
            $roles = collect(['admin', 'teacher', 'student'])->mapWithKeys(function (string $name) {
                $role = Role::firstOrCreate(
                    ['name' => $name],
                    ['description' => ucfirst($name)]
                );

                return [$name => $role];
            });

            $adminUser = $this->upsertUser('QA Admin', 'qa.admin@example.com', 'Admin123!', $roles['admin']->id);
            $teacherAUser = $this->upsertUser('QA Teacher A', 'qa.teacher.a@example.com', 'Teacher123!', $roles['teacher']->id);
            $teacherBUser = $this->upsertUser('QA Teacher B', 'qa.teacher.b@example.com', 'Teacher123!', $roles['teacher']->id);
            $studentAUser = $this->upsertUser('QA Student A', 'qa.student.a@example.com', 'Student123!', $roles['student']->id);
            $studentBUser = $this->upsertUser('QA Student B', 'qa.student.b@example.com', 'Student123!', $roles['student']->id);
            $studentCUser = $this->upsertUser('José García', 'qa.student.c@example.com', 'Student123!', $roles['student']->id);

            $teacherA = Teacher::firstOrCreate(['user_id' => $teacherAUser->id]);
            $teacherB = Teacher::firstOrCreate(['user_id' => $teacherBUser->id]);
            $studentA = Student::firstOrCreate(['user_id' => $studentAUser->id]);
            $studentB = Student::firstOrCreate(['user_id' => $studentBUser->id]);
            $studentC = Student::firstOrCreate(['user_id' => $studentCUser->id]);

            $studentA->forceFill(['total_xp' => 40])->save();
            $studentB->forceFill(['total_xp' => 20])->save();
            $studentC->forceFill(['total_xp' => 10])->save();

            $classS1 = $this->upsertClass($teacherA->id, 'QA Spanish S1', 'Manual QA class for Spanish beginners');
            $classKs3 = $this->upsertClass($teacherA->id, 'QA Spanish KS3', 'Shared vocabulary QA class');
            $classYear7 = $this->upsertClass($teacherB->id, 'QA Spanish Year 7', 'Teacher B QA class');

            $this->enroll($classS1, [$studentA->id, $studentC->id]);
            $this->enroll($classKs3, [$studentC->id]);
            $this->enroll($classYear7, [$studentB->id]);

            $family = $this->upsertLevel(
                'QA Family Basics',
                'Private family vocabulary for QA',
                'S1',
                $teacherAUser->id,
                'private'
            );
            $travel = $this->upsertLevel(
                'QA Travel Basics',
                'Shared travel vocabulary for QA',
                'KS3',
                $teacherAUser->id,
                'shared'
            );
            $school = $this->upsertLevel(
                'QA School Basics',
                'Teacher B private vocabulary',
                'Year 7',
                $teacherBUser->id,
                'private'
            );
            $core = $this->upsertLevel(
                'QA Core Spanish',
                'Admin owned shared vocabulary',
                'Beginner A1',
                $adminUser->id,
                'shared'
            );

            $familyWords = $this->upsertWords($family, [
                ['word' => 'Madre', 'translation' => 'Mother', 'example' => 'Mi madre es amable.', 'notes' => 'Currently mastered QA word.'],
                ['word' => 'Padre', 'translation' => 'Father', 'example' => 'Mi padre trabaja aquí.', 'notes' => 'Completed word intentionally decayed for review QA.'],
                ['word' => 'Hermano', 'translation' => 'Brother', 'example' => 'Mi hermano juega fútbol.', 'notes' => 'Partially mastered review word.'],
                ['word' => 'Hermana', 'translation' => 'Sister', 'example' => 'Mi hermana estudia mucho.', 'notes' => 'Lower-mastery review word.'],
            ]);
            $travelWords = $this->upsertWords($travel, [
                ['word' => 'España', 'translation' => 'Spain', 'example' => 'Fui a España.'],
                ['word' => 'Tren', 'translation' => 'Train', 'example' => 'El tren llega a las ocho.'],
                ['word' => 'Aeropuerto', 'translation' => 'Airport', 'example' => 'El aeropuerto está lejos.'],
                ['word' => 'Billete', 'translation' => 'Ticket', 'example' => 'Tengo un billete de tren.'],
            ]);
            $schoolWords = $this->upsertWords($school, [
                ['word' => 'Escuela', 'translation' => 'School', 'example' => 'La escuela es grande.'],
                ['word' => 'Profesor', 'translation' => 'Teacher', 'example' => 'El profesor habla español.'],
                ['word' => 'Libro', 'translation' => 'Book', 'example' => 'Tengo un libro nuevo.'],
                ['word' => 'Clase', 'translation' => 'Class', 'example' => 'La clase empieza ahora.'],
            ]);
            $this->upsertWords($core, [
                ['word' => 'Niño', 'translation' => 'Boy'],
                ['word' => 'Mañana', 'translation' => 'Tomorrow'],
                ['word' => 'Café', 'translation' => 'Coffee'],
                ['word' => '¿Qué tal?', 'translation' => 'How are you?'],
            ]);

            $classS1->vocabularyLevels()->syncWithoutDetaching([$family->id, $travel->id]);
            $classKs3->vocabularyLevels()->syncWithoutDetaching([$travel->id, $core->id]);
            $classYear7->vocabularyLevels()->syncWithoutDetaching([$travel->id, $school->id]);

            $this->upsertProgress($studentA, $familyWords['Madre'], 100, 4, 4, now(), now()->subDays(12));
            $this->upsertProgress($studentA, $familyWords['Padre'], 100, 5, 4, now()->subDays(6), now()->subDays(12));
            $this->upsertProgress($studentA, $familyWords['Hermano'], 75, 4, 3, now(), null);
            $this->upsertProgress($studentA, $familyWords['Hermana'], 50, 3, 2, now(), null);

            $this->upsertProgress($studentB, $travelWords['Tren'], 50, 3, 2, now()->subDay(), null);
            $this->upsertProgress($studentC, $familyWords['Madre'], 25, 2, 1, now()->subDays(2), null);

            $this->seedPracticeSession($studentA, $family, $familyWords->values()->all(), 3, now()->subDays(2));
            $this->seedPracticeSession($studentB, $travel, $travelWords->values()->all(), 2, now()->subDay());
        });

        $this->command?->info('Manual QA data seeded successfully.');
    }

    private function upsertUser(string $name, string $email, string $password, int $roleId): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make($password), 'role_id' => $roleId]
        );
    }

    private function upsertClass(int $teacherId, string $name, string $description): SchoolClass
    {
        return SchoolClass::updateOrCreate(
            ['teacher_id' => $teacherId, 'name' => $name],
            ['description' => $description, 'language' => 'Spanish']
        );
    }

    private function enroll(SchoolClass $schoolClass, array $studentIds): void
    {
        $students = collect($studentIds)->mapWithKeys(fn (int $id) => [$id => [
            'status' => 'active',
            'enrolled_at' => now(),
        ]])->all();

        $schoolClass->students()->syncWithoutDetaching($students);
    }

    private function upsertLevel(
        string $title,
        string $description,
        string $stage,
        int $ownerId,
        string $visibility
    ): VocabularyLevel {
        return VocabularyLevel::updateOrCreate(
            ['title' => $title],
            [
                'description' => $description,
                'stage' => $stage,
                'difficulty' => 'beginner',
                'created_by_user_id' => $ownerId,
                'visibility' => $visibility,
            ]
        );
    }

    private function upsertWords(VocabularyLevel $level, array $words)
    {
        return collect($words)->mapWithKeys(function (array $data) use ($level) {
            $word = VocabularyWord::updateOrCreate(
                ['vocabulary_level_id' => $level->id, 'word' => $data['word']],
                [
                    'translation' => $data['translation'],
                    'example' => $data['example'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'audio_path' => null,
                ]
            );

            return [$data['word'] => $word];
        });
    }

    private function upsertProgress(
        Student $student,
        VocabularyWord $word,
        int $mastery,
        int $attempts,
        int $correctAttempts,
        $lastPracticedAt,
        $completedAt
    ): void {
        StudentWordProgress::updateOrCreate(
            ['student_id' => $student->id, 'vocabulary_word_id' => $word->id],
            [
                'mastery_percent' => $mastery,
                'attempts' => $attempts,
                'correct_attempts' => $correctAttempts,
                'last_practiced_at' => $lastPracticedAt,
                'completed_at' => $completedAt,
            ]
        );
    }

    private function seedPracticeSession(
        Student $student,
        VocabularyLevel $level,
        array $words,
        int $correctAnswers,
        $startedAt
    ): void {
        $totalQuestions = count($words);
        $session = PracticeSession::updateOrCreate(
            [
                'student_id' => $student->id,
                'vocabulary_level_id' => $level->id,
                'total_questions' => $totalQuestions,
                'correct_answers' => $correctAnswers,
            ],
            [
                'started_at' => $startedAt,
                'completed_at' => $startedAt->copy()->addMinutes(12),
                'score_percent' => $totalQuestions > 0 ? (int) round(($correctAnswers / $totalQuestions) * 100) : 0,
            ]
        );

        foreach (array_values($words) as $index => $word) {
            $correct = $index < $correctAnswers;
            PracticeAttempt::updateOrCreate(
                ['practice_session_id' => $session->id, 'vocabulary_word_id' => $word->id],
                [
                    'submitted_answer' => $correct ? $word->translation : 'QA incorrect answer',
                    'is_correct' => $correct,
                    'attempted_at' => $startedAt->copy()->addMinutes($index + 1),
                ]
            );
        }
    }
}
