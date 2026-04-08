<?php

namespace App\Http\Controllers\Api\Admin\AdminCon;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    
    // Get Admin By ID
    // ---------------
    public function getAdminById($id)
    {
        $admin = Admin::find($id);

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $admin
        ]);
    }


    // Admin Change Password
    // ---------------------
    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:6|confirmed'
        ]);

        // Get logged-in admin
        $admin = auth()->user();

        // Check old password
        if (!Hash::check($request->old_password, $admin->admin_password)) {
            return response()->json([
                'success' => false,
                'message' => 'Old password is incorrect'
            ], 400);
        }

        // Update new password
        $admin->admin_password = Hash::make($request->new_password);
        $admin->save();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }


    // Update Admin Details
    // --------------------
    public function updateProfile(Request $request)
    {
        $admin = auth()->user();

        $request->validate([
            'admin_name'  => 'sometimes|string|max:50',
            'admin_phone' => 'sometimes|digits:10',
            'admin_image' => 'sometimes|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        // Update Name
        if ($request->has('admin_name')) {
            $admin->admin_name = $request->admin_name;
        }

        // Update Phone
        if ($request->has('admin_phone')) {
            $admin->admin_phone = $request->admin_phone;
        }

        // Update Image
        if ($request->hasFile('admin_image')) {

            // Delete Old Image (if exists)
            if ($admin->admin_image && 
                Storage::disk('public')->exists($admin->admin_image)) {
                
                Storage::disk('public')->delete($admin->admin_image);
            }

            // Store New Image
            $path = $request->file('admin_image')
                            ->store('admin_images', 'public');

            $admin->admin_image = $path;
        }

        $admin->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $admin
        ]);
    }


}