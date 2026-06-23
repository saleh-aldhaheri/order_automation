<?php

namespace App\Models;

use App\Enums\ShopsEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Package extends Model
{
    protected $fillable = [
        'external_package_id',
        'external_order_id',
        'order_id',
        'shop_type',
        'external_package_status',
        'package_status',
        'details'
    ];

    protected $hidden = [
        'details',
    ];

    public $casts = [
        'details' => 'array',
        'shop_type' =>  ShopsEnum::class
    ];

    public function scopeGetPackage(Builder $query, string $externalPackageId, int $orderId)
    {
        $query->where('order_id', $orderId)
            ->where('external_package_id', $externalPackageId);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
