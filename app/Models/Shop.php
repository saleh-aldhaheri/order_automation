<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    /** @use HasFactory<\Database\Factories\ShopFactory> */
    use HasFactory;

    protected $fillable = [
        'shop_type',
        'external_shop_id',
        'is_active',
        'configuration',
    ];

    /**
     * Hidden from array/JSON serialization — `configuration` holds OAuth
     * @var list<string>
     */
    protected $hidden = [
        'configuration',
    ];

    protected  $casts = [
        "configuration" => 'array'
    ];
}
