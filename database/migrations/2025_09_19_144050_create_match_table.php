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
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phase_id')->constrained('phases')->cascadeOnDelete();
            $table->foreignId('time_slot_id')->nullable()->constrained('time_slots')->nullOnDelete();
            $table->foreignId('court_id')->nullable()->constrained('courts')->nullOnDelete();
            $table->enum('type', ['MD','WD','XD']);
            $table->foreignId('pair_a_id')->constrained('pairs');
            $table->foreignId('pair_b_id')->constrained('pairs');
            $table->enum('status', ['scheduled','playing','finished','canceled'])->default('scheduled')->index();
            $table->unsignedSmallInteger('score_team_a')->nullable();
            $table->unsignedSmallInteger('score_team_b')->nullable();
            $table->enum('winner', ['A','B','draw'])->nullable();
            $table->timestamps();

            // Ensure pair_a_id and pair_b_id are different (handled by application logic)
            $table->index(['pair_a_id', 'pair_b_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
