<?php

use App\Models\Branch;
use App\Models\DailyClosingReport;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    Carbon::setTestNow('2025-06-18 12:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

test('admin can generate and inspect an idempotent branch closing report', function () {
    $branch = Branch::create(['name' => 'Main', 'code' => 'MAIN', 'is_active' => true]);
    $admin = User::factory()->admin()->create();
    $completedOrder = Order::factory()->create([
        'branch_id' => $branch->id,
        'status' => 'completed',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $payment = Payment::factory()->create([
        'order_id' => $completedOrder->id,
        'amount' => 800,
        'payment_method' => 'cash',
        'status' => 'completed',
        'paid_at' => now(),
    ]);
    $cancelledOrder = Order::factory()->create([
        'branch_id' => $branch->id,
        'status' => 'cancelled',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    Payment::factory()->create([
        'order_id' => $cancelledOrder->id,
        'amount' => 200,
        'payment_method' => 'gcash',
        'status' => 'pending',
        'paid_at' => null,
    ]);
    DB::table('refunds')->insert([
        'payment_id' => $payment->id,
        'amount' => 100,
        'reason' => 'Approved adjustment',
        'status' => 'processed_manual',
        'processed_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($admin)->post(route('admin.reports.daily-closing.generate'), [
        'branch_id' => $branch->id,
        'closing_date' => '2025-06-18',
    ])->assertRedirect();

    $report = DailyClosingReport::query()->sole();
    expect($report->total_orders)->toBe(2)
        ->and($report->completed_orders)->toBe(1)
        ->and($report->cancelled_orders)->toBe(1)
        ->and((float) $report->cash_sales)->toBe(800.0)
        ->and((float) $report->gcash_sales)->toBe(0.0)
        ->and((float) $report->gross_sales)->toBe(800.0)
        ->and((float) $report->refund_total)->toBe(100.0)
        ->and((float) $report->net_sales)->toBe(700.0)
        ->and($report->generated_by_user_id)->toBe($admin->id);

    $this->get(route('admin.reports.daily-closing.show', $report))
        ->assertInertia(fn ($page) => $page
            ->where('module', 'closing-detail')
            ->where('closingReport.id', $report->id)
            ->where('closingReport.net_sales', 700)
        );

    $this->post(route('admin.reports.daily-closing.generate'), [
        'branch_id' => $branch->id,
        'closing_date' => '2025-06-18',
    ])->assertRedirect();

    expect(DailyClosingReport::count())->toBe(1);
});

test('daily closing command creates one report for every branch and can safely rerun', function () {
    Branch::create(['name' => 'Active', 'code' => 'ACTIVE', 'is_active' => true]);
    Branch::create(['name' => 'Inactive', 'code' => 'INACTIVE', 'is_active' => false]);

    $this->artisan('app:generate-daily-closing-reports', ['--date' => '2025-06-17'])
        ->assertSuccessful();

    expect(DailyClosingReport::whereDate('closing_date', '2025-06-17')->count())->toBe(2);

    $this->artisan('app:generate-daily-closing-reports', ['--date' => '2025-06-17'])
        ->assertSuccessful();

    expect(DailyClosingReport::whereDate('closing_date', '2025-06-17')->count())->toBe(2);
});

test('only admins can generate or view daily closing reports', function () {
    $branch = Branch::create(['name' => 'Main', 'code' => 'MAIN']);
    $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
    $staff->branches()->attach($branch->id);

    $this->actingAs($staff)->post(route('admin.reports.daily-closing.generate'), [
        'branch_id' => $branch->id,
        'closing_date' => '2025-06-18',
    ])->assertForbidden();

    expect(DailyClosingReport::count())->toBe(0);
});
