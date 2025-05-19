<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\DriverTracking;

class DriverTrackingController extends Controller
{
    // Membuat API untuk menyempan data tracking (Booking tracking)
    public function store(Request $request) {
        if (!auth()->user()->checkDriver()) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda bukan driver',
                'data' => auth()->user()
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            /**
             * @example -6.313131 description
             */
            'latitude' => 'required|numeric|between:-90,90',
            /**
             * @example 106.313131
             */
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'data' => ['errors' => $validator->errors()]
            ], 422);
        }

        $timestamp = now();

        $driverTracking = DriverTracking::create([
            'driver_id' => auth()->user()->driver->d,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'tracked_at' => $timestamp,
        ]);

        $request->user()->driver->update([
            'current_latitude' => $request->latitude,
            'current_longitude' => $request->longitude,
            'last_online' => $timestamp
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil disimpan',
            'data' =>   $driverTracking
        ]);
    }
}
