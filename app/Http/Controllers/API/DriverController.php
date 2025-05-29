<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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
}
