<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customization_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_customization_id')->constrained('product_customizations')->cascadeOnDelete();
            $table->string('name', 100);
            $table->decimal('additional_price', 10, 2)->default(0.00);
            $table->decimal('price_modifier', 10, 2)->default(0.00);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customization_options');
    }
};
