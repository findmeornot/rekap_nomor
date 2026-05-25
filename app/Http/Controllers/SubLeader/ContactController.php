<?php

namespace App\Http\Controllers\SubLeader;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\User;
use App\Services\ContactImportService;
use App\Support\PhoneNumber;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class ContactController extends Controller
{
    public function index(): View
    {
        $assistantMarketing = auth()->user();
        $contactsCount = Contact::where('assistant_marketing_id', $assistantMarketing->id)->count();
        $target = $assistantMarketing->TARGET_ASSISTANT_MARKETING;

        return view('subleader.contacts.index', [
            'contacts' => Contact::where('assistant_marketing_id', auth()->id())
                ->latest()
                ->paginate(20),
            'contactsCount' => $contactsCount,
            'target' => $target,
            'progress' => $target > 0 ? min(100, (int) round(($contactsCount / $target) * 100)) : 0,
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

        if (! $subLeader->team_id) {
            return back()
                ->withErrors(['phones' => 'Akun belum memiliki tim. Hubungi superadmin untuk assign tim.'])
                ->withInput();
        }

        $rawPhones = trim($request->string('phones')->toString());
        if ($rawPhones === '') {
            $rawPhones = $request->string('phone')->toString();
        }

        [$phones, $invalidCount] = $this->extractPhonesFromText($rawPhones);

        if (empty($phones)) {
            return back()
                ->withErrors(['phones' => PhoneNumber::validationMessage()])
                ->withInput();
        }

        $periodKey = Contact::activePeriodKey();
        $existingNormalized = Contact::query()
            ->where('period_key', $periodKey)
            ->whereIn('normalized_phone', $phones)
            ->pluck('normalized_phone')
            ->flip();
        $batchPhones = [];
        $contactName = $request->string('contact_name')->toString();
        $contactName = $contactName !== '' ? $contactName : null;

        $created = 0;
        $skippedDuplicate = 0;

        foreach ($phones as $normalizedPhone) {
            if (isset($existingNormalized[$normalizedPhone]) || isset($batchPhones[$normalizedPhone])) {
                $skippedDuplicate++;
                continue;
            }

            Contact::create($this->contactAttributes($subLeader, $normalizedPhone, $contactName));

            $batchPhones[$normalizedPhone] = true;
            $created++;
        }

        return back()->with(
            'success',
            "Input selesai. Berhasil: {$created}, Duplikat: {$skippedDuplicate}, Tidak valid: {$invalidCount}."
        );
    }

    public function import(Request $request, ContactImportService $contactImportService): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:5120'],
        ]);

        $subLeader = auth()->user();

        if (! $subLeader->team_id) {
            return back()->withErrors([
                'file' => 'Akun belum memiliki tim. Hubungi superadmin untuk assign tim.',
            ]);
        }

        $rows = $contactImportService->extractRows($request->file('file'));
        if (empty($rows)) {
            return back()->withErrors(['file' => 'File kosong atau format kolom tidak dikenali.']);
        }

        $summary = $contactImportService->importRows($rows, [
            'team_id' => $subLeader->team_id,
            'input_by' => $subLeader->id,
            'assistant_marketing_id' => $subLeader->id,
            'main_marketing_id' => null,
        ]);

        return back()->with(
            'success',
            "Import selesai. Berhasil: {$summary['created']}, Duplikat: {$summary['skipped_duplicate']}, Tidak valid: {$summary['skipped_invalid']}."
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function contactAttributes(User $subLeader, string $normalizedPhone, ?string $contactName): array
    {
        return [
            'contact_name' => $contactName,
            'phone' => $normalizedPhone,
            'normalized_phone' => $normalizedPhone,
            'period_key' => Contact::activePeriodKey(),
            'team_id' => $subLeader->team_id,
            'assistant_marketing_id' => $subLeader->id,
            'input_by' => $subLeader->id,
            'main_marketing_id' => null,
            'status' => Contact::STATUS_UNCONTACTED,
        ];
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

            if (! PhoneNumber::isValid($cleanToken)) {
                $invalidCount++;
                continue;
            }

            $normalizedPhone = PhoneNumber::normalize($cleanToken);
            if ($normalizedPhone === null) {
                $invalidCount++;
                continue;
            }

            $phones[] = $normalizedPhone;
        }

        return [$phones, $invalidCount];
    }

}
