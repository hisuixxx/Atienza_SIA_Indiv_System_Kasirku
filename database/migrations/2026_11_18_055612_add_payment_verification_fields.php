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
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'verification_status')) {
                $table->string('verification_status', 30)->nullable()->default('pending')->index();
            }
        });

        Schema::table('payment_verifications', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_verifications', 'submitted_by')) {
                $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('payment_verifications', 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('payment_verifications', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable();
            }
            if (! Schema::hasColumn('payment_verifications', 'review_note')) {
                $table->text('review_note')->nullable();
            }
        });

        DB::table('payment_verifications')->where('status', 'approved')->update(['status' => 'verified']);
        DB::table('payment_verifications')
            ->whereNull('reviewed_by')
            ->whereNotNull('verified_by')
            ->update(['reviewed_by' => DB::raw('verified_by')]);
        DB::table('payment_verifications')
            ->whereNull('reviewed_at')
            ->whereNotNull('verified_at')
            ->update(['reviewed_at' => DB::raw('verified_at')]);
        DB::table('payment_verifications')
            ->whereNull('review_note')
            ->whereNotNull('notes')
            ->update(['review_note' => DB::raw('notes')]);

        DB::table('payments')
            ->where('payment_method', 'gcash')
            ->whereIn('status', ['completed', 'approved'])
            ->update(['verification_status' => 'verified']);
        DB::table('payments')
            ->where('payment_method', 'gcash')
            ->where('status', 'rejected')
            ->update(['verification_status' => 'rejected']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_verifications', function (Blueprint $table) {
            foreach (['submitted_by', 'reviewed_by'] as $column) {
                if (Schema::hasColumn('payment_verifications', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }
            if (Schema::hasColumn('payment_verifications', 'reviewed_at')) {
                $table->dropColumn('reviewed_at');
            }
            if (Schema::hasColumn('payment_verifications', 'review_note')) {
                $table->dropColumn('review_note');
            }
        });
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'verification_status')) {
                $table->dropIndex(['verification_status']);
                $table->dropColumn('verification_status');
            }
        });
    }
};
