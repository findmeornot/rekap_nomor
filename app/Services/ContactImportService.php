<?php

namespace App\Services;

use App\Models\Contact;
use App\Support\PhoneNumber;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ContactImportService
{
    /**
     * @return array<int, array{contact_name: string|null, phone: string}>
     */
    public function extractRows(UploadedFile $file): array
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());

        $rawRows = match ($extension) {
            'csv', 'txt' => $this->extractCsvRows($file),
            'xlsx', 'xls' => $this->extractSpreadsheetRows($file),
            default => [],
        };

        return $this->normalizeRows($rawRows);
    }

    /**
     * @param array<int, array{contact_name: string|null, phone: string}> $rows
     * @param array{team_id:int,input_by:int,sub_leader_id:?int,leader_id:?int,period_key?:string} $context
     * @return array{created:int,skipped_duplicate:int,skipped_invalid:int}
     */
    public function importRows(array $rows, array $context): array
    {
        $periodKey = $context['period_key'] ?? Contact::activePeriodKey();
        $existingNormalized = Contact::query()
            ->where('period_key', $periodKey)
            ->whereNotNull('normalized_phone')
            ->pluck('normalized_phone')
            ->flip();

        $batchPhones = [];
        $created = 0;
        $skippedDuplicate = 0;
        $skippedInvalid = 0;

        foreach ($rows as $row) {
            $rawPhone = (string) ($row['phone'] ?? '');
            if (! PhoneNumber::isValid($rawPhone)) {
                $skippedInvalid++;
                continue;
            }

            $normalizedPhone = PhoneNumber::normalize($rawPhone);
            if ($normalizedPhone === null) {
                $skippedInvalid++;
                continue;
            }

            if (isset($existingNormalized[$normalizedPhone]) || isset($batchPhones[$normalizedPhone])) {
                $skippedDuplicate++;
                continue;
            }

            $contactName = isset($row['contact_name']) && trim((string) $row['contact_name']) !== ''
                ? trim((string) $row['contact_name'])
                : null;

            Contact::create([
                'contact_name' => $contactName,
                'phone' => $normalizedPhone,
                'normalized_phone' => $normalizedPhone,
                'period_key' => $periodKey,
                'team_id' => $context['team_id'],
                'sub_leader_id' => $context['sub_leader_id'] ?? null,
                'input_by' => $context['input_by'],
                'leader_id' => $context['leader_id'] ?? null,
                'status' => Contact::STATUS_UNCONTACTED,
            ]);

            $batchPhones[$normalizedPhone] = true;
            $created++;
        }

        return [
            'created' => $created,
            'skipped_duplicate' => $skippedDuplicate,
            'skipped_invalid' => $skippedInvalid,
        ];
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function extractCsvRows(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if (! $handle) {
            return [];
        }

        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            $rows[] = $data;
        }
        fclose($handle);

        return $rows;
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function extractSpreadsheetRows(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();

        return $sheet->toArray(null, true, true, false);
    }

    /**
     * @param array<int, array<int, mixed>> $rawRows
     * @return array<int, array{contact_name: string|null, phone: string}>
     */
    private function normalizeRows(array $rawRows): array
    {
        if (empty($rawRows)) {
            return [];
        }

        $header = array_map(
            fn ($value) => strtolower(trim((string) $value)),
            $rawRows[0]
        );

        $phoneIndex = null;
        $nameIndex = null;

        foreach ($header as $index => $column) {
            if (in_array($column, ['phone', 'nomor', 'no hp', 'nohp', 'number'], true)) {
                $phoneIndex = $index;
            }

            if (in_array($column, ['name', 'nama', 'contact_name', 'kontak'], true)) {
                $nameIndex = $index;
            }
        }

        if ($phoneIndex === null) {
            return [];
        }

        $rows = [];
        foreach (array_slice($rawRows, 1) as $row) {
            $rows[] = [
                'contact_name' => $nameIndex !== null ? (string) ($row[$nameIndex] ?? '') : null,
                'phone' => (string) ($row[$phoneIndex] ?? ''),
            ];
        }

        return $rows;
    }
}
