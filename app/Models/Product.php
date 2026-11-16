<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $guarded = ['created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'base_price' => 'decimal:2',
            'is_active' => 'boolean',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name) . '-' . Str::random(5);
            }
            if (!isset($product->base_price) && isset($product->price)) {
                $product->base_price = $product->price;
            }
            if (!isset($product->price) && isset($product->base_price)) {
                $product->price = $product->base_price;
            }
            if (!isset($product->is_available) && isset($product->is_active)) {
                $product->is_available = $product->is_active;
            }
            if (!isset($product->is_active) && isset($product->is_available)) {
                $product->is_active = $product->is_available;
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ProductPhotos::class);
    }

    public function primaryPhoto(): HasOne
    {
        return $this->hasOne(ProductPhotos::class)->where('is_primary', true);
    }

    public function customizations(): HasMany
    {
        return $this->hasMany(ProductCustomization::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItems::class);
    }

    public function getPriceAttribute($value)
    {
        return $value ?? $this->attributes['base_price'] ?? 0;
    }

    public function getBasePriceAttribute($value)
    {
        return $value ?? $this->attributes['price'] ?? 0;
    }

    public function getIsAvailableAttribute($value)
    {
        return $value ?? $this->attributes['is_active'] ?? true;
    }

    public function getIsActiveAttribute($value)
    {
        return $value ?? $this->attributes['is_available'] ?? true;
    }

    public function getImageUrlAttribute(): string
    {
        $primary = $this->photos()->where('is_primary', true)->first();
        if ($primary) {
            return $primary->url;
        }

        $anyPhoto = $this->photos()->first();
        if ($anyPhoto) {
            return $anyPhoto->url;
        }

        if ($this->image_path && (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://'))) {
            return $this->image_path;
        }

        if ($this->image_path && file_exists(public_path('storage/' . $this->image_path))) {
            return asset('storage/' . $this->image_path);
        }

        return 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=600&q=80';
    }
}
