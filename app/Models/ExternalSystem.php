<?php

namespace App\Models;

use App\Traits\SearchableTrait;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class ExternalSystem extends Model implements Authenticatable
{
    use AuthenticatableTrait, HasApiTokens, HasFactory, SearchableTrait;

    protected $fillable = [
        'system_name',
        'client_id',
        'client_secret',
        'is_active',
    ];

    protected $searchable = [
        'system_name',
        'client_id',
    ];

    protected $hidden = ['client_secret'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
