<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branch_product_availability', function (Blueprint $table): void {
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_available')->default(true);
            $table->unique(['branch_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::table('branch_product_availability', function (Blueprint $table): void {
            $table->dropUnique(['branch_id', 'product_id']);
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['product_id']);
            $table->dropColumn(['branch_id', 'product_id', 'is_available']);
        });
    }
};
