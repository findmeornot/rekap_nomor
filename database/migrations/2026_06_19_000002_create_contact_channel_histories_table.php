<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_channel_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('marketing_channel'); // toploker, topmatch, kerja_malam
            $table->foreignId('marketing_user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_contacted')->default(false);
            $table->timestamp('contacted_at')->nullable();
            $table->foreignId('status_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('status_updated_at')->nullable();
            $table->timestamps();

            // One record per contact per channel
            $table->unique(['contact_id', 'marketing_channel']);
            $table->index('marketing_channel');
            $table->index('marketing_user_id');
            $table->index(['marketing_channel', 'is_contacted']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_channel_histories');
    }
};
