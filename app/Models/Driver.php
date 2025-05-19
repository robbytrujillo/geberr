<?php

namespace App\Models;

// use Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

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

    protected $casts = [
        'is_active' => 'boolean',
        'last_online' => 'datetime',
        'current_latitude' => 'float',
        'current_longitude' => 'float'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected $appends = [
        'name',
        'email',
        'photo_url',
        'whatsapp',
    ];

    protected function name (): Attribute
    {
        return Attribute::make (
            get: fn () => $this->user?->name
        );
    }
}
