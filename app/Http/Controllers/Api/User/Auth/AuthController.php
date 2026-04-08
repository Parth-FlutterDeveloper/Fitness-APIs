<?php

namespace App\Http\Controllers\Api\User\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class AuthController extends Controller
{
    
    // User Register
    // -------------
    public function register(Request $request)
    {
        $request->validate([
            'user_name'              => 'required|string|max:20',
            'user_email'             => 'required|email|unique:user,user_email',
            'user_phone'             => 'required|string|max:15',
            'user_city'              => 'required|string|max:15',
            'user_password'          => 'required|min:6',
            'user_confirm_password'  => 'required|same:user_password',
            'user_birthdate'         => 'required|date',
            'user_height'            => 'required|integer',
            'user_weight'            => 'required|integer',
            'user_target_weight'     => 'required|integer',
            'user_gender'            => 'required|string|max:10',
            'user_goal'              => 'required|integer',
            'user_body_type'         => 'required|in:ectomorph,mesomorph,endomorph',
            'user_image'             => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048'
        ]);

        // 👉 Handle Image Upload
        $imagePath = null;
        if ($request->hasFile('user_image')) {
            $imagePath = $request->file('user_image')
                ->store('users', 'public');
        }

        $user = User::create([
            'user_name'          => $request->user_name,
            'user_email'         => $request->user_email,
            'user_phone'         => $request->user_phone,
            'user_city'          => $request->user_city,
            'user_password'      => $request->user_password, // auto-hashed
            'user_birthdate'     => $request->user_birthdate,
            'user_height'        => $request->user_height,
            'user_weight'        => $request->user_weight,
            'user_target_weight' => $request->user_target_weight,
            'user_gender'        => $request->user_gender,
            'user_goal'          => $request->user_goal,
            'user_body_type'     => $request->user_body_type,
            'user_xp_points'     => 0,
            'user_image'         => $imagePath
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => $user
        ], 201);
    }


    // User Login
    // ----------
    public function login(Request $request)
    {
        // Validate request
        $request->validate([
            'user_email'    => 'required|email',
            'user_password' => 'required|min:6'
        ]);

        // Find user by email
        $user = User::where('user_email', $request->user_email)->first();

        // User not found OR password wrong
        if (!$user || !Hash::check($request->user_password, $user->user_password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password'
            ], 401);
        }

        // Create token (Sanctum)
        $token = $user->createToken('user-token')->plainTextToken;

        // Success response
        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'token'   => $token,
            'data'    => [
                'user_id'    => $user->user_id,
                'user_name'  => $user->user_name,
                'user_email' => $user->user_email
            ]
        ], 200);
    }


    // Forgot Password
    // ---------------
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'user_email' => 'required|email'
        ]);

        $user = User::where('user_email', $request->user_email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $otp = rand(100000, 999999);

        $user->otp = $otp;
        $user->otp_expires_at = Carbon::now()->addMinutes(6);
        $user->save();

        // Send OTP Email
        Mail::raw("Your password reset OTP is: $otp", function ($message) use ($user) {
            $message->to($user->user_email)
                    ->subject('User Password Reset OTP');
        });

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your email'
        ], 200);
    }


    // Verify OTP
    // ----------
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'user_email' => 'required|email',
            'otp'        => 'required'
        ]);

        $user = User::where('user_email', $request->user_email)
                    ->where('otp', $request->otp)
                    ->first();

        if (!$user || now()->gt($user->otp_expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully'
        ], 200);
    }


    // Reset Password
    // --------------
    public function resetPassword(Request $request)
    {
        $request->validate([
            'user_email' => 'required|email',
            'password'   => 'required|min:6|confirmed'
        ]);

        $user = User::where('user_email', $request->user_email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $user->user_password = $request->password; // auto-hashed
        $user->otp = null;
        $user->otp_expires_at = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully'
        ], 200);
    }

}
