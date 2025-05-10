<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Spatie\Color\Distance;
use App\Models\Setting;

class BookingController extends Controller
{
    // cek harga
    public function priceCheck(Request $request)
    {
       $validator = Validator::make($request->all(), [
           'distance' => 'required|numeric|min:0',
        //    'weight' => 'required|numeric',
        //    'courier' => 'required|numeric',
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
}
