<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitations', function (Blueprint $table): void {
            // Raw token stored for QR regeneration; token_hash is used for scan lookups.
            $table->string('token', 128)->nullable()->after('token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table): void {
            $table->dropColumn('token');
        });
    }
};
