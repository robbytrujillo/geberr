<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'vehicle_number',
        'is_active',
        'current_latitude',
        'current_longitude',
        'last_online'
    ];
}
