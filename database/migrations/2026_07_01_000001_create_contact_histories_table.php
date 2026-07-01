<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_histories', function (Blueprint $table) {
            $table->id();

            // Reference to the original contact (nullable since contacts are deleted after archival)
            $table->unsignedBigInteger('contact_id')->nullable();

            // Contact data snapshot (mirrors the contacts table)
            $table->string('contact_name')->nullable();
            $table->string('phone');
            $table->string('normalized_phone')->nullable();
            $table->string('period_key', 7)->nullable(); // original period key, e.g. "2026-06"

            // Ownership snapshot
            $table->unsignedBigInteger('team_id')->nullable();
            $table->unsignedBigInteger('sub_leader_id')->nullable();
            $table->unsignedBigInteger('leader_id')->nullable();
            $table->unsignedBigInteger('input_by')->nullable();

            // Status snapshot
            $table->string('status', 32)->default('belum_dihubungi');
            $table->boolean('is_contacted')->default(false);
            $table->unsignedBigInteger('status_updated_by')->nullable();
            $table->timestamp('status_updated_at')->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->unsignedBigInteger('contacted_by_leader_id')->nullable();

            // Archive metadata
            $table->string('archive_period', 7); // e.g. "2026-06" (month being archived)
            $table->timestamp('archived_at');
            $table->timestamp('original_created_at')->nullable();

            $table->timestamps();

            // Performance indexes
            $table->index('phone');
            $table->index('normalized_phone');
            $table->index('archive_period');
            $table->index('archived_at');
            $table->index('contact_id');
            $table->index('sub_leader_id');
            $table->index('leader_id');
            $table->index('team_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_histories');
    }
};
