<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\DriverTracking;
use DB;

class DriverTrackingController extends Controller
{
    // Membuat API untuk menyempan data tracking (Booking tracking)
    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            /**
             * @example -6.313131
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

         if (!auth()->user()->checkDriver()) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda bukan driver',
                'data' => auth()->user()
            ], 403);
        }

        $timestamps = now();

        $activeBooking = Booking::getActiveBooking(auth()->user()->id, 'driver', auth()->user()->driver->id);

        $driverTracking = DriverTracking::create([
            'driver_id' => auth()->user()->driver->id,
            'booking_id' => $activeBooking ? $activeBooking->id : null,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'tracked_at' => $timestamps
        ]);

        $request->user()->driver->update([
            'current_latitude' => $request->latitude,
            'current_longitude' => $request->longitude,
            'last_online' => $timestamps
        ]);
        
        $responseData = [
            'tracking' => $driverTracking,
            'active_booking' => $activeBooking ? $activeBooking->load('customer')->load('driver') : null
        ];

        if (!$activeBooking) {
            $availableBookings = Booking::with('customer')
                                    ->where('status', Booking::STATUS_FINDING_DRIVER)
                                    ->where('driver_id')
                                    ->select([
                                        'bookings.*',
                                        DB::raw("(6371 * acos(
                                            cos(radians(" . $request->latitude . ")) *
                                            cos(radians(latitude_origin)) *
                                            cos(radians(longitude_origin) - radians(" . $request->longitude . ")) +
                                            sin(radians(" . $request->latitude . ")) *
                                            sin(radians(latitude_origin))
                                        )) as distance")
                                    ])
                                    ->orderBy('created_at', 'desc')
                                    ->get();

            $responseData['available_booking'] = $availableBookings->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'customer_name' => $booking->customer->name,
                    'pickup_address' => $booking->address_origin,
                    'destination_address' => $booking->address_destination,
                    'distance_from_driver' => round($booking->distance, 2),
                    'price' => $booking->price,
                    'created_at' => $booking->created_at
                ];
            });
        }

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil disimpan',
            'data' =>   $responseData
        ]);
    }

    public function getTrackingBookingId($booking_id) {
        $booking = Booking::with('driver')->findOrFail($booking_id);

        $user = auth()->user();
        if ($user->checkDriver()) {
            if ($booking->driver_id == null || $booking->driver_id != $user->driver->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses (driver)',
                    'data' => null
                ], 403);
            }
        }

        if ($user->checkCustomer()) {
            if ($booking->customer_id != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses (customer)',
                    'data' => null
                ], 403);
            }
        }

        $trackings = DriverTracking::where('booking_id', $booking_id)
                        ->orderBy('tracked_at', 'desc')
                        ->get();

        $lastTracking = $trackings->first();

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil ditemukan',
            'data' => [
                'booking' => $booking,
                'last_position' => $lastTracking ? [
                    'latitude' => $lastTracking->latitude,
                    'longitude' => $lastTracking->longitude,
                    'tracked_at' => $lastTracking->tracked_at
                    ] : null,
                'tracking_history' => $trackings,
            ]
        ]);
    }
}
