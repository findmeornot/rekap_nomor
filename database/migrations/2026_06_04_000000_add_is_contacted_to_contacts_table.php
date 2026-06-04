<?php

use App\Models\Contact;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->boolean('is_contacted')->default(false)->after('status');
        });

        // Backfill from existing status/contacted_at
        DB::table('contacts')
            ->where('status', Contact::STATUS_CONTACTED)
            ->orWhereNotNull('contacted_at')
            ->update(['is_contacted' => true]);
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('is_contacted');
        });
    }
};
