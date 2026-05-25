<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->foreignId('input_by')
                ->nullable()
                ->after('assistant_marketing_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        DB::table('contacts')
            ->whereNotNull('assistant_marketing_id')
            ->whereNull('input_by')
            ->update([
                'input_by' => DB::raw('assistant_marketing_id'),
            ]);
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('input_by');
        });
    }
};
