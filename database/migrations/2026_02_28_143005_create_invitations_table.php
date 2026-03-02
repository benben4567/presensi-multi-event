<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_participant_id')->unique()->constrained('event_participants')->cascadeOnDelete();
            $table->string('token_hash')->unique();
            $table->dateTime('issued_at');
            $table->dateTime('expires_at');
            $table->dateTime('revoked_at')->nullable();
            $table->string('revoked_reason', 100)->nullable();
            $table->foreignUuid('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('expires_at');
            $table->index('revoked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
