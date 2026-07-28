<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('dedup_week', 8)->nullable()->after('period_key');
        });

        $this->backfillDedupWeek();

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropUnique(['normalized_phone', 'period_key']);
            $table->unique(['normalized_phone', 'dedup_week']);
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropUnique(['normalized_phone', 'dedup_week']);
            $table->unique(['normalized_phone', 'period_key']);
            $table->dropColumn('dedup_week');
        });
    }

    private function backfillDedupWeek(): void
    {
        DB::table('contacts')
            ->orderBy('id')
            ->chunkById(500, function ($contacts): void {
                foreach ($contacts as $contact) {
                    $reference = $contact->created_at ?? now();

                    DB::table('contacts')
                        ->where('id', $contact->id)
                        ->update([
                            'dedup_week' => Carbon::parse($reference)->format('o-\WW'),
                        ]);
                }
            });
    }
};
