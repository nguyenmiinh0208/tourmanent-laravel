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
        Schema::create('phases', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['vong_loai', 'ban_ket', 'chung_ket'])->index();
            $table->string('name');
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->enum('status', ['draft','scheduled','playing','completed','archived'])->default('draft')->index();
            $table->unsignedInteger('matches_per_player')->default(1);
            $table->string('seed')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phases');
    }
};
