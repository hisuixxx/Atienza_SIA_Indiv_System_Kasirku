<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table): void {
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->text('remarks')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table): void {
            $table->dropForeign(['order_id']);
            $table->dropColumn(['order_id', 'unit_cost', 'remarks']);
        });
    }
};
