<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    /** @use HasFactory<\Database\Factories\ProviderFactory> */
    use HasFactory;

    protected $fillable = [
        'provider_type',
        'provider_id',
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
