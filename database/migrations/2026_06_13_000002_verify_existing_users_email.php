<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfill existing accounts as verified so only new sign-ups face the
     * email-verification gate introduced alongside this migration.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    /**
     * Irreversible: we can't tell which users were backfilled vs. genuinely verified.
     */
    public function down(): void
    {
        // no-op
    }
};
