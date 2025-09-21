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
        Schema::create('match_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->enum('team_side', ['A','B']);
            $table->enum('result', ['win','lose','draw'])->nullable();
            $table->decimal('points', 5, 2)->default(0); // win=1, lose=0, draw=0.5 (mặc định)
            $table->timestamps();
            $table->unique(['match_id','user_id']);
            $table->index(['user_id','result']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_participants');
    }
};
