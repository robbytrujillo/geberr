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
    // Controller register  
    public function register(Request $request) {
        $validator = Validator::make($request->all(), [
            /**
             * @example Customer B
            */
            'name' => 'required|string|max:255',
            
            /**
             * @example customer-b@geberr.com
            */
            'email' => 'required|string|email|unique:users',

            /**
             * @example password
            */
            'password' => 'required|string|min:8',
            // 'role' => 'required|string|in:admin,customer,driver',

            /**
             * @example 081222222222
            */
            'whatsapp' => 'required|string',
        ]);

         if ($validator->fails()) {
           return response()->json([
            'success' => false,
            'message' => 'Validation Error',
            'data' => ['errors' => $validator->errors()]
           ], 422);
       }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => 'customer',
            'whatsapp' => $request->whatsapp,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $user->token = $token;

        return response()->json([
            'success' => true,
            'message' => 'Register Success',
            'data' =>   $user
        ]);
    }
    
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

    // logout
    public function user(Request $request) {
        $user = $request->user();
        if ($user->role === 'driver') {
            $user->driver = $user->driver;
        }

        return response()->json([
            'success' => true,
            'message' => 'Berhasil Mendapatkan Data User',
            'data' =>   $user
        ]);
    }
}
