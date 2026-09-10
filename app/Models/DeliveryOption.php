<?php

namespace App\Models;

use App\Enums\DeliveryOptionName;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'store_id',
    'name',
    'description',
    'additional_fee',
    'estimated_delivery_minutes',
    'is_active',
])]
class DeliveryOption extends Model
{
    protected function casts(): array
    {
        return [
            'name' => DeliveryOptionName::class,
            'additional_fee' => 'decimal:2',
            'estimated_delivery_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}