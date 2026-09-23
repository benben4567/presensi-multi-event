<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Encrypted payloads are longer than the original varchar(128).
        // SQLite has no real varchar length limit, so only MySQL needs this.
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE invitations MODIFY token TEXT NULL');
        }

        DB::table('invitations')->whereNotNull('token')->orderBy('id')->each(function (object $row): void {
            DB::table('invitations')
                ->where('id', $row->id)
                ->update(['token' => Crypt::encryptString($row->token)]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('invitations')->whereNotNull('token')->orderBy('id')->each(function (object $row): void {
            DB::table('invitations')
                ->where('id', $row->id)
                ->update(['token' => Crypt::decryptString($row->token)]);
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE invitations MODIFY token VARCHAR(128) NULL');
        }
    }
};
