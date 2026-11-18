<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class DailyClosingReport extends Model
{
    protected $fillable = [
        'branch_id',
        'closing_date',
        'total_orders',
        'completed_orders',
        'cancelled_orders',
        'cash_sales',
        'gcash_sales',
        'other_sales',
        'gross_sales',
        'refund_total',
        'net_sales',
        'generated_by_user_id',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'cash_sales' => 'decimal:2',
            'gcash_sales' => 'decimal:2',
            'other_sales' => 'decimal:2',
            'gross_sales' => 'decimal:2',
            'refund_total' => 'decimal:2',
            'net_sales' => 'decimal:2',
            'generated_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by_user_id');
    }
}
