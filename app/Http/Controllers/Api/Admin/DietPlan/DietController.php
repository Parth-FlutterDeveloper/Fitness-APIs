<?php

namespace App\Http\Controllers\Api\Admin\DietPlan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DietPlans;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use App\Models\Meal;

class DietController extends Controller
{
    
    // Get all Diet Plans
    // ------------------
    public function dietplans()
    {
        $dietPlans = DietPlans::all();

        return response()->json([
            'success' => true,
            'data' => $dietPlans
        ], 200);
    }


    // Insert Diet Plan
    // ----------------
    public function insertDietPlan(Request $request)
    {
        $request->validate([
            'diet_plans_name'      => 'required|string|max:50|unique:diet_plans,diet_plans_name',
            'diet_plan_description'=> 'required|string|max:255',
            'diet_plan_goal'       => 'required|integer|exists:goals,goal_id',
            'daily_calorie_target' => 'required|integer|min:500',
            'diet_plan_image'      => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        DB::beginTransaction();

        try {

            $image = $request->file('diet_plan_image');
            $originalSize = $image->getSize(); // bytes

            $imageName = time() . '_' . uniqid();
            $imagePath = '';

            // ✅ Compress ONLY if > 200KB
            if ($originalSize > 200 * 1024) {

                $manager = new ImageManager(new Driver());
                $img = $manager->read($image);

                $img->scale(width: 600);

                $encoded = $img->toWebp(75);

                $imagePath = 'diet_plans/' . $imageName . '.webp';

                Storage::disk('public')->put($imagePath, (string) $encoded);

            } else {

                // 🔹 Store original if already small
                $extension = $image->getClientOriginalExtension();
                $imagePath = 'diet_plans/' . $imageName . '.' . $extension;

                Storage::disk('public')->put(
                    $imagePath,
                    file_get_contents($image)
                );
            }

            $dietPlan = DietPlans::create([
                'diet_plans_name'       => $request->diet_plans_name,
                'diet_plan_description' => $request->diet_plan_description,
                'diet_plan_goal'        => $request->diet_plan_goal,
                'daily_calorie_target'  => $request->daily_calorie_target,
                'diet_plan_image'       => $imagePath,
                'created_at'            => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Diet plan created successfully',
                'data'    => $dietPlan
            ], 201);

        } catch (\Exception $e) {

            DB::rollBack();

            if (isset($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to create diet plan',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    

    // public function insertDietPlan(Request $request)
    // {
    //     $request->validate([
    //         'diet_plans_name' => 'required|string|max:50|unique:diet_plans,diet_plans_name',
    //         'diet_plan_description' => 'required|string|max:255',
    //         'diet_plan_goal' => 'required|integer|exists:goals,goal_id',
    //         'daily_calorie_target' => 'required|integer|min:500',
    //         'diet_plan_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
    //     ]);

    //     DB::beginTransaction();

    //     try {
    //         // Store image (same as workout)
    //         $imagePath = $request->file('diet_plan_image')
    //             ->store('diet_plans', 'public');

    //         // Create diet plan
    //         $dietPlan = DietPlans::create([
    //             'diet_plans_name' => $request->diet_plans_name,
    //             'diet_plan_description' => $request->diet_plan_description,
    //             'diet_plan_goal' => $request->diet_plan_goal,
    //             'daily_calorie_target' => $request->daily_calorie_target,
    //             'diet_plan_image' => $imagePath,
    //         ]);

    //         DB::commit();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Diet plan created successfully',
    //             'data' => $dietPlan
    //         ], 201);

    //     } catch (\Exception $e) {

    //         DB::rollBack();

    //         // delete uploaded image if DB fails
    //         if (isset($imagePath)) {
    //             Storage::disk('public')->delete($imagePath);
    //         }

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to create diet plan'
    //         ], 500);
    //     }
    // }


    // Update Diet Plan Details
    // ------------------------

    public function updateDietPlan(Request $request, $diet_plan_id)
    {
        $request->validate([
            'diet_plans_name' => 'sometimes|string|max:50|unique:diet_plans,diet_plans_name,' . $diet_plan_id . ',diet_plan_id',
            'diet_plan_description' => 'sometimes|string|max:255',
            'diet_plan_goal' => 'sometimes|integer|exists:goals,goal_id',
            'daily_calorie_target' => 'sometimes|integer|min:500',
            'diet_plan_image' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        DB::beginTransaction();

        try {

            $dietPlan = DietPlans::findOrFail($diet_plan_id);

            if ($request->hasFile('diet_plan_image')) {

                if (
                    $dietPlan->diet_plan_image &&
                    Storage::disk('public')->exists($dietPlan->diet_plan_image)
                ) {
                    Storage::disk('public')->delete($dietPlan->diet_plan_image);
                }

                $image = $request->file('diet_plan_image');
                $originalSize = $image->getSize();

                $imageName = time() . '_' . uniqid();

                if ($originalSize > 200 * 1024) {

                    $manager = new ImageManager(new Driver());
                    $img = $manager->read($image);

                    $img->scale(width: 600);

                    $encoded = $img->toWebp(75);

                    $path = 'diet_plans/' . $imageName . '.webp';

                    Storage::disk('public')->put($path, (string) $encoded);

                } else {

                    $extension = $image->getClientOriginalExtension();
                    $path = 'diet_plans/' . $imageName . '.' . $extension;

                    Storage::disk('public')->put(
                        $path,
                        file_get_contents($image)
                    );
                }

                $dietPlan->diet_plan_image = $path;
            }

            $dietPlan->fill($request->only([
                'diet_plans_name',
                'diet_plan_description',
                'diet_plan_goal',
                'daily_calorie_target',
            ]));

            if ($dietPlan->isDirty()) {
                $dietPlan->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Diet plan updated successfully',
                'data' => $dietPlan
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Diet plan update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // public function updateDietPlan(Request $request, $diet_plan_id)
    // {
    //     $request->validate([
    //         'diet_plans_name' => 'sometimes|string|max:50|unique:diet_plans,diet_plans_name,' . $diet_plan_id . ',diet_plan_id',
    //         'diet_plan_description' => 'sometimes|string|max:255',
    //         'diet_plan_goal' => 'sometimes|integer|exists:goals,goal_id',
    //         'daily_calorie_target' => 'sometimes|integer|min:500',
    //         'diet_plan_image' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:2048',
    //     ]);

    //     DB::beginTransaction();

    //     try {

    //         $dietPlan = DietPlans::findOrFail($diet_plan_id);

    //         // Handle image update
    //         if ($request->hasFile('diet_plan_image')) {

    //             // Delete old image if exists
    //             if (
    //                 $dietPlan->diet_plan_image &&
    //                 Storage::disk('public')->exists($dietPlan->diet_plan_image)
    //             ) {
    //                 Storage::disk('public')->delete($dietPlan->diet_plan_image);
    //             }

    //             // Store new image
    //             $imagePath = $request->file('diet_plan_image')
    //                 ->store('diet_plans', 'public');

    //             $dietPlan->diet_plan_image = $imagePath;
    //         }

    //         // Update only provided fields
    //         $dietPlan->fill($request->only([
    //             'diet_plans_name',
    //             'diet_plan_description',
    //             'diet_plan_goal',
    //             'daily_calorie_target',
    //         ]));

    //         // Save only if something actually changed
    //         if ($dietPlan->isDirty()) {
    //             $dietPlan->save();
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Diet plan updated successfully',
    //             'data' => $dietPlan
    //         ], 200);

    //     } catch (\Exception $e) {
    //         DB::rollBack();

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Diet plan update failed',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }


    // Delete Diet Plan
    // ----------------
    public function deleteDietPlan($diet_plan_id)
    {
        DB::beginTransaction();

        try {

            // Check if diet plan is used in diet_meals
            $isUsed = Meal::where('diet_plan_id', $diet_plan_id)->exists();

            if ($isUsed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Diet plan cannot be deleted because it is used in diet meals'
                ], 409); // Conflict
            }

            $dietPlan = DietPlans::findOrFail($diet_plan_id);

            // Delete diet plan image if exists
            if (
                $dietPlan->diet_plan_image &&
                Storage::disk('public')->exists($dietPlan->diet_plan_image)
            ) {
                Storage::disk('public')->delete($dietPlan->diet_plan_image);
            }

            // Delete diet plan record
            $dietPlan->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Diet plan and image deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Diet plan delete failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }



}
