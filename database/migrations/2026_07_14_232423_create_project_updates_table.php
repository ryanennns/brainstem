<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_updates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('type', ['code_change', 'plan_updated', 'miscellaneous']);
            $table->text('summary');

            $table->foreignUuid('project_id')
                ->references('id')
                ->on('projects')
                ->cascadeOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_updates');
    }
};
