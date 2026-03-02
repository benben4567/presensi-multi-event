<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('phone_e164')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('phone_e164');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
