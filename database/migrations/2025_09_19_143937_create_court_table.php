<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('courts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->time('start_time')->default('08:00:00');
            $table->time('end_time')->default('12:00:00');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('courts')->insert([
            ['name' => 'Sân 1', 'start_time' => '08:00:00', 'end_time' => '12:00:00', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sân 2', 'start_time' => '08:00:00', 'end_time' => '12:00:00', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sân 3', 'start_time' => '08:00:00', 'end_time' => '12:00:00', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courts');
    }
};
