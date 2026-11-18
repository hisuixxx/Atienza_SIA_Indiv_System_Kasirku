<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;

class OrderStatusService
{
    private const TRANSITIONS = [
        Order::STATUS_PENDING => [Order::STATUS_CONFIRMED, Order::STATUS_PREPARING, Order::STATUS_CANCELLED],
        Order::STATUS_CONFIRMED => [Order::STATUS_PREPARING, Order::STATUS_CANCELLED],
        Order::STATUS_PREPARING => [Order::STATUS_READY, Order::STATUS_CANCELLED],
        Order::STATUS_READY => [Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_COMPLETED, Order::STATUS_CANCELLED],
        Order::STATUS_READY_FOR_PICKUP => [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED],
        Order::STATUS_OUT_FOR_DELIVERY => [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED],
    ];

    public function recordInitial(Order $order, ?User $actor, ?string $notes = null): void
    {
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'branch_id' => $order->branch_id,
            'from_status' => null,
            'to_status' => $order->status,
            'user_id' => $actor?->id,
            'notes' => $notes,
        ]);
    }

    public function transition(Order $order, string $next, ?User $actor, ?string $notes = null): void
    {
        abort_unless(in_array($next, self::TRANSITIONS[$order->status] ?? [], true), 422, 'Invalid order status transition.');

        $previous = $order->status;
        $order->update(['status' => $next, 'order_status' => $next]);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'branch_id' => $order->branch_id,
            'from_status' => $previous,
            'to_status' => $next,
            'user_id' => $actor?->id,
            'notes' => $notes,
        ]);
    }
}
