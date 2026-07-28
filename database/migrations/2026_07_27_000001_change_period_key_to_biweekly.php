<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migrate duplicate detection from monthly periods (Y-m, e.g. "2026-07")
 * to bi-weekly periods (Y-m-{half}, e.g. "2026-07-1" or "2026-07-2").
 *
 * Period 1: day  1–14
 * Period 2: day 15–last day of month
 *
 * Only the live `contacts` table is affected. The `contact_histories` table
 * uses `archive_period` (Y-m format) for display grouping and is unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Widen the column: VARCHAR(7) → VARCHAR(10) to fit "2026-07-2".
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('period_key', 10)->nullable()->change();
        });

        // 2. Drop the existing unique constraint before backfilling
        //    (avoids conflicts during the update).
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropUnique(['normalized_phone', 'period_key']);
        });

        // 3. Backfill existing rows: convert "YYYY-MM" → "YYYY-MM-{1|2}"
        //    based on the day-of-month from created_at.
        DB::table('contacts')
            ->orderBy('id')
            ->chunkById(500, function ($contacts): void {
                foreach ($contacts as $contact) {
                    $periodKey = $contact->period_key;

                    if ($periodKey === null) {
                        continue;
                    }

                    // Already in bi-weekly format (contains a second hyphen) — skip.
                    if (substr_count($periodKey, '-') >= 2) {
                        continue;
                    }

                    // Determine which half based on the contact's created_at day.
                    $createdAt = $contact->created_at ? new \DateTime($contact->created_at) : new \DateTime();
                    $day       = (int) $createdAt->format('j');
                    $half      = $day <= 14 ? '1' : '2';

                    DB::table('contacts')
                        ->where('id', $contact->id)
                        ->update(['period_key' => $periodKey . '-' . $half]);
                }
            });

        // 4. Recreate the unique constraint with the new (wider) period_key values.
        Schema::table('contacts', function (Blueprint $table) {
            $table->unique(['normalized_phone', 'period_key']);
        });
    }

    public function down(): void
    {
        // Reverse: strip the "-{half}" suffix and restore the monthly key.
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropUnique(['normalized_phone', 'period_key']);
        });

        // Convert "YYYY-MM-1" / "YYYY-MM-2" back to "YYYY-MM".
        // If two rows share the same (normalized_phone, YYYY-MM) after stripping,
        // we keep the one with the higher id (most recent) and delete the older.
        DB::statement("
            UPDATE contacts
            SET period_key = SUBSTRING(period_key, 1, 7)
            WHERE period_key REGEXP '^[0-9]{4}-[0-9]{2}-[12]$'
        ");

        // Remove any duplicates that would violate the restored monthly constraint.
        DB::statement("
            DELETE c1 FROM contacts c1
            INNER JOIN contacts c2
                ON  c1.normalized_phone = c2.normalized_phone
                AND c1.period_key       = c2.period_key
                AND c1.id               < c2.id
        ");

        Schema::table('contacts', function (Blueprint $table) {
            $table->string('period_key', 7)->nullable()->change();
            $table->unique(['normalized_phone', 'period_key']);
        });
    }
};
