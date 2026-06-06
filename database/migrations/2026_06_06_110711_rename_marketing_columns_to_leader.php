<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Rename columns in `users`
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'main_marketing_id') && !Schema::hasColumn('users', 'leader_id')) {
                $table->dropForeign(['main_marketing_id']);
                $table->renameColumn('main_marketing_id', 'leader_id');
                $table->foreign('leader_id')->references('id')->on('users')->nullOnDelete();
            }
        });

        // 2. Rename columns in `contacts`
        Schema::table('contacts', function (Blueprint $table) {
            if (Schema::hasColumn('contacts', 'assistant_marketing_id') && !Schema::hasColumn('contacts', 'sub_leader_id')) {
                $table->dropForeign(['assistant_marketing_id']);
                $table->renameColumn('assistant_marketing_id', 'sub_leader_id');
                $table->foreign('sub_leader_id')->references('id')->on('users')->nullOnDelete();
            }

            if (Schema::hasColumn('contacts', 'main_marketing_id') && !Schema::hasColumn('contacts', 'leader_id')) {
                $table->dropForeign(['main_marketing_id']);
                $table->renameColumn('main_marketing_id', 'leader_id');
                $table->foreign('leader_id')->references('id')->on('users')->nullOnDelete();
            }

            if (Schema::hasColumn('contacts', 'contacted_by_main_marketing_id') && !Schema::hasColumn('contacts', 'contacted_by_leader_id')) {
                $table->dropForeign(['contacted_by_main_marketing_id']);
                $table->renameColumn('contacted_by_main_marketing_id', 'contacted_by_leader_id');
                $table->foreign('contacted_by_leader_id')->references('id')->on('users')->nullOnDelete();
            }
        });

        // 3. Update roles in `users` table
        DB::table('users')->where('role', 'main_marketing')->update(['role' => 'leader']);
        DB::table('users')->where('role', 'assistant_marketing')->update(['role' => 'sub_leader']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'leader_id')) {
                $table->dropForeign(['leader_id']);
                $table->renameColumn('leader_id', 'main_marketing_id');
                $table->foreign('main_marketing_id')->references('id')->on('users')->nullOnDelete();
            }
        });

        Schema::table('contacts', function (Blueprint $table) {
            if (Schema::hasColumn('contacts', 'sub_leader_id')) {
                $table->dropForeign(['sub_leader_id']);
                $table->renameColumn('sub_leader_id', 'assistant_marketing_id');
                $table->foreign('assistant_marketing_id')->references('id')->on('users')->nullOnDelete();
            }

            if (Schema::hasColumn('contacts', 'leader_id')) {
                $table->dropForeign(['leader_id']);
                $table->renameColumn('leader_id', 'main_marketing_id');
                $table->foreign('main_marketing_id')->references('id')->on('users')->nullOnDelete();
            }

            if (Schema::hasColumn('contacts', 'contacted_by_leader_id')) {
                $table->dropForeign(['contacted_by_leader_id']);
                $table->renameColumn('contacted_by_leader_id', 'contacted_by_main_marketing_id');
                $table->foreign('contacted_by_main_marketing_id')->references('id')->on('users')->nullOnDelete();
            }
        });

        DB::table('users')->where('role', 'leader')->update(['role' => 'main_marketing']);
        DB::table('users')->where('role', 'sub_leader')->update(['role' => 'assistant_marketing']);
    }
};
