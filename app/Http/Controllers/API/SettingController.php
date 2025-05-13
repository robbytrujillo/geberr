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
    }
}
