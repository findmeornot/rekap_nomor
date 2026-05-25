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
            $table->string('period_key', 7)->nullable()->after('phone');
            $table->foreignId('team_id')
                ->nullable()
                ->after('main_marketing_id')
                ->constrained('teams')
                ->nullOnDelete();
        });

        $this->backfillContacts();

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->unique(['phone', 'period_key']);
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropUnique(['phone', 'period_key']);
            $table->unique('phone');
            $table->dropConstrainedForeignId('team_id');
            $table->dropColumn('period_key');
        });
    }

    private function backfillContacts(): void
    {
        $usersTeam = DB::table('users')
            ->whereNotNull('team_id')
            ->pluck('team_id', 'id');

        DB::table('contacts')
            ->orderBy('id')
            ->chunkById(200, function ($contacts) use ($usersTeam): void {
                foreach ($contacts as $contact) {
                    $periodKey = $contact->created_at
                        ? date('Y-m', strtotime((string) $contact->created_at))
                        : now()->format('Y-m');

                    $normalized = PhoneNumber::normalize((string) $contact->phone);
                    $phone = $normalized ?? (string) $contact->phone;

                    $teamId = null;
                    if ($contact->assistant_marketing_id && isset($usersTeam[$contact->assistant_marketing_id])) {
                        $teamId = $usersTeam[$contact->assistant_marketing_id];
                    } elseif ($contact->main_marketing_id && isset($usersTeam[$contact->main_marketing_id])) {
                        $teamId = $usersTeam[$contact->main_marketing_id];
                    }

                    DB::table('contacts')
                        ->where('id', $contact->id)
                        ->update([
                            'phone' => $phone,
                            'period_key' => $periodKey,
                            'team_id' => $teamId,
                        ]);
                }
            });
    }
};
