<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('script_versions', function (Blueprint $table) {
            $table->foreignId('project_id')
                ->constrained('projects')
                ->restrictOnDelete();

            $table->unsignedInteger('version_number');

            $table->string('title', 160)->nullable();

            $table->text('content');

            $table->text('change_summary')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->unique(
                ['project_id', 'version_number'],
                'script_versions_project_version_unique'
            );

            $table->index(
                ['project_id', 'created_at'],
                'script_versions_project_created_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('script_versions', function (Blueprint $table) {
            $table->dropIndex(
                'script_versions_project_created_index'
            );

            $table->dropUnique(
                'script_versions_project_version_unique'
            );

            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('project_id');

            $table->dropColumn([
                'version_number',
                'title',
                'content',
                'change_summary',
            ]);
        });
    }
};