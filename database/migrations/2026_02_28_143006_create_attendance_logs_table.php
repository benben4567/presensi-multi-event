<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('event_participant_id')->constrained('event_participants')->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('event_sessions')->cascadeOnDelete();
            $table->enum('action', ['check_in', 'check_out']);
            $table->dateTime('scanned_at');
            $table->foreignId('device_id')->constrained('devices');
            $table->foreignUuid('operator_user_id')->constrained('users');
            $table->timestamps();

            $table->unique(['event_participant_id', 'session_id', 'action']);
            $table->index(['event_id', 'session_id', 'action']);
            $table->index(['event_participant_id', 'session_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
