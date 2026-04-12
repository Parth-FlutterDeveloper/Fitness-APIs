<?php

namespace App\Http\Controllers\Api\Admin\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;

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

        // Send OTP Email
        // --------------
        
        // Mail::raw("Your Admin OTP is: $otp", function ($message) use ($admin) {
        //     $message->to($admin->admin_email)
        //             ->subject('Admin Password Reset OTP');
        // });

        Mail::html("
            <div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f4f4f4;'>
                <div style='max-width: 500px; margin: auto; background: #ffffff; padding: 20px; border-radius: 10px;'>
                    
                    <h2 style='color: #333;'>Password Reset Request</h2>

                    <p>Hello Admin,</p>

                    <p>We received a request to reset your password for your <b>Fitness App Admin Panel</b>.</p>

                    <p>Please use the OTP below to proceed with resetting your password:</p>

                    <h1 style='text-align: center; color: #2e86de;'>$otp</h1>

                    <p>This OTP is valid for <b>6 minutes</b>.</p>

                    <p>If you did not request a password reset, please ignore this email.</p>

                    <hr>

                    <p style='font-size: 12px; color: #888;'>
                        This is an automated message. Please do not reply to this email.
                    </p>

                    <p style='font-size: 12px; color: #888;'>
                        © " . date('Y') . " Fitness App. All rights reserved.
                    </p>

                </div>
            </div>
        ", function ($message) use ($admin) {
            $message->to($admin->admin_email)
                    ->subject('🔐 Admin Password Reset OTP');
        });

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to admin email'
        ]);
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
