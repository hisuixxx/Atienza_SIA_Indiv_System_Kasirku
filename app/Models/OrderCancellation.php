<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderCancellation extends Model
{
    protected $table = 'cancellations';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return ['cancelled_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
