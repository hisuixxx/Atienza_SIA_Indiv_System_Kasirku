<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CateringRequest extends Model
{
    use HasFactory;

    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_QUOTED = 'quoted';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_DECLINED = 'declined';

    protected $fillable = [
        'user_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'event_name',
        'event_date',
        'event_location',
        'number_of_guests',
        'preferred_coffee_package',
        'additional_instructions',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'datetime',
            'number_of_guests' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statusHistories(): MorphMany
    {
        return $this->morphMany(RequestStatusHistory::class, 'trackable')->latest();
    }
}
