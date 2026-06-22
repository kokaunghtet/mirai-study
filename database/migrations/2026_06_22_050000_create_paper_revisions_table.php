<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paper_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paper_id')->constrained('exam_papers')->cascadeOnDelete();
            $table->foreignId('editor_id')->constrained('users')->cascadeOnDelete();
            $table->string('action'); // 'uploaded' | 'edited'
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paper_revisions');
    }
};
