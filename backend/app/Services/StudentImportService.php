<?php

namespace App\Services;

use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class StudentImportService
{
    private const REQUIRED_HEADERS = ['name', 'email', 'password'];

    public function import(SchoolClass $schoolClass, UploadedFile $file): array
    {
        [$headers, $rows] = $this->parse($file);
        $missing = array_values(array_diff(self::REQUIRED_HEADERS, $headers));
        if ($missing) {
            throw ValidationException::withMessages([
                'file' => ['Missing required CSV columns: '.implode(', ', $missing).'.'],
            ]);
        }
        if (! $rows) {
            throw ValidationException::withMessages(['file' => ['The CSV file does not contain any student rows.']]);
        }

        $studentRole = Role::query()->where('name', 'student')->first();
        if (! $studentRole) {
            throw ValidationException::withMessages(['file' => ['The student role is not configured.']]);
        }

        $summary = [
            'created' => 0,
            'enrolled_existing' => 0,
            'already_enrolled' => 0,
            'failed' => 0,
            'errors' => [],
            'temporary_passwords' => [],
        ];
        $seenEmails = [];

        foreach ($rows as $csvRow) {
            $data = $csvRow['data'];
            $data['name'] = trim((string) ($data['name'] ?? ''));
            $data['email'] = mb_strtolower(trim((string) ($data['email'] ?? '')));
            $data['password'] = trim((string) ($data['password'] ?? ''));

            $validator = Validator::make($data, [
                'name' => ['nullable', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'password' => ['nullable', 'string', 'min:8', 'max:255'],
            ]);
            if ($validator->fails()) {
                $this->addError($summary, $csvRow['row'], $data['email'], $validator->errors()->toArray());
                continue;
            }
            if (isset($seenEmails[$data['email']])) {
                $this->addError($summary, $csvRow['row'], $data['email'], ['email' => ['Duplicate email in CSV file.']]);
                continue;
            }
            $existingUser = User::query()->with(['role', 'student'])->whereRaw('LOWER(email) = ?', [$data['email']])->first();
            if ($existingUser) {
                $seenEmails[$data['email']] = true;
                if ($existingUser->role?->name !== 'student' || ! $existingUser->student) {
                    $this->addError($summary, $csvRow['row'], $data['email'], ['email' => ['This email belongs to a non-student account.']]);
                    continue;
                }
                if ($schoolClass->students()->where('students.id', $existingUser->student->id)->exists()) {
                    $summary['already_enrolled']++;
                    continue;
                }

                $schoolClass->students()->attach($existingUser->student->id, ['status' => 'active', 'enrolled_at' => now()]);
                $summary['enrolled_existing']++;
                continue;
            }

            if ($data['name'] === '') {
                $this->addError($summary, $csvRow['row'], $data['email'], ['name' => ['The name field is required when creating a student.']]);
                continue;
            }

            $seenEmails[$data['email']] = true;
            $generatedPassword = $data['password'] === '' ? Str::random(16) : null;
            $password = $data['password'] !== '' ? $data['password'] : $generatedPassword;
            try {
                DB::transaction(function () use ($schoolClass, $studentRole, $data, $password) {
                    $user = User::create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'password' => Hash::make($password),
                        'role_id' => $studentRole->id,
                    ]);
                    $student = Student::create(['user_id' => $user->id]);
                    $schoolClass->students()->attach($student->id, ['status' => 'active', 'enrolled_at' => now()]);
                });
                $summary['created']++;
                if ($generatedPassword !== null) {
                    $summary['temporary_passwords'][] = ['email' => $data['email'], 'password' => $generatedPassword];
                }
            } catch (Throwable $exception) {
                report($exception);
                $this->addError($summary, $csvRow['row'], $data['email'], ['row' => ['The student could not be created or enrolled.']]);
            }
        }

        return $summary;
    }

    private function parse(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages(['file' => ['The CSV file could not be read.']]);
        }
        try {
            $headerRow = fgetcsv($handle);
            if ($headerRow === false || $headerRow === [null]) {
                throw ValidationException::withMessages(['file' => ['The CSV file is empty.']]);
            }
            $headers = array_map(function ($header) {
                return mb_strtolower(trim((string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $header)));
            }, $headerRow);
            if (count(array_filter($headers)) !== count($headers) || count($headers) !== count(array_unique($headers))) {
                throw ValidationException::withMessages(['file' => ['The CSV header contains empty or duplicate columns.']]);
            }

            $rows = [];
            $rowNumber = 1;
            while (($values = fgetcsv($handle)) !== false) {
                $rowNumber++;
                if (collect($values)->every(fn ($value) => trim((string) $value) === '')) continue;
                $values = array_pad($values, count($headers), null);
                $rows[] = ['row' => $rowNumber, 'data' => array_combine($headers, array_slice($values, 0, count($headers)))];
            }
            return [$headers, $rows];
        } finally {
            fclose($handle);
        }
    }

    private function addError(array &$summary, int $row, string $email, array $errors): void
    {
        $summary['failed']++;
        $summary['errors'][] = compact('row', 'email', 'errors');
    }
}
