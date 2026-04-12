<?php

namespace App\Http\Controllers\Api\User\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

        try {
            $htmlContent = "
                <div style='font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;'>
                    <div style='max-width: 500px; margin: auto; background: #ffffff; padding: 25px; border-radius: 10px;'>
                        <h2 style='color: #333; margin-bottom: 20px;'>Password Reset Request</h2>

                        <p style='font-size: 15px; color: #555;'>Hello User,</p>

                        <p style='font-size: 15px; color: #555;'>
                            We received a request to reset your password for your <b>Fitness App</b> account.
                        </p>

                        <p style='font-size: 15px; color: #555;'>
                            Please use the OTP below to continue:
                        </p>

                        <div style='text-align: center; margin: 30px 0;'>
                            <span style='display: inline-block; font-size: 32px; font-weight: bold; color: #ffffff; background-color: #2e86de; padding: 12px 24px; border-radius: 8px; letter-spacing: 4px;'>
                                {$otp}
                            </span>
                        </div>

                        <p style='font-size: 15px; color: #555;'>
                            This OTP is valid for <b>6 minutes</b>.
                        </p>

                        <p style='font-size: 15px; color: #555;'>
                            If you did not request this password reset, please ignore this email.
                        </p>

                        <hr style='margin: 25px 0; border: none; border-top: 1px solid #ddd;'>

                        <p style='font-size: 12px; color: #888; margin-bottom: 5px;'>
                            This is an automated email. Please do not reply.
                        </p>

                        <p style='font-size: 12px; color: #888;'>
                            &copy; " . date('Y') . " Fitness API. All rights reserved.
                        </p>
                    </div>
                </div>
            ";

            $response = Http::withHeaders([
                'api-key' => env('BREVO_API_KEY'),
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ])->post('https://api.brevo.com/v3/smtp/email', [
                'sender' => [
                    'name' => env('MAIL_FROM_NAME', 'Fitness API'),
                    'email' => env('MAIL_FROM_ADDRESS'),
                ],
                'to' => [
                    [
                        'email' => $user->user_email,
                        'name' => 'User',
                    ]
                ],
                'subject' => 'User Password Reset OTP',
                'htmlContent' => $htmlContent,
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'OTP sent to your email'
                ], 200);
            }

            Log::error('Brevo User Forgot Password API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Mail sending failed',
                'error' => $response->body()
            ], 500);

        } catch (\Exception $e) {
            Log::error('User forgot password mail exception: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Mail sending failed',
                'error' => $e->getMessage()
            ], 500);
        }
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
