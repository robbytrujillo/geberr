<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Driver;
use App\Models\Booking;

class DriverController extends Controller
{
    public function toggleActive() {
        if (!auth()->user()->checkDriver()) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda bukan driver',
                'data' => null
            ], 403);
        }

        $driver = Driver::where('user_id', auth()->user()->id)->first();
        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Data Driver tidak ditemukan',
                'data' => null
            ], 404);
        }

        $activeBooking = Booking::getActiveBooking(auth()->user()->id, 'driver', auth()->user()->driver->id);
        if ($activeBooking) {
            return response()->json([
                'success' => false,
                'message' => 'Anda masih memiliki booking aktif',
                'data' => null
            ], 422);
        }

        $driver->update([
            'is_active' => !$driver->is_active,
            'last_online' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => $driver->is_active ? 'Driver Aktif' : 'Driver Tidak Aktif',
            'data' => $driver->load('user')
        ]);
    }

    public function todayStats() {
        if (!auth()->user()->checkDriver()) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda bukan driver',
                'data' => null
            ], 403);
        }

        $driver = Driver::where('user_id', auth()->user()->id)->first();
        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Data Driver tidak ditemukan',
                'data' => null
            ], 403);
        }

        $today = now()->format('Y-m-d');

        $stats = $driver->bookings()
                    ->whereDate('created_at', $today)
                    ->where('status', 'paid')
                    ->selectRaw('COUNT(*) as total_bookings, SUM(price) as total_earnings')
                    ->first();

        // mendapatkan data booking hari ini
        $paidBookings = $driver->bookings()
                            ->with('customer')
                            ->whereDate('created_at', $today)
                            ->where('status', 'paid')
                            ->latest()
                            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data ditemukan',
            'data' => [
                'driver' => $driver->load('user'),
                'stats' => [
                    'total_bookings' => (int) $stats->total_bookings,
                    'total_earnings' => (float) $stats->total_earnings,
                    'date' => $today,
                ],
                'paid_bookings' => $paidBookings
            ]
        ]);
    }
}
