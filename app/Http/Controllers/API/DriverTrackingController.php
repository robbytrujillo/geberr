<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DriverTrackingController extends Controller
{
    // Booking tracking
    public function store(Request $request) {
        $validate = $request->validate([
            /**
             * @example -6.313131 description
             */
            'latitude' => 'required|numeric|between:-90,90',
            /**
             * @example 106.313131
             */
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if (!auth()->user()->checkDriver()) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda bukan driver',
                'data' => auth()->user()
            ], 403);
        }

        $timestamp = now();

        $driverTracking = DriverTracking::create([
            'driver_id' => auth()->user()->driver->d,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'tracked_at' => $timestamp,
        ]);
    }
}
