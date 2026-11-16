<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    public const METHOD_CASH = 'cash';
    public const METHOD_GCASH = 'gcash';
    public const METHOD_CASH_ON_PICKUP = 'cash_on_pickup';
    public const METHOD_CASH_ON_DELIVERY = 'cash_on_delivery';
    public const METHOD_MIDTRANS = 'midtrans';

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_FAILED = 'failed';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'verified_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    public function getStatusAttribute($value)
    {
        return $value ?? $this->attributes['payment_status'] ?? 'pending';
    }

    public function getPaymentStatusAttribute($value)
    {
        return $value ?? $this->attributes['status'] ?? 'pending';
    }

    public function getReceiptUrlAttribute(): ?string
    {
        $path = $this->receipt_image_path ?? $this->receipt_image ?? null;
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/' . $path);
    }
}
