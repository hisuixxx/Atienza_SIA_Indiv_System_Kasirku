<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\DailyClosingReport;
use App\Models\User;
use App\Services\SalesReportService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class GenerateDailyClosingReports extends Command
{
    protected $signature = 'app:generate-daily-closing-reports {--date= : Closing date (defaults to yesterday)}';

    protected $description = 'Generate daily closing reports for all branches';

    public function handle(SalesReportService $salesReports): int
    {
        $date = CarbonImmutable::parse($this->option('date') ?: today()->subDay()->toDateString())->startOfDay();
        if ($date->greaterThan(today())) {
            $this->error('A daily closing report cannot be generated for a future date.');

            return self::FAILURE;
        }

        $branches = Branch::query()->orderBy('id')->get();
        foreach ($branches as $branch) {
            $report = $salesReports->generateClosingReport($branch, $date);
            $this->deliverReport($branch, $report);
        }

        $this->info("Generated or refreshed {$branches->count()} closing report(s) for {$date->toDateString()}.");

        return self::SUCCESS;
    }

    private function deliverReport(Branch $branch, DailyClosingReport $report): void
    {
        if (in_array(config('mail.default'), ['log', 'array'], true)) {
            return;
        }

        $recipients = User::query()
            ->where('is_active', true)
            ->whereNotNull('email')
            ->where(function ($query) use ($branch): void {
                $query->whereIn('role', [User::ROLE_ADMIN, 'administrator'])
                    ->orWhere(function ($staff) use ($branch): void {
                        $staff->whereIn('role', [User::ROLE_STAFF, 'branch_manager'])
                            ->whereHas('branches', fn ($branches) => $branches->where('branches.id', $branch->id));
                    });
            })
            ->pluck('email')
            ->all();

        if (! $recipients) {
            return;
        }

        $body = implode(PHP_EOL, [
            "Daily closing report: {$branch->name}",
            'Closing date: '.CarbonImmutable::parse($report->closing_date)->toDateString(),
            "Total orders: {$report->total_orders}",
            "Completed orders: {$report->completed_orders}",
            "Cancelled orders: {$report->cancelled_orders}",
            'Cash sales: '.number_format((float) $report->cash_sales, 2),
            'GCash sales: '.number_format((float) $report->gcash_sales, 2),
            'Refunds: '.number_format((float) $report->refund_total, 2),
            'Net sales: '.number_format((float) $report->net_sales, 2),
        ]);

        Mail::raw($body, function ($message) use ($recipients, $branch, $report): void {
            $date = CarbonImmutable::parse($report->closing_date)->toDateString();
            $message->to($recipients)->subject("Daily closing: {$branch->name} - {$date}");
        });
    }
}
