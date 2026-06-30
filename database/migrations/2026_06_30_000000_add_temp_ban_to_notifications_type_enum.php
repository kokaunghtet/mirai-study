<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('like_post', 'comment_post', 'follow_user', 'system', 'report_reviewed', 'temp_ban') NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('like_post', 'comment_post', 'follow_user', 'system', 'report_reviewed') NOT NULL");
        }
    }
};
