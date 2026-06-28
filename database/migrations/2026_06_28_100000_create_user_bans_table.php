<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_bans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['temporary', 'permanent']);
            $table->text('reason')->nullable();
            $table->foreignId('report_id')->nullable()->constrained('reports')->nullOnDelete();
            $table->foreignId('banned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable(); // null = permanent
            $table->timestamp('lifted_at')->nullable();  // set on unban or auto-expiry
            $table->foreignId('lifted_by')->nullable()->constrained('users')->nullOnDelete(); // null = auto-expired
            $table->timestamps();

            $table->index(['user_id', 'lifted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_bans');
    }
};
