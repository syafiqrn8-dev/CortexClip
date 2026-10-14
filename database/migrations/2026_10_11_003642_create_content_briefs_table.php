<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('content_briefs', function (Blueprint $table) {
            $table->id();

            // SETIAP PROJECT HANYA MEMILIKI 1 BRIEF
            $table->foreignId('project_id')
                    ->unique()
                    ->constrained('projects')
                    ->restrictOnDelete();

            // INFORMASI UTAMA BRIEF
            $table->string('topic', 240);
            $table->text('target_audience')->nullable();
            $table->string('content_goal', 120)->nullable();
            $table->string('tone', 80)->nullable();
            $table->text('additional_instructions')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_briefs');
    }
};
