<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use SoftDeletes;

    const STATUS_FINDING_DRIVER = 'finding_driver';
    const STATUS_DRIVER_PICKUP = 'driver_pickup';
    const STATUS_DRIVER_DELIVER = 'driver_deliver';
    const STATUS_ARRIVED = 'arrived';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELED = 'cancelled';

    protected $fillable = [
        'customer_id',
        'driver_id',
        'latitude_origin',
        'longitude_origin',
        'address_origin',
        'latitude_destination',
        'longitude_destination',
        'address_destination',
        'distance',
        'price',
        'status',
        'time_estimate',
    ];

    protected $casts = [
        'latitude_origin' => 'float',
        'longitude_origin' => 'float',
        'latitude_destination' => 'float',
        'longitude_destination' => 'float',
        'distance' => 'float',
        'price' => 'float',
        'time_estimate' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function getStatusColorAtribute(): string 
    {
        return match ($this->status) {
            self::STATUS_FINDING_DRIVER => 'warning',
            self::STATUS_DRIVER_PICKUP,
            self::STATUS_DRIVER_DELIVER => 'primary',
            self::STATUS_ARRIVED,
            self::STATUS_PAID => 'success',
            self::STATUS_CANCELED => 'danger',
            default => 'secondary',
        };
    }

    // check Booking apakah masih Active atau tidak
    public static function hasActiveBooking($customerId): bool {
        return static::where('customer_id', $customerId)
            ->whereNotIn('status', [self::STATUS_PAID, self::STATUS_CANCELED])
            ->exists();
    }

    // mendapatkan booking yang masih aktif
    public static function getActiveBooking($userId, $role, $driverId = null): ?Booking {
        $query = self::query();

        if ($role === 'customer') {
            $query->where('customer_id', $userId);
        } else if ($role === 'driver') {
            $query->where('driver_id', $driverId);
        }

        return $query->whereNotIn('status', [
            self::STATUS_PAID, 
            self::STATUS_CANCELED
        ])->first();
    }

    public function isFindingDriver(): bool {
        return $this->status === self::STATUS_FINDING_DRIVER;
    }
}
