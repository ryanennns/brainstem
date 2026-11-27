<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memories', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device');
            $table->enum('source', ['claude_code', 'codex', 'opencode', 'ignition']);
            $table->string('session_id');
            $table->longText('summary');
            $table->timestamps();

            $table->unique(['user_id', 'source', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memories');
    }
};
