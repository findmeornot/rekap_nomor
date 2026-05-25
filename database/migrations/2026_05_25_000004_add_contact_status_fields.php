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
            $table->string('status', 32)
                ->default(Contact::STATUS_UNCONTACTED)
                ->after('main_marketing_id');
            $table->foreignId('status_updated_by')
                ->nullable()
                ->after('status')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('status_updated_at')->nullable()->after('status_updated_by');
        });

        DB::table('contacts')
            ->whereNotNull('contacted_at')
            ->update([
                'status' => Contact::STATUS_CONTACTED,
                'status_updated_at' => DB::raw('contacted_at'),
                'status_updated_by' => DB::raw('contacted_by_main_marketing_id'),
            ]);
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('status_updated_by');
            $table->dropColumn(['status', 'status_updated_at']);
        });
    }
};
