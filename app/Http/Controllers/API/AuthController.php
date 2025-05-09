<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Auth;
use App\Models\User;
use App\Models\Setting;

class AuthController extends Controller
{
    // Controller login
    public function login(Request $request) {
       $validator = Validator::make($request->all(), [
           /**
            * @example driverbobon@geberr.com
            */
           'email' => 'required|email',

           /**
            * @example password
            */
           'password' => 'required',
       ]);

       if ($validator->fails()) {
           return response()->json([
            'success' => false,
            'message' => 'Validation Error',
            'data' => ['errors' => $validator->errors()]
           ], 422);
       }

       if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
            'success' => false,
            'message' => 'Invalid Login Details',
            'data' => null
           ], 401);
       }

       $user = User::where('email', $request->email)->firstOrFail();
       $setting = Setting::getSettings();

       $token = $user->createToken('auth_token')->plainTextToken;

       $user->token = $token;

       if ($user->role === 'driver') {
           $user->driver = $user->driver;
           $user->setting = $setting;
       }

        return response()->json([
            'success' => true,
            'message' => 'Login Success',
            'data' =>   $user
        ]);
        
    }
}
