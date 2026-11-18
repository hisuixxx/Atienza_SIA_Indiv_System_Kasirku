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
        Schema::table('cancellations', function (Blueprint $table) {
            if (! Schema::hasColumn('cancellations', 'requested_by')) {
                $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('cancellations', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable();
            }
            if (! Schema::hasColumn('cancellations', 'review_note')) {
                $table->text('review_note')->nullable();
            }
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cancellations', function (Blueprint $table) {
            foreach (['requested_by', 'cancelled_at', 'review_note'] as $column) {
                if (Schema::hasColumn('cancellations', $column)) {
                    if ($column === 'requested_by') {
                        $table->dropConstrainedForeignId($column);
                    } else {
                        $table->dropColumn($column);
                    }
                }
            }
        });
    }
};
