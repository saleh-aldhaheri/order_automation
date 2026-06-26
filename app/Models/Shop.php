<?php

namespace App\Models;

use App\Enums\ShopsEnum;
use App\Traits\SearchableTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shop extends Model
{
    /** @use HasFactory<\Database\Factories\ShopFactory> */
    use HasFactory, SearchableTrait;

    protected $searchable = [
        'shop_type',
        'external_shop_id',
    ];

    protected $fillable = [
        'shop_type',
        'external_shop_id',
        'is_active',
        'auth_configuration',
    ];

    /**
     * Hidden from array/JSON serialization — `auth_configuration` holds OAuth
     * @var list<string>
     */
    protected $hidden = [
        'auth_configuration',
    ];

    protected  $casts = [
        "auth_configuration" => 'array',
        'shop_type' => ShopsEnum::class
    ];

    public function scopeGetShop(Builder $query, string $externalShopId, ShopsEnum $shopType): void
    {
        $query->where('external_shop_id', $externalShopId)->where('shop_type', $shopType->value);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
