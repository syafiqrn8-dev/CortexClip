<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ai_generations', function (Blueprint $table) {
            $table->foreignId('project_id')
                ->constrained('projects')
                ->restrictOnDelete();

            $table->string('task_type', 40);
            $table->string('provider', 40)->nullable();
            $table->string('model', 100)->nullable();

            $table->string('status', 20)->default('pending');

            $table->jsonb('input_summary')->nullable();
            $table->jsonb('result_summary')->nullable();

            $table->string('error_code', 80)->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->index(
                ['project_id', 'task_type', 'created_at'],
                'ai_generations_project_task_created_index'
            );

            $table->index(
                ['status', 'created_at'],
                'ai_generations_status_created_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_generations', function (Blueprint $table) {
            $table->dropIndex(
                'ai_generations_project_task_created_index'
            );

            $table->dropIndex(
                'ai_generations_status_created_index'
            );

            $table->dropConstrainedForeignId('project_id');

            $table->dropColumn([
                'task_type',
                'provider',
                'model',
                'status',
                'input_summary',
                'result_summary',
                'error_code',
                'started_at',
                'finished_at',
            ]);
        });
    }
};