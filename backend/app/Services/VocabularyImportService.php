<?php

namespace App\Services;

use App\Models\VocabularyLevel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class VocabularyImportService
{
    private const REQUIRED_HEADERS = ['word', 'translation'];

    private const SUPPORTED_HEADERS = ['word', 'translation', 'example', 'notes'];

    public function import(VocabularyLevel $level, UploadedFile $file): array
    {
        [$headers, $rows] = $this->parse($file);
        $missingHeaders = array_values(array_diff(self::REQUIRED_HEADERS, $headers));

        if ($missingHeaders) {
            throw ValidationException::withMessages([
                'file' => ['Missing required CSV columns: ' . implode(', ', $missingHeaders) . '.'],
            ]);
        }

        if (!$rows) {
            throw ValidationException::withMessages([
                'file' => ['The CSV file does not contain any vocabulary rows.'],
            ]);
        }

        $knownWords = $level->words()
            ->pluck('word')
            ->mapWithKeys(fn ($word) => [$this->normalizeWord($word) => true])
            ->all();

        $validRows = [];
        $errors = [];
        $skipped = 0;

        foreach ($rows as $csvRow) {
            $data = array_intersect_key($csvRow['data'], array_flip(self::SUPPORTED_HEADERS));
            $validator = Validator::make($data, [
                'word' => ['required', 'string', 'max:255'],
                'translation' => ['required', 'string', 'max:255'],
                'example' => ['nullable', 'string'],
                'notes' => ['nullable', 'string'],
            ]);

            if ($validator->fails()) {
                $errors[] = ['row' => $csvRow['row'], 'errors' => $validator->errors()->toArray()];
                continue;
            }

            $validated = $validator->validated();
            $normalizedWord = $this->normalizeWord($validated['word']);

            if (isset($knownWords[$normalizedWord])) {
                $skipped++;
                continue;
            }

            $knownWords[$normalizedWord] = true;
            $validRows[] = [
                'word' => trim($validated['word']),
                'translation' => trim($validated['translation']),
                'example' => $this->nullableValue($validated['example'] ?? null),
                'notes' => $this->nullableValue($validated['notes'] ?? null),
            ];
        }

        DB::transaction(function () use ($level, $validRows) {
            foreach ($validRows as $row) {
                $level->words()->create($row);
            }
        });

        return [
            'imported' => count($validRows),
            'skipped' => $skipped,
            'failed' => count($errors),
            'errors' => $errors,
        ];
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
                $header = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header);
                return mb_strtolower(trim($header));
            }, $headerRow);

            if (count(array_filter($headers)) !== count($headers) || count($headers) !== count(array_unique($headers))) {
                throw ValidationException::withMessages(['file' => ['The CSV header contains empty or duplicate columns.']]);
            }

            $rows = [];
            $rowNumber = 1;
            while (($values = fgetcsv($handle)) !== false) {
                $rowNumber++;
                if ($this->isBlankRow($values)) {
                    continue;
                }

                $values = array_pad($values, count($headers), null);
                $rows[] = [
                    'row' => $rowNumber,
                    'data' => array_combine($headers, array_slice($values, 0, count($headers))),
                ];
            }

            return [$headers, $rows];
        } finally {
            fclose($handle);
        }
    }

    private function normalizeWord(string $word): string
    {
        return mb_strtolower(trim($word));
    }

    private function nullableValue(?string $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function isBlankRow(array $values): bool
    {
        return collect($values)->every(fn ($value) => trim((string) $value) === '');
    }
}
