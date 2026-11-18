<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_status_histories', function (Blueprint $table) {
            if (! Schema::hasColumn('order_status_histories', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('order_id')->constrained('branches')->nullOnDelete();
            }
            if (! Schema::hasColumn('order_status_histories', 'notes')) {
                $table->text('notes')->nullable();
            }
        });

        DB::table('order_status_histories')
            ->whereNull('branch_id')
            ->orderBy('id')
            ->chunkById(100, function ($histories): void {
                foreach ($histories as $history) {
                    $branchId = DB::table('orders')->where('id', $history->order_id)->value('branch_id');
                    if ($branchId) {
                        DB::table('order_status_histories')->where('id', $history->id)->update(['branch_id' => $branchId]);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_status_histories', function (Blueprint $table) {
            if (Schema::hasColumn('order_status_histories', 'branch_id')) {
                $table->dropConstrainedForeignId('branch_id');
            }
            if (Schema::hasColumn('order_status_histories', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
