<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scan_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('event_participant_id')->nullable()->constrained('event_participants')->nullOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('event_sessions')->nullOnDelete();
            $table->string('device_uuid')->nullable();
            $table->foreignUuid('operator_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('source', ['qr', 'manual']);
            $table->enum('result', ['accepted', 'rejected', 'warning']);
            $table->string('code');
            $table->string('message');
            $table->string('token_fingerprint')->nullable();
            $table->string('manual_note')->nullable();
            $table->dateTime('scanned_at');
            $table->timestamps();

            $table->index(['event_id', 'scanned_at']);
            $table->index(['event_participant_id', 'scanned_at']);
            $table->index(['result', 'code']);
            $table->index('device_uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_attempts');
    }
};
