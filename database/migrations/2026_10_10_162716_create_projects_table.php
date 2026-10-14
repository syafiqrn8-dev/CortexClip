<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            // PEMILIK PROJECT
            $table->foreignId('user_id')
                    ->constrained('users')
                    ->restrictOnDelete();

            // INFORMASI PROJECT
            $table->string('title', 160);
            $table->string('platform', 32);
            $table->string('aspect_ratio', 16)->default('9:16');

            $table->unsignedInteger('target_duration_seconds')->nullable();
            
            // STATUS DAN TAHAPAN PENGERJAAN PROJECT
            $table->string('status', 24)->default('draft');
            $table->string('current_stage', 24)->default('brief');

            // WAKTU PEMBUATAN DAN PEMBARUAN PROJECT
            $table->timestamps();

            // Soft Delete : Project ditandai terhapus, tapi bukan langsung terhapus di database
            $table->softDeletes();

            // Indeks untuk daftar dan filter project pengguna/user
            $table->index(['user_id', 'updated_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
