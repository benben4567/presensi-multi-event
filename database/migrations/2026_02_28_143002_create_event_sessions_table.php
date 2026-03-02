<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('name');
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->enum('type', ['day'])->default('day');
            $table->timestamps();

            $table->unique(['event_id', 'start_at']);
            $table->index(['event_id', 'start_at', 'end_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_sessions');
    }
};
