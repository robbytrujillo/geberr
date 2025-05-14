<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
// use Spatie\Color\Distance;
use App\Models\Setting;
use App\Models\Booking;
// use App\Models\User;

class BookingController extends Controller
{
    // cek harga
    public function priceCheck(Request $request)
    {
       $validator = Validator::make($request->all(), [
           'distance' => 'required|numeric|min:0',
       ]);
       
        if ($validator->fails()) {
           return response()->json([
            'success' => false,
            'message' => 'Validation Error',
            'data' => ['errors' => $validator->errors()]
           ], 422);
       }

       $setting = Setting::first();
       $price = floor($request->distance) * $setting->price_per_km;

       return response()->json([
        'success' => true,
        'message' => 'Harga berhasil dihitung',
        'data' =>   $price
       ]);
    }

    // store booking
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
           /**
            * @example -6.313131
            */
           'latitude_origin' => 'required|numeric|between:-90,90',

           /**
             * @example 106.313131
            */
           'longitude_origin' => 'required|numeric|between:-180,180',

           /**
            * @example Cibubur
            */
           'address_origin' => 'required|string|max:255',

           /**
             * @example -6.414141
            */
           'latitude_destination' => 'required|numeric|between:-90,90',

           /**
             * @example 106.414141
            */
           'longitude_destination' => 'required|numeric|between:-180,180',

           /**
            * @example Margonda
            */
           'address_destination' => 'required|string|max:255',

           /**
            * @example 4
            */
           'distance' => 'required|numeric|min:0',

           /**
            * @example 300
            */
           'time_estimate' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
           return response()->json([
            'success' => false,
            'message' => 'Validation Error',
            'data' => ['errors' => $validator->errors()]
           ], 422);
        }

        if (!auth()->user()->checkCustomer()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Customer yang dapat melakukan booking',
                'data' => ['errors' => $validator->errors()]
            ], 403);
        }

        // validasi untuk membatasi booking, customer hanya bisa melakukan booking baru jika booking lain statusnya paid atau canceled
        if (Booking::hasActiveBooking(auth()->id())) {
            $activeBooking = Booking::getActiveBooking(auth()->id(), auth()->user()->role)->load('customer')->load('driver'); // getActiveBooking($userId, 'customer');
            return response()->json([
                'success' => false,
                'message' => 'Anda masih memiliki booking aktif, Selesaikan terlebih dahulu',
                'data' => [
                    'active_booking' => $activeBooking
                ]
            ], 422);
        }
        
        $setting = Setting::getSettings();
        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting belum diatur',
                'data' => null
            ]);
        }

        $price = floor($request->distance) * $setting->price_per_km; 
        $booking = Booking::create([
            'customer_id' => auth()->id(),
            'latitude_origin' => $request->latitude_origin,
            'longitude_origin' => $request->longitude_origin,
            'address_origin' => $request->address_origin,
            'latitude_destination' => $request->latitude_destination,
            'longitude_destination' => $request->longitude_destination,
            'address_destination' => $request->address_destination,
            'distance' => $request->distance,
            'price' => $price,
            'time_estimate' => $request->time_estimate,

        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking berhasil dibuat',
            'data' => [
                'booking' => $booking->load('customer')->load('driver'), 
                'price_detail' => [
                    'distance' => $request->distance,
                    'price_per_km' => $setting->price_per_km,
                    'total_price' => $price
                ]
            ]
        ]);
    } 

    // cancel booking
    public function cancel(Booking $booking) {
        if (auth()->id() !== $booking->customer_id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses',
                'data' => null
            ], 403);
        }

        if (!$booking->isFindingDriver()) {
            return response()->json([
                'success' => false,
                'message' => 'Booking tidak dapat dibatalkan',
                'data' => null
            ], 422);
        }

        $booking->update(['status' => Booking::STATUS_CANCELED]);
        return response()->json([
            'success' => true,
            'message' => 'Booking berhasil dibatalkan',
            'data' => null
        ]);
    }

    public function getAll(Request $request) {
        $validator = Validator::make($request->all(), [
             'start_date' => 'nullable|date_format: Y-m-d',
             'end_date' => 'nullable|date_format: Y-m-d',
             'status' => 'nullable|in:finding_driver,driver_pickup,driver_deliver,arrived,paid,canceled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'data' => ['errors' => $validator->errors()]
            ], 422);
        }

        $query = Booking::with(['customer', 'driver'])
                ->when($request->filled('start_date'), function ($q) use ($request) {
                    return $q->whereDate(['created_at', '>=', $request->start_date]);
                })
                ->when($request->filled('end_date'), function ($q) use ($request) {
                    return $q->whereDate(['created_at', '<=', $request->end_date]);
                })
                ->when($request->filled('status'), function ($q) use ($request) {
                    return $q->where('status', $request->status);
                });
                

        if (auth()->user()->checkDriver()) {
            $query->where('driver_id', auth()->user()->driver->id);
        } else if (auth()->user()->checkDriver()) {
            $query->where('customer_id', auth()->user()->id);
        }

        $bookings = $query->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mendapatkan data booking',
            'data' => $bookings
            ]
        );
    }

    // get Detail Booking
    public function show($booking_id) {
        $booking = Booking::with(['customer', 'driver'])->find($booking_id);
        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking tidak ditemukan',
                'data' => null
            ], 404);
        }

        $user = auth()->user();
        if (!$user->checkDriver()) {
            if ($booking->driver_id != null && $booking->driver_id != $user->driver->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses',
                    'data' => null
                ], 403);
            }
        }

        if ($user->checkCustomer()) {
            if ($booking->customer_id != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses',
                    'data' => null
                ], 403);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mendapatkan data booking',
            'data' => $booking
        ]);
    }
}
