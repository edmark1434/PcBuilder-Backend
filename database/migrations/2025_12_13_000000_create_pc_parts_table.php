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
        Schema::create('pc_parts', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // Processors, Motherboards, Memory, Storage, Graphics Cards, Power Supply, Cases, Cooling
            $table->string('external_id')->unique(); // Original ID from CSV
            $table->string('vendor'); // Brand name
            $table->string('title'); // Product name/title
            $table->decimal('price', 12, 2); // Price in Philippine Peso
            $table->text('image')->nullable(); // Image URL
            $table->text('link')->nullable(); // Product link
            $table->timestamps();
            
            // Add indexes for faster queries
            $table->index('type');
            $table->index('vendor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pc_parts');
    }
};
