<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code')->nullable()->unique();
            $table->string('name');
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->enum('status', ['draft', 'open', 'closed'])->default('draft');
            $table->dateTime('override_until')->nullable();
            $table->json('settings')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index(['start_at', 'end_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
