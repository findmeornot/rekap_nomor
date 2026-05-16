<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'main_marketing_id')) {
                $table->foreignId('main_marketing_id')
                    ->nullable()
                    ->after('role')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        Schema::table('contacts', function (Blueprint $table) {
            if (! Schema::hasColumn('contacts', 'assistant_marketing_id')) {
                $table->foreignId('assistant_marketing_id')
                    ->nullable()
                    ->after('phone')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('contacts', 'main_marketing_id')) {
                $table->foreignId('main_marketing_id')
                    ->nullable()
                    ->after('assistant_marketing_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('contacts', 'contacted_by_main_marketing_id')) {
                $table->foreignId('contacted_by_main_marketing_id')
                    ->nullable()
                    ->after('contacted_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        DB::table('users')
            ->where('role', 'leader')
            ->update(['role' => 'main_marketing']);

        DB::table('users')
            ->where('role', 'sub_leader')
            ->update(['role' => 'assistant_marketing']);

        $contactsUpdate = [];

        if (Schema::hasColumn('contacts', 'leader_id')) {
            $contactsUpdate['main_marketing_id'] = DB::raw('leader_id');
        }

        if (Schema::hasColumn('contacts', 'sub_leader_id')) {
            $contactsUpdate['assistant_marketing_id'] = DB::raw('sub_leader_id');
        }

        if (Schema::hasColumn('contacts', 'contacted_by_leader_id')) {
            $contactsUpdate['contacted_by_main_marketing_id'] = DB::raw('contacted_by_leader_id');
        }

        if (! empty($contactsUpdate)) {
            DB::table('contacts')->update($contactsUpdate);
        }

        Schema::table('contacts', function (Blueprint $table) {
            if (Schema::hasColumn('contacts', 'sub_leader_id')) {
                $table->dropConstrainedForeignId('sub_leader_id');
            }
            if (Schema::hasColumn('contacts', 'leader_id')) {
                $table->dropConstrainedForeignId('leader_id');
            }
            if (Schema::hasColumn('contacts', 'contacted_by_leader_id')) {
                $table->dropConstrainedForeignId('contacted_by_leader_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'leader_id')) {
                $table->dropConstrainedForeignId('leader_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->foreignId('sub_leader_id')
                ->after('phone')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('leader_id')
                ->after('sub_leader_id')
                ->constrained('users')
                ->cascadeOnDelete();

            if (! Schema::hasColumn('contacts', 'contacted_by_leader_id')) {
                $table->foreignId('contacted_by_leader_id')
                    ->nullable()
                    ->after('contacted_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        $contactsUpdate = [];

        if (Schema::hasColumn('contacts', 'main_marketing_id')) {
            $contactsUpdate['leader_id'] = DB::raw('main_marketing_id');
        }

        if (Schema::hasColumn('contacts', 'assistant_marketing_id')) {
            $contactsUpdate['sub_leader_id'] = DB::raw('assistant_marketing_id');
        }

        if (Schema::hasColumn('contacts', 'contacted_by_main_marketing_id')) {
            $contactsUpdate['contacted_by_leader_id'] = DB::raw('contacted_by_main_marketing_id');
        }

        if (! empty($contactsUpdate)) {
            DB::table('contacts')->update($contactsUpdate);
        }

        Schema::table('contacts', function (Blueprint $table) {
            if (Schema::hasColumn('contacts', 'assistant_marketing_id')) {
                $table->dropConstrainedForeignId('assistant_marketing_id');
            }
            if (Schema::hasColumn('contacts', 'main_marketing_id')) {
                $table->dropConstrainedForeignId('main_marketing_id');
            }
            if (Schema::hasColumn('contacts', 'contacted_by_main_marketing_id')) {
                $table->dropConstrainedForeignId('contacted_by_main_marketing_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('leader_id')
                ->nullable()
                ->after('role')
                ->constrained('users')
                ->nullOnDelete();
        });

        DB::table('users')
            ->where('role', 'main_marketing')
            ->update(['role' => 'leader']);

        DB::table('users')
            ->where('role', 'assistant_marketing')
            ->update(['role' => 'sub_leader']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('main_marketing_id');
        });
    }
};
