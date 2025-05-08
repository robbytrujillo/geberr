<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    // login
    public function login() {
        return response()->json([
            'success' => true,
            'message' => 'Sukses membuat API',
            'data' => null
        ]);
    }
}
