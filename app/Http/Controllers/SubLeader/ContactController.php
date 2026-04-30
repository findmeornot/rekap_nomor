<?php

namespace App\Http\Controllers\SubLeader;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ContactController extends Controller
{
    public function index(): View
    {
        return view('subleader.contacts.index', [
            'contacts' => Contact::where('sub_leader_id', auth()->id())
                ->latest()
                ->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'contact_name' => ['nullable', 'string', 'max:255'],
            'phones' => ['nullable', 'string', 'max:10000'],
            'phone' => ['nullable', 'string', 'max:255'],
        ]);

        $subLeader = auth()->user();

        if (! $subLeader->leader_id) {
            return back()
                ->withErrors(['phones' => 'Akun sub leader belum memiliki leader. Hubungi superadmin.'])
                ->withInput();
        }

        $rawPhones = trim($request->string('phones')->toString());
        if ($rawPhones === '') {
            $rawPhones = $request->string('phone')->toString();
        }

        [$phones, $invalidCount] = $this->extractPhonesFromText($rawPhones);

        if (empty($phones)) {
            return back()
                ->withErrors(['phones' => 'Nomor tidak valid.'])
                ->withInput();
        }

        $existingPhones = Contact::whereIn('phone', $phones)->pluck('phone')->flip();
        $batchPhones = [];
        $contactName = $request->string('contact_name')->toString();
        $contactName = $contactName !== '' ? $contactName : null;

        $created = 0;
        $skippedDuplicate = 0;

        foreach ($phones as $phone) {
            if (isset($existingPhones[$phone]) || isset($batchPhones[$phone])) {
                $skippedDuplicate++;
                continue;
            }

            Contact::create([
                'contact_name' => $contactName,
                'phone' => $phone,
                'sub_leader_id' => $subLeader->id,
                'leader_id' => $subLeader->leader_id,
            ]);

            $batchPhones[$phone] = true;
            $created++;
        }

        return back()->with(
            'success',
            "Input selesai. Berhasil: {$created}, Duplikat: {$skippedDuplicate}, Tidak valid: {$invalidCount}."
        );
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:5120'],
        ]);

        $subLeader = auth()->user();

        if (! $subLeader->leader_id) {
            return back()->withErrors([
                'file' => 'Akun sub leader belum memiliki leader. Hubungi superadmin.',
            ]);
        }

        $rows = $this->extractRows($request->file('file'));
        if (empty($rows)) {
            return back()->withErrors(['file' => 'File kosong atau format kolom tidak dikenali.']);
        }

        $existingPhones = Contact::pluck('phone')->flip();
        $batchPhones = [];

        $created = 0;
        $skippedDuplicate = 0;
        $skippedInvalid = 0;

        foreach ($rows as $row) {
            $normalizedPhone = preg_replace('/\D+/', '', (string) ($row['phone'] ?? ''));

            if (! $normalizedPhone) {
                $skippedInvalid++;
                continue;
            }

            if (isset($existingPhones[$normalizedPhone]) || isset($batchPhones[$normalizedPhone])) {
                $skippedDuplicate++;
                continue;
            }

            Contact::create([
                'contact_name' => isset($row['contact_name']) && trim((string) $row['contact_name']) !== ''
                    ? trim((string) $row['contact_name'])
                    : null,
                'phone' => $normalizedPhone,
                'sub_leader_id' => $subLeader->id,
                'leader_id' => $subLeader->leader_id,
            ]);

            $batchPhones[$normalizedPhone] = true;
            $created++;
        }

        return back()->with(
            'success',
            "Import selesai. Berhasil: {$created}, Duplikat: {$skippedDuplicate}, Tidak valid: {$skippedInvalid}."
        );
    }

    /**
     * @return array<int, array{contact_name: string|null, phone: string|null}>
     */
    private function extractRows(UploadedFile $file): array
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
     * @return array<int, array{contact_name: string|null, phone: string|null}>
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

    /**
     * @return array{0: array<int, string>, 1: int}
     */
    private function extractPhonesFromText(string $value): array
    {
        $tokens = preg_split('/[\s,;]+/', trim($value)) ?: [];

        $phones = [];
        $invalidCount = 0;

        foreach ($tokens as $token) {
            $cleanToken = trim($token);
            if ($cleanToken === '') {
                continue;
            }

            $normalizedPhone = preg_replace('/\D+/', '', $cleanToken);
            if (! $normalizedPhone) {
                $invalidCount++;
                continue;
            }

            $phones[] = $normalizedPhone;
        }

        return [$phones, $invalidCount];
    }
}
