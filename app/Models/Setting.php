<?php

namespace App\Models;

use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'interval_seconds',
        'price_per_km'
    ];

    // mendefiniskan variable
    protected $casts = [
        'interval_seconds' => 'integer',
        'price_per_km' => 'float'
    ];

    protected static function boot () {
        parent::boot();

        static::creating(function ($model) {
            if (static::count() > 0) {
                return false;
            }
        });
    }

    public static function getSettings() {
        return static::first();
    }
}
