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
        Schema::create('favorite_builds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('build_id');
            $table->decimal('total_price', 10, 2);
            $table->json('parts_data'); // Store parts as JSON
            $table->json('build_data'); // Store entire build data as JSON
            $table->timestamps();
            
            // Add unique constraint to prevent duplicate favorites
            $table->unique(['user_id', 'build_id', 'total_price']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favorite_builds');
    }
};