<?php

use App\Support\PhoneNumber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('normalized_phone', 20)->nullable()->after('phone');
        });

        $this->backfillNormalizedPhone();
        $this->removeDuplicateNormalizedPeriodPairs();

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropUnique(['phone', 'period_key']);
            $table->unique(['normalized_phone', 'period_key']);
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropUnique(['normalized_phone', 'period_key']);
            $table->unique(['phone', 'period_key']);
            $table->dropColumn('normalized_phone');
        });
    }

    private function backfillNormalizedPhone(): void
    {
        DB::table('contacts')
            ->orderBy('id')
            ->chunkById(200, function ($contacts): void {
                foreach ($contacts as $contact) {
                    $normalized = PhoneNumber::normalize((string) $contact->phone);

                    DB::table('contacts')
                        ->where('id', $contact->id)
                        ->update([
                            'normalized_phone' => $normalized,
                        ]);
                }
            });
    }

    private function removeDuplicateNormalizedPeriodPairs(): void
    {
        $duplicateGroups = DB::table('contacts')
            ->select('normalized_phone', 'period_key', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as total'))
            ->whereNotNull('normalized_phone')
            ->whereNotNull('period_key')
            ->groupBy('normalized_phone', 'period_key')
            ->having('total', '>', 1)
            ->get();

        foreach ($duplicateGroups as $group) {
            DB::table('contacts')
                ->where('normalized_phone', $group->normalized_phone)
                ->where('period_key', $group->period_key)
                ->where('id', '!=', $group->keep_id)
                ->delete();
        }
    }
};
