<?php

use App\Models\Branch;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function createOperationsStaff(Branch $branch, string $role = 'staff'): User
{
    $user = User::factory()->create(['role' => $role]);
    $user->branches()->attach($branch->id);

    return $user;
}

test('gcash payments require branch staff verification and rejection reasons', function () {
    $branch = Branch::create(['name' => 'Main', 'code' => 'MAIN']);
    $staff = createOperationsStaff($branch);
    $order = Order::factory()->create(['branch_id' => $branch->id, 'status' => 'pending']);
    $payment = Payment::factory()->create(['order_id' => $order->id, 'payment_method' => 'gcash', 'status' => 'pending']);

    $this->actingAs($staff)->post(route('admin.payments.reference', $payment), ['reference_number' => 'GC-123'])->assertRedirect();
    $verification = DB::table('payment_verifications')->where('payment_id', $payment->id)->first();
    expect($verification->status)->toBe('pending');

    $this->post(route('admin.payments.verification.decide', $verification->id), ['decision' => 'reject'])->assertSessionHasErrors('review_note');
    $this->post(route('admin.payments.verification.decide', $verification->id), ['decision' => 'reject', 'review_note' => 'Reference does not match'])
        ->assertRedirect();
    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'status' => 'rejected',
        'verification_status' => 'rejected',
        'verified_by_user_id' => $staff->id,
        'rejection_reason' => 'Reference does not match',
    ]);

    $this->post(route('admin.payments.verification.decide', $verification->id), ['decision' => 'approve'])
        ->assertNotFound();
    $this->assertDatabaseCount('payment_verifications', 1);

    $secondOrder = Order::factory()->create(['branch_id' => $branch->id, 'status' => 'pending']);
    $secondPayment = Payment::factory()->create(['order_id' => $secondOrder->id, 'payment_method' => 'gcash', 'status' => 'pending']);
    $this->post(route('admin.payments.reference', $secondPayment), ['reference_number' => 'GC-456'])->assertRedirect();
    $secondVerification = DB::table('payment_verifications')->where('payment_id', $secondPayment->id)->first();
    $this->post(route('admin.payments.verification.decide', $secondVerification->id), ['decision' => 'approve'])->assertRedirect();
    $this->assertDatabaseHas('payments', [
        'id' => $secondPayment->id,
        'status' => 'completed',
        'verification_status' => 'verified',
        'verified_by_user_id' => $staff->id,
    ]);
});

test('cancellation records employee, reason, status history, and customer notification', function () {
    $branch = Branch::create(['name' => 'Main', 'code' => 'MAIN']);
    $staff = createOperationsStaff($branch);
    $customer = User::factory()->create(['role' => 'customer']);
    $order = Order::factory()->create(['branch_id' => $branch->id, 'user_id' => $customer->id, 'status' => 'pending']);
    Payment::factory()->create(['order_id' => $order->id, 'payment_method' => 'cash', 'status' => 'completed']);

    $this->actingAs($staff)->post(route('admin.orders.cancellation', $order), ['reason' => 'Customer requested cancellation'])
        ->assertRedirect();
    $this->assertDatabaseHas('cancellations', [
        'order_id' => $order->id,
        'requested_by' => $staff->id,
        'reason' => 'Customer requested cancellation',
        'status' => 'cancelled',
    ]);
    $this->assertDatabaseHas('order_status_histories', [
        'order_id' => $order->id,
        'branch_id' => $branch->id,
        'from_status' => 'pending',
        'to_status' => 'cancelled',
        'user_id' => $staff->id,
    ]);
    $this->assertDatabaseHas('notifications', ['user_id' => $customer->id]);
    expect($order->fresh()->status)->toBe('cancelled');

    $this->post(route('admin.orders.cancellation', $order), ['reason' => 'Duplicate'])->assertStatus(422);
});

test('refund approval cannot complete processing without confirmation and reference', function () {
    $branch = Branch::create(['name' => 'Main', 'code' => 'MAIN']);
    $admin = User::factory()->create(['role' => 'admin']);
    $order = Order::factory()->create(['branch_id' => $branch->id]);
    $payment = Payment::factory()->create(['order_id' => $order->id, 'amount' => 1000, 'status' => 'completed']);
    $refundId = DB::table('refunds')->insertGetId([
        'payment_id' => $payment->id,
        'requested_by' => $admin->id,
        'user_id' => $admin->id,
        'amount' => 250,
        'reason' => 'Order adjustment',
        'status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($admin)->post(route('admin.refunds.review', $refundId), ['decision' => 'approve'])->assertRedirect();
    $this->post(route('admin.refunds.process', $refundId), [])->assertSessionHasErrors([
        'processing_confirmation',
        'processing_reference',
    ]);
    $this->assertDatabaseHas('refunds', ['id' => $refundId, 'status' => 'approved_manual', 'processed_at' => null]);

    $this->post(route('admin.refunds.process', $refundId), [
        'processing_confirmation' => true,
        'processing_reference' => 'REFUND-123',
    ])->assertRedirect();
    $this->assertDatabaseHas('refunds', [
        'id' => $refundId,
        'status' => 'processed_manual',
        'processed_by' => $admin->id,
        'processing_reference' => 'REFUND-123',
    ]);
});

