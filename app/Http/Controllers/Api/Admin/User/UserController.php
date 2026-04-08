<?php

namespace App\Http\Controllers\Api\Admin\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    
    // Get All User
    // ------------
    public function getAllUsers(){

        $users = User::all(); 

        return response()->json([
            'success' => true,
            'message' => 'All users fetched successfully',
            'total' => $users->count(),
            'data' => $users
        ], 200);
    }

    // Delete User By ID
    // -----------------
    public function deleteUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        DB::beginTransaction();

        try {
            // ✅ Delete user image if exists
            if ($user->user_image && Storage::disk('public')->exists($user->user_image)) {
                Storage::disk('public')->delete($user->user_image);
            }

            // ================= AI WORKOUT DELETE =================

            // Step 1: Delete AI workout exercises
            DB::table('ai_workout_exercises')
                ->whereIn('ai_workout_id', function ($query) use ($id) {
                    $query->select('ai_workout_id')
                        ->from('ai_workouts')
                        ->where('user_id', $id);
                })
                ->delete();

            // Step 2: Delete AI workouts
            DB::table('ai_workouts')
                ->where('user_id', $id)
                ->delete();

            // ✅ Delete related records
            DB::table('user_feedback')->where('user_id', $id)->delete();
            DB::table('user_progress')->where('user_id', $id)->delete();

            // ✅ Delete user
            $user->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User and related data deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Without Delete The Image
    // ------------------------
    
    // public function deleteUser($id)
    // {
    //     $user = User::find($id);

    //     if (!$user) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'User not found'
    //         ], 404);
    //     }

    //     DB::beginTransaction();
    //     // delete related leaderboard records first
    //     DB::table('leaderboard')->where('user_id', $id)->delete();
    //     DB::table('user_exercises')->where('user_id', $id)->delete();
    //     DB::table('user_feedback')->where('user_id', $id)->delete();
    //     DB::table('user_progress')->where('user_id', $id)->delete();
    //     DB::table('user_workouts')->where('user_id', $id)->delete();
    //     // now delete user
    //     $user->delete();
    //     DB::commit();

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'User and related data deleted successfully'
    //     ], 200);
    // }

}
