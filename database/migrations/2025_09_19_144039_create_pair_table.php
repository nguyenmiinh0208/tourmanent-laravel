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
        Schema::create('pairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phase_id')->constrained('phases')->cascadeOnDelete();
            $table->foreignId('user_lo_id')->constrained('users');
            $table->foreignId('user_hi_id')->constrained('users');
            $table->enum('type', ['MD','WD','XD']); // Men/Women/Mixed Double
            $table->boolean('created_by_algorithm')->default(false);
            $table->timestamps();

            $table->unique(['phase_id','user_lo_id','user_hi_id']);
            $table->index(['phase_id','type']);
            $table->index(['created_by_algorithm']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pairs');
    }
};
