<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_agent_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('project_id')->constrained()->cascadeOnDelete();
            $table->string('agent');
            $table->string('session_id');
            $table->timestamps();

            $table->unique(['project_id', 'agent', 'session_id']);
            $table->unique(['id', 'project_id']);
        });

        Schema::table('project_updates', function (Blueprint $table): void {
            $table->unsignedBigInteger('project_agent_session_id')->nullable();
        });

        $now = now();

        DB::table('project_updates')
            ->select('project_id')
            ->distinct()
            ->pluck('project_id')
            ->each(function (string $projectId) use ($now): void {
                $sessionId = DB::table('project_agent_sessions')->insertGetId([
                    'project_id' => $projectId,
                    'agent' => 'legacy',
                    'session_id' => 'legacy',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('project_updates')
                    ->where('project_id', $projectId)
                    ->update(['project_agent_session_id' => $sessionId]);
            });

        Schema::table('project_updates', function (Blueprint $table): void {
            $table->unsignedBigInteger('project_agent_session_id')->nullable(false)->change();
            $table->foreign(['project_agent_session_id', 'project_id'])
                ->references(['id', 'project_id'])
                ->on('project_agent_sessions')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('project_updates', function (Blueprint $table): void {
            $table->dropForeign(['project_agent_session_id', 'project_id']);
            $table->dropColumn('project_agent_session_id');
        });

        Schema::dropIfExists('project_agent_sessions');
    }
};
