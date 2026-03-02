<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_participants', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained('participants')->cascadeOnDelete();
            $table->json('meta')->nullable();
            $table->enum('access_status', ['allowed', 'disabled', 'blacklisted'])->default('allowed');
            $table->string('access_reason', 100)->nullable();
            $table->dateTime('access_updated_at')->nullable();
            $table->foreignUuid('access_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['event_id', 'participant_id']);
            $table->index(['event_id', 'access_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_participants');
    }
};
