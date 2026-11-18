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
        Schema::table('refunds', function (Blueprint $table) {
            foreach (['requested_by', 'authorized_by', 'processed_by'] as $column) {
                if (! Schema::hasColumn('refunds', $column)) {
                    $table->foreignId($column)->nullable()->constrained('users')->nullOnDelete();
                }
            }
            if (! Schema::hasColumn('refunds', 'authorized_at')) {
                $table->timestamp('authorized_at')->nullable();
            }
            if (! Schema::hasColumn('refunds', 'processing_reference')) {
                $table->string('processing_reference', 120)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            foreach (['requested_by', 'authorized_by', 'processed_by'] as $column) {
                if (Schema::hasColumn('refunds', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }
            foreach (['authorized_at', 'processing_reference'] as $column) {
                if (Schema::hasColumn('refunds', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
