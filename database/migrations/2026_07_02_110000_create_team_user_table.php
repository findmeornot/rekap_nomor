<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('team_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        // Migrate existing sub-leaders from users.team_id to team_user
        $subLeaders = DB::table('users')
            ->where('role', 'sub_leader')
            ->whereNotNull('team_id')
            ->get();
            
        foreach ($subLeaders as $subLeader) {
            DB::table('team_user')->insert([
                'team_id' => $subLeader->team_id,
                'user_id' => $subLeader->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_user');
    }
};
