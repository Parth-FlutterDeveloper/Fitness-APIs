<?php

namespace App\Http\Controllers\Api\Admin\DietMeal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Meal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class MealController extends Controller
{

    // Get All Meals
    public function getAllMeals()
    {
        $meals = Meal::all();

        if ($meals->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No meals found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $meals
        ]);
    }


    // Insert New Meal
    // ---------------
    public function insertMeal(Request $request)
    {
        $request->validate([
            'diet_plan_id'    => 'required|integer|exists:diet_plans,diet_plan_id',
            'meal_name'       => 'required|string|max:30|unique:diet_meals,meal_name',
            'meal_description'=> 'required|string|max:100',
            'meal_type'       => 'required|in:breakfast,lunch,dinner,snack',
            'meal_calories'   => 'required|integer|min:0',
            'meal_protein'    => 'required|numeric|min:0',
            'meal_carbs'      => 'required|numeric|min:0',
            'meal_fats'       => 'required|numeric|min:0',
            'meal_recipe'     => 'required|string',
            'meal_image'      => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        DB::beginTransaction();

        try {

            $image = $request->file('meal_image');
            $originalSize = $image->getSize(); // bytes

            $imageName = time() . '_' . uniqid();
            $imagePath = '';

            // ✅ Compress ONLY if image > 200KB
            if ($originalSize > 200 * 1024) {

                $manager = new ImageManager(new Driver());
                $img = $manager->read($image);

                // Resize width to 600px (auto height)
                $img->scale(width: 600);

                // Convert to WebP with 75% quality
                $encoded = $img->toWebp(75);

                $imagePath = 'meals/' . $imageName . '.webp';

                Storage::disk('public')->put($imagePath, (string) $encoded);

            } else {

                // Store original if already small
                $extension = $image->getClientOriginalExtension();
                $imagePath = 'meals/' . $imageName . '.' . $extension;

                Storage::disk('public')->put(
                    $imagePath,
                    file_get_contents($image)
                );
            }

            // Create Meal Record
            $meal = Meal::create([
                'diet_plan_id'    => $request->diet_plan_id,
                'meal_name'       => $request->meal_name,
                'meal_description'=> $request->meal_description,
                'meal_type'       => $request->meal_type,
                'meal_calories'   => $request->meal_calories,
                'meal_protein'    => $request->meal_protein,
                'meal_carbs'      => $request->meal_carbs,
                'meal_fats'       => $request->meal_fats,
                'meal_recipe'     => $request->meal_recipe,
                'meal_image'      => $imagePath,
                'created_at'      => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Meal created successfully',
                'data'    => $meal
            ], 201);

        } catch (\Exception $e) {

            DB::rollBack();

            if (isset($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to create meal',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // public function insertMeal(Request $request)
    // {
    //     $request->validate([
    //         'diet_plan_id'   => 'required|integer|exists:diet_plans,diet_plan_id',
    //         'meal_name'      => 'required|string|max:30|unique:diet_meals,meal_name',
    //         'meal_description'=> 'required|string|max:100',
    //         'meal_type'      => 'required|in:breakfast,lunch,dinner,snack',
    //         'meal_calories'  => 'required|integer|min:0',
    //         'meal_protein'   => 'required|numeric|min:0',
    //         'meal_carbs'     => 'required|numeric|min:0',
    //         'meal_fats'      => 'required|numeric|min:0',
    //         'meal_recipe'    => 'required|string',
    //         'meal_image'     => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
    //     ]);

    //     DB::beginTransaction();

    //     try {

    //         // Store image in storage/app/public/meals
    //         $imagePath = $request->file('meal_image')
    //             ->store('meals', 'public');

    //         // Create meal record
    //         $meal = Meal::create([
    //             'diet_plan_id'   => $request->diet_plan_id,
    //             'meal_name'      => $request->meal_name,
    //             'meal_description'=> $request->meal_description,
    //             'meal_type'      => $request->meal_type,
    //             'meal_calories'  => $request->meal_calories,
    //             'meal_protein'   => $request->meal_protein,
    //             'meal_carbs'     => $request->meal_carbs,
    //             'meal_fats'      => $request->meal_fats,
    //             'meal_recipe'    => $request->meal_recipe,
    //             'meal_image'     => $imagePath,
    //             'created_at'     => now()
    //         ]);

    //         DB::commit();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Meal created successfully',
    //             'data' => $meal
    //         ], 201);

    //     } catch (\Exception $e) {

    //         DB::rollBack();

    //         if (isset($imagePath)) {
    //             Storage::disk('public')->delete($imagePath);
    //         }

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to create meal',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }


    // Update Meal
    // -----------
    public function updateMeal(Request $request, $meal_id)
    {
        $request->validate([
            'diet_plan_id'    => 'sometimes|integer|exists:diet_plans,diet_plan_id',
            'meal_name'       => 'sometimes|string|max:30|unique:diet_meals,meal_name,' . $meal_id . ',meal_id',
            'meal_description'=> 'sometimes|string|max:100',
            'meal_type'       => 'sometimes|in:breakfast,lunch,dinner,snack',
            'meal_calories'   => 'sometimes|integer|min:0',
            'meal_protein'    => 'sometimes|numeric|min:0',
            'meal_carbs'      => 'sometimes|numeric|min:0',
            'meal_fats'       => 'sometimes|numeric|min:0',
            'meal_recipe'     => 'sometimes|string',
            'meal_image'      => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        DB::beginTransaction();

        try {

            $meal = Meal::findOrFail($meal_id);
            $oldImage = $meal->meal_image;
            $imagePath = $oldImage; // default keep old

            // =============================
            // Handle Image Update + Compress
            // =============================
            if ($request->hasFile('meal_image')) {

                $image = $request->file('meal_image');
                $originalSize = $image->getSize(); // bytes
                $imageName = time() . '_' . uniqid();

                // Compress only if > 200KB
                if ($originalSize > 200 * 1024) {

                    $manager = new ImageManager(new Driver());
                    $img = $manager->read($image);

                    $img->scale(width: 600);

                    $encoded = $img->toWebp(75);

                    $imagePath = 'meals/' . $imageName . '.webp';

                    Storage::disk('public')->put($imagePath, (string) $encoded);

                } else {

                    $extension = $image->getClientOriginalExtension();
                    $imagePath = 'meals/' . $imageName . '.' . $extension;

                    Storage::disk('public')->put(
                        $imagePath,
                        file_get_contents($image)
                    );
                }

                // Delete old image AFTER new image stored
                if ($oldImage && Storage::disk('public')->exists($oldImage)) {
                    Storage::disk('public')->delete($oldImage);
                }

                $meal->meal_image = $imagePath;
            }

            // =============================
            // Update Other Fields
            // =============================
            $meal->fill($request->only([
                'diet_plan_id',
                'meal_name',
                'meal_description',
                'meal_type',
                'meal_calories',
                'meal_protein',
                'meal_carbs',
                'meal_fats',
                'meal_recipe'
            ]));

            // Save only if something changed
            if ($meal->isDirty()) {
                $meal->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Meal updated successfully',
                'data'    => $meal
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            // If new image was created but DB failed → delete it
            if (isset($imagePath) && $imagePath !== $oldImage) {
                Storage::disk('public')->delete($imagePath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Meal update failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // public function updateMeal(Request $request, $meal_id)
    // {
    //     $request->validate([
    //         'diet_plan_id'   => 'sometimes|integer|exists:diet_plans,diet_plan_id',
    //         'meal_name'      => 'sometimes|string|max:30|unique:diet_meals,meal_name,' . $meal_id . ',meal_id',
    //         'meal_description'=> 'sometimes|string|max:100',
    //         'meal_type'      => 'sometimes|in:breakfast,lunch,dinner,snack',
    //         'meal_calories'  => 'sometimes|integer|min:0',
    //         'meal_protein'   => 'sometimes|numeric|min:0',
    //         'meal_carbs'     => 'sometimes|numeric|min:0',
    //         'meal_fats'      => 'sometimes|numeric|min:0',
    //         'meal_recipe'    => 'sometimes|string',
    //         'meal_image'     => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:2048',
    //     ]);

    //     DB::beginTransaction();

    //     try {

    //         $meal = Meal::findOrFail($meal_id);

    //         // Handle image update
    //         if ($request->hasFile('meal_image')) {

    //             // Delete old image if exists
    //             if (
    //                 $meal->meal_image &&
    //                 Storage::disk('public')->exists($meal->meal_image)
    //             ) {
    //                 Storage::disk('public')->delete($meal->meal_image);
    //             }

    //             // Store new image
    //             $imagePath = $request->file('meal_image')
    //                 ->store('meals', 'public');

    //             $meal->meal_image = $imagePath;
    //         }

    //         // Update only provided fields
    //         $meal->fill($request->only([
    //             'diet_plan_id',
    //             'meal_name',
    //             'meal_description',
    //             'meal_type',
    //             'meal_calories',
    //             'meal_protein',
    //             'meal_carbs',
    //             'meal_fats',
    //             'meal_recipe'
    //         ]));

    //         // Save only if something changed
    //         if ($meal->isDirty()) {
    //             $meal->save();
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Meal updated successfully',
    //             'data' => $meal
    //         ], 200);

    //     } catch (\Exception $e) {

    //         DB::rollBack();

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Meal update failed',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }


    // Delete Meal
    // -----------
    public function deleteMeal($meal_id)
    {
        DB::beginTransaction();

        try {

            $meal = Meal::find($meal_id);

            if (!$meal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Meal not found'
                ], 404);
            }

            // Delete image if exists
            if (
                $meal->meal_image &&
                Storage::disk('public')->exists($meal->meal_image)
            ) {
                Storage::disk('public')->delete($meal->meal_image);
            }

            // Delete meal record
            $meal->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Meal deleted successfully'
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Meal deletion failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }


}
