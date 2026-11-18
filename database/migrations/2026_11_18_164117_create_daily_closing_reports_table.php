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
        Schema::create('daily_closing_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->date('closing_date');
            $table->unsignedInteger('total_orders')->default(0);
            $table->unsignedInteger('completed_orders')->default(0);
            $table->unsignedInteger('cancelled_orders')->default(0);
            $table->decimal('cash_sales', 12, 2)->default(0);
            $table->decimal('gcash_sales', 12, 2)->default(0);
            $table->decimal('other_sales', 12, 2)->default(0);
            $table->decimal('gross_sales', 12, 2)->default(0);
            $table->decimal('refund_total', 12, 2)->default(0);
            $table->decimal('net_sales', 12, 2)->default(0);
            $table->foreignId('generated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
            $table->unique(['branch_id', 'closing_date']);
            $table->index('closing_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_closing_reports');
    }
};
