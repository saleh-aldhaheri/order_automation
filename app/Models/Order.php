<?php

namespace App\Models;

use App\Enums\OrderStatusEnum;
use App\Enums\ShopsEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'external_order_id',
        'external_shop_id',
        'shop_id',
        'shop_type',
        'external_order_status',
        'order_status',
        'details'
    ];

    protected $hidden = [
        'details',
    ];

    public $casts = [
        'details' => 'array',
        'order_status' => OrderStatusEnum::class,
        'shop_type' =>  ShopsEnum::class
    ];

    public function scopeGetOrder(Builder $query, string $externalOrderId, int $shopId)
    {
        $query->where('shop_id', $shopId)
            ->where('external_order_id', $externalOrderId);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }
}
