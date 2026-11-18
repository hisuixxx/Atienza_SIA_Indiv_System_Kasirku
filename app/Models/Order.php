<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PREPARING = 'preparing';
    public const STATUS_READY_FOR_PICKUP = 'ready_for_pickup';
    public const STATUS_READY = 'ready';
    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'tax' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItems::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItems::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest();
    }

    public function cancellation(): HasOne
    {
        return $this->hasOne(OrderCancellation::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasManyThrough(Refund::class, Payment::class);
    }

    public function getTotalPriceAttribute()
    {
        if ($this->total_amount > 0) {
            return $this->total_amount;
        }

        return $this->orderItems->sum(function ($item) {
            return $item->subtotal;
        });
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status ?? $this->order_status) {
            self::STATUS_PENDING => 'badge-pending',
            self::STATUS_CONFIRMED => 'badge-info',
            self::STATUS_PREPARING => 'badge-warning',
            self::STATUS_READY, self::STATUS_READY_FOR_PICKUP => 'badge-primary',
            self::STATUS_OUT_FOR_DELIVERY => 'badge-info',
            self::STATUS_COMPLETED => 'badge-success',
            self::STATUS_CANCELLED => 'badge-danger',
            default => 'badge-secondary',
        };
    }
}
