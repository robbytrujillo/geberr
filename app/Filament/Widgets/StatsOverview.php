<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Driver;
use App\Models\User;
use App\Models\Booking;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Driver', Driver::count()),
            Stat::make('Customer', User::where('role', 'customer')->count()),
            Stat::make('Success Booking', Booking::where('status', 'paid')->count()),
        ];
    }
}