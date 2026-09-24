<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_participants', function (Blueprint $table): void {
            $table->dateTime('invitation_email_sent_at')->nullable()->after('access_updated_by');
        });
    }

    public function down(): void
    {
        Schema::table('event_participants', function (Blueprint $table): void {
            $table->dropColumn('invitation_email_sent_at');
        });
    }
};
