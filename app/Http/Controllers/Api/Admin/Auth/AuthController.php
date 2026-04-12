<?php

namespace App\Http\Controllers\Api\Admin\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
   
    // Admin Login 
    // -----------

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]); 

        $admin = Admin::where('admin_email', $request->email)->first();

        if (!$admin || !Hash::check($request->password, $admin->admin_password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);    
        }

        // Last Login Time
        $admin->last_login = now();
        $admin->save();

        $token = $admin->createToken('admin-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'admin' => $admin
        ]);
    }


    // Forgot Password
    // ---------------

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $admin = Admin::where('admin_email', $request->email)->first();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin not found'
            ], 404);
        }

        $otp = rand(100000, 999999);

        $admin->otp = $otp;
        $admin->otp_expires_at = Carbon::now()->addMinutes(6);
        $admin->save();

        try {
            $htmlContent = "
                <div style='font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;'>
                    <div style='max-width: 500px; margin: auto; background: #ffffff; padding: 25px; border-radius: 10px;'>
                        <h2 style='color: #333; margin-bottom: 20px;'>Admin Password Reset Request</h2>

                        <p style='font-size: 15px; color: #555;'>Hello Admin,</p>

                        <p style='font-size: 15px; color: #555;'>
                            We received a request to reset your password for your <b>Fitness App Admin Panel</b>.
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
                        'email' => $admin->admin_email,
                        'name' => 'Admin',
                    ]
                ],
                'subject' => 'Admin Password Reset OTP',
                'htmlContent' => $htmlContent,
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'OTP sent to admin email'
                ]);
            }

            Log::error('Brevo API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Mail sending failed',
                'error' => $response->body()
            ], 500);

        } catch (\Exception $e) {
            Log::error('Forgot password mail exception: ' . $e->getMessage());

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
            'email' => 'required|email',
            'otp' => 'required'
        ]);

        $admin = Admin::where('admin_email', $request->email)
                    ->where('otp', $request->otp)
                    ->first();

        if (!$admin || now()->gt($admin->otp_expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully'
        ]);
    }


    // Reset Password
    // ---------------

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed'
        ]);

        $admin = Admin::where('admin_email', $request->email)->first();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin not found'
            ], 404);
        }

        $admin->admin_password = Hash::make($request->password);
        $admin->otp = null;
        $admin->otp_expires_at = null;
        $admin->save();

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully'
        ]);
    }


}
