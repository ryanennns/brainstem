<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repositories', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('remote_url')->nullable();
            $table->string('default_branch')->nullable();
            $table->timestamps();
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->foreignUuid('repository_id')->nullable()->constrained()->nullOnDelete();
            $table->json('working_branches')->nullable();
        });

        DB::statement('UPDATE projects SET working_branches = git_branches');

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn('git_branches');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->json('git_branches')->nullable();
        });

        DB::statement('UPDATE projects SET git_branches = working_branches');

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('repository_id');
            $table->dropColumn('working_branches');
        });

        Schema::dropIfExists('repositories');
    }
};
