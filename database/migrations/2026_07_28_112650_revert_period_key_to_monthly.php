<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Undo the semi-monthly period_key split ("YYYY-MM-1" / "YYYY-MM-2") introduced
 * in 2026_07_27_000001_change_period_key_to_biweekly.php. Duplicate detection
 * no longer depends on period_key (it uses dedup_week), so the split serves no
 * purpose; period_key goes back to being a plain monthly key ("YYYY-MM") for
 * reporting and archival.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->convert('/^(\d{4}-\d{2})-[12]$/', fn (array $m) => $m[1]);

        Schema::table('contacts', function (Blueprint $table) {
            $table->string('period_key', 7)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('period_key', 10)->nullable()->change();
        });

        $this->convert('/^\d{4}-\d{2}$/', function () {
            return null;
        }, restoreHalf: true);
    }

    private function convert(string $pattern, callable $resolve, bool $restoreHalf = false): void
    {
        DB::table('contacts')
            ->orderBy('id')
            ->chunkById(500, function ($contacts) use ($pattern, $resolve, $restoreHalf): void {
                foreach ($contacts as $contact) {
                    $periodKey = $contact->period_key;

                    if ($periodKey === null || ! preg_match($pattern, $periodKey, $m)) {
                        continue;
                    }

                    $newValue = $restoreHalf
                        ? $this->withHalfSuffix($periodKey, $contact->created_at)
                        : $resolve($m);

                    DB::table('contacts')
                        ->where('id', $contact->id)
                        ->update(['period_key' => $newValue]);
                }
            });
    }

    private function withHalfSuffix(string $periodKey, ?string $createdAt): string
    {
        $reference = $createdAt ? new \DateTime($createdAt) : new \DateTime();
        $half = ((int) $reference->format('j')) <= 14 ? '1' : '2';

        return $periodKey.'-'.$half;
    }
};
