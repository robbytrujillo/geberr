<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;

class SettingController extends Controller
{
    public function index() {
        $setting = Setting::getSettings();
        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting belum diatur',
                'data' => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mendapatkan setting',
            'data' => [
                'interval_seconds' => $setting->interval_seconds,
                'price_per_km' => $setting->price_per_km,
                'price_per_km_formatted' => 'Rp. ' . number_format($setting->price_per_km, 0, ',', '.')
            ]
        ]);
    }
}
