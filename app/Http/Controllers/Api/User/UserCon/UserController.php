<?php

namespace App\Http\Controllers\Api\User\UserCon;
    
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    

    // ---------
    // Get User 
    // ---------

     public function getProfile(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Profile fetched successfully',
            'data' => $request->user()
        ]);
    }


    // ------------------------
    // Update the User Profile
    // ------------------------
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'user_name'   => 'sometimes|string|max:50',
            'user_phone'  => 'sometimes|string|max:15',
            'user_city'   => 'sometimes|string|max:50',
            'user_height' => 'sometimes|numeric',
            'user_weight' => 'sometimes|numeric',
            'user_image'  => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        // Update basic fields
        $user->update($request->only([
            'user_name',
            'user_phone',
            'user_city',
            'user_height',
            'user_weight'
        ]));

        // Handle image update
        if ($request->hasFile('user_image')) {

            $image = $request->file('user_image');
            $originalSize = $image->getSize();
            $imageName = time() . '_' . uniqid();
            $imagePath = '';

            // ✅ Compress only if > 200KB
            if ($originalSize > 200 * 1024) {

                $manager = new ImageManager(new Driver());
                $img = $manager->read($image);

                $img->scale(width: 600);

                $encoded = $img->toWebp(75);

                $imagePath = 'users/' . $imageName . '.webp';

                Storage::disk('public')->put($imagePath, (string) $encoded);

            } else {

                $extension = $image->getClientOriginalExtension();
                $imagePath = 'users/' . $imageName . '.' . $extension;

                Storage::disk('public')->put(
                    $imagePath,
                    file_get_contents($image)
                );
            }

            // Delete old image if exists
            if ($user->user_image && Storage::disk('public')->exists($user->user_image)) {
                Storage::disk('public')->delete($user->user_image);
            }

            $user->update([
                'user_image' => $imagePath
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user->fresh()
        ]);
    }


}
