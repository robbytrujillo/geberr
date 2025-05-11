<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Spatie\Color\Distance;
use App\Models\Setting;
use App\Models\Booking;
use App\Models\User;

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
           'latitude_origin' => 'required|numeric|between:-90,90',
           'longitude_origin' => 'required|numeric|between:-180,180',
           'address_origin' => 'required|string|max:255',
           'latitude_destination' => 'required|numeric|between:-90,90',
           'longitude_destination' => 'required|numeric|between:-180,180',
           'address_destination' => 'required|string|max:255',
           'distance' => 'required|numeric|min:0',
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

        $setting = Setting::getSetting();
        if ($setting) {
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
}
