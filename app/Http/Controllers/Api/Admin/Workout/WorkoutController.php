<?php

namespace App\Http\Controllers\Api\Admin\Workout;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Workout;
use Illuminate\Support\Facades\DB;

class WorkoutController extends Controller
{
    
    // Workout list (filter by goal optional)
    // --------------------------------------
    public function workouts(Request $request)
    {
        $query = Workout::with(['focusArea', 'goals', 'exercises']);

        if ($request->filled('goal_id')) {
            $query->whereHas('goals', function ($q) use ($request) {
                $q->where('goals.goal_id', $request->goal_id);
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->get()
        ]);
    }


    // Single workout details
    // ----------------------
    public function getWorkout($id)
    {
        $workout = Workout::with(['focusArea', 'goals'])
            ->where('workout_id', $id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $workout
        ]);
    }


    // Exercises inside workout
    // ------------------------
    public function workoutExercises($id)
    {
        $workout = Workout::with('exercises')
            ->where('workout_id', $id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'workout_id' => $id,
            'exercises' => $workout->exercises
        ]);
    }


    // Add New Workout
    // ---------------
    public function addWorkout(Request $request)
    {
        $request->validate([
            'workout_name' => 'required|string|max:20|unique:workout,workout_name',
            'workout_description' => 'required|string|max:100',
            'workout_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
            'workout_focus_area_id' => 'required|integer',
            'workout_duration_minute' => 'integer',
            'workout_difficulty' => 'required|string|max:15',

            'goal_ids' => 'required|array|min:1',
            'goal_ids.*' => 'integer',

            'exercises' => 'required|array|min:1',
            'exercises.*.exercise_id' => 'required|integer',
            'exercises.*.exercise_order' => 'required|integer'
        ]);

        DB::beginTransaction();

        try {
            
            // ==========================
            // Image Compress Logic Added
            // ==========================
            $image = $request->file('workout_image');
            $originalSize = $image->getSize();
            $imageName = time() . '_' . uniqid();
            $imagePath = '';

            // Compress only if > 200KB
            if ($originalSize > 200 * 1024) {

                $manager = new ImageManager(new Driver());
                $img = $manager->read($image);

                $img->scale(width: 600);

                $encoded = $img->toWebp(75);

                $imagePath = 'workouts/' . $imageName . '.webp';

                Storage::disk('public')->put($imagePath, (string) $encoded);

            } else {

                $extension = $image->getClientOriginalExtension();
                $imagePath = 'workouts/' . $imageName . '.' . $extension;

                Storage::disk('public')->put(
                    $imagePath,
                    file_get_contents($image)
                );
            }

            // Create workout
            $workout = Workout::create([
                'workout_name' => $request->workout_name,
                'workout_description' => $request->workout_description,
                'workout_image' => $imagePath,
                'workout_focus_area_id' => $request->workout_focus_area_id,
                'workout_duration_minute' => $request->workout_duration_minute,
                'workout_difficulty' => $request->workout_difficulty,
            ]);

            // Attach goals (pivot: workout_goals)
            $workout->goals()->sync($request->goal_ids);

            // Attach exercises with order (pivot: workout_exercises)
            $exerciseData = [];

            foreach ($request->exercises as $exercise) {
                $exerciseData[$exercise['exercise_id']] = [
                    'exercise_order' => $exercise['exercise_order']
                ];
            }

            $workout->exercises()->sync($exerciseData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Workout created successfully',
                'data' => $workout
            ], 201);

        } catch (\Exception $e) {

            DB::rollBack();

            // Delete uploaded image if failed
            if (isset($imagePath) && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to create workout'
            ], 500);
        }
    }

    // public function addWorkout(Request $request)
    // {
    //     $request->validate([
    //         'workout_name' => 'required|string|max:20|unique:workout,workout_name',
    //         'workout_description' => 'required|string|max:100',
    //         'workout_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
    //         'workout_focus_area_id' => 'required|integer',
    //         'workout_duration_minute' => 'required|integer',
    //         'workout_difficulty' => 'required|string|max:15',

    //         'goal_ids' => 'required|array|min:1',
    //         'goal_ids.*' => 'integer',

    //         'exercises' => 'required|array|min:1',
    //         'exercises.*.exercise_id' => 'required|integer',
    //         'exercises.*.exercise_order' => 'required|integer'
    //     ]);

    //     DB::beginTransaction();

    //     try {
            
    //         // Store image
    //         $imagePath = $request->file('workout_image')
    //             ->store('workouts', 'public');
                
    //         // Create workout
    //         $workout = Workout::create([
    //             'workout_name' => $request->workout_name,
    //             'workout_description' => $request->workout_description,
    //             'workout_image' => $imagePath,
    //             'workout_focus_area_id' => $request->workout_focus_area_id,
    //             'workout_duration_minute' => $request->workout_duration_minute,
    //             'workout_difficulty' => $request->workout_difficulty,
    //         ]);

    //         // Attach goals (pivot: workout_goals)
    //         $workout->goals()->sync($request->goal_ids);

    //         // Attach exercises with order (pivot: workout_exercises)
    //         $exerciseData = [];

    //         foreach ($request->exercises as $exercise) {
    //             $exerciseData[$exercise['exercise_id']] = [
    //                 'exercise_order' => $exercise['exercise_order']
    //             ];
    //         }

    //         $workout->exercises()->sync($exerciseData);

    //         DB::commit();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Workout created successfully',
    //             'data' => $workout
    //         ], 201);

    //     } catch (\Exception $e) {
    //         DB::rollBack();

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to create workout'
    //         ], 500);
    //     }
    // }


    // Update Workout Details + Goals
    // ------------------------------
    public function updateWorkout(Request $request, $workout_id)
    {
        $request->validate([
            'workout_name' => 'sometimes|string|max:20|unique:workout,workout_name,' . $workout_id . ',workout_id',
            'workout_description' => 'sometimes|string|max:100',
            'workout_image' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:4096',
            'workout_focus_area_id' => 'sometimes|integer',
            'workout_duration_minute' => 'sometimes|integer',
            'workout_difficulty' => 'sometimes|string|max:15',

            'goal_ids' => 'sometimes|array|min:1',
            'goal_ids.*' => 'integer',
        ]);

        DB::beginTransaction();

        try {

            $workout = Workout::findOrFail($workout_id);
            $oldImage = $workout->workout_image;
            $imagePath = $oldImage;

            // ==========================
            // Image Compress Logic Added
            // ==========================
            if ($request->hasFile('workout_image')) {

                $image = $request->file('workout_image');
                $originalSize = $image->getSize();
                $imageName = time() . '_' . uniqid();

                // Compress only if > 200KB
                if ($originalSize > 200 * 1024) {

                    $manager = new ImageManager(new Driver());
                    $img = $manager->read($image);

                    $img->scale(width: 600);

                    $encoded = $img->toWebp(75);

                    $imagePath = 'workouts/' . $imageName . '.webp';

                    Storage::disk('public')->put($imagePath, (string) $encoded);

                } else {

                    $extension = $image->getClientOriginalExtension();
                    $imagePath = 'workouts/' . $imageName . '.' . $extension;

                    Storage::disk('public')->put(
                        $imagePath,
                        file_get_contents($image)
                    );
                }

                // Delete old image AFTER new one stored
                if ($oldImage && Storage::disk('public')->exists($oldImage)) {
                    Storage::disk('public')->delete($oldImage);
                }

                $workout->workout_image = $imagePath;
            }

            // Update only provided fields
            $workout->fill($request->only([
                'workout_name',
                'workout_description',
                'workout_focus_area_id',
                'workout_duration_minute',
                'workout_difficulty',
            ]));

            // Save ONLY if values are actually different
            if ($workout->isDirty()) {
                $workout->save();
            }

            // Update goals only if sent
            if ($request->has('goal_ids')) {
                $workout->goals()->sync($request->goal_ids);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Workout updated successfully'
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            // If new image created but DB failed → delete it
            if (isset($imagePath) && $imagePath !== $oldImage) {
                Storage::disk('public')->delete($imagePath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Workout update failed'
            ], 500);
        }
    }

    // public function updateWorkout(Request $request, $workout_id)
    // {
    //     $request->validate([
    //         'workout_name' => 'sometimes|string|max:20|unique:workout,workout_name,' . $workout_id . ',workout_id',
    //         'workout_description' => 'sometimes|string|max:100',
    //         'workout_image' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:2048',
    //         'workout_focus_area_id' => 'sometimes|integer',
    //         'workout_duration_minute' => 'sometimes|integer',
    //         'workout_difficulty' => 'sometimes|string|max:15',

    //         'goal_ids' => 'sometimes|array|min:1',
    //         'goal_ids.*' => 'integer',
    //     ]);

    //     DB::beginTransaction();

    //     try {

    //         $workout = Workout::findOrFail($workout_id);

    //         if ($request->hasFile('workout_image')) {
    //             // Delete old image if exists
    //             if ($workout->workout_image && Storage::disk('public')->exists($workout->workout_image)) {
    //                 Storage::disk('public')->delete($workout->workout_image);
    //             }
    //             // Store new image
    //             $imagePath = $request->file('workout_image')
    //                 ->store('workouts', 'public');

    //             $workout->workout_image = $imagePath;   
    //         }

    //         // Update only provided fields
    //         $workout->fill($request->only([
    //             'workout_name',
    //             'workout_description',
    //             'workout_focus_area_id',
    //             'workout_duration_minute',
    //             'workout_difficulty',
    //         ]));

    //         // Save ONLY if values are actually different
    //         if ($workout->isDirty()) {
    //             $workout->save();
    //         }

    //         // Update goals only if sent
    //         if ($request->has('goal_ids')) {
    //             $workout->goals()->sync($request->goal_ids);
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Workout updated successfully'
    //         ]);

    //     } catch (\Exception $e) {
    //         DB::rollBack();

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Workout update failed'
    //         ], 500);
    //     }
    // }


    // Update Exercise In Workout
    // --------------------------

    
    // Add Excercise in Workout
    // ------------------------
    public function addExerciseToWorkout(Request $request, $workout_id)
    {

        $request->validate([
            'exercise_id' => 'required|integer|exists:exercise,exercise_id',
        ]);

        $workout = Workout::findOrFail($workout_id);

        // Prevent duplicate attach
        if ($workout->exercises()
            ->where('workout_exercises.exercise_id', $request->exercise_id)
            ->exists()
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Exercise already added to workout'
            ], 422);
        }

        // Get next order
        $nextOrder = $workout->exercises()
            ->max('workout_exercises.exercise_order');

        $nextOrder = $nextOrder ? $nextOrder + 1 : 1;

        // Attach with auto order
        $workout->exercises()->attach($request->exercise_id, [
            'exercise_order' => $nextOrder
        ]);

        // 🔥 Update workout duration
        $this->updateWorkoutDuration($workout_id);

        return response()->json([
            'success' => true,
            'message' => 'Exercise added to workout',
            'exercise_order' => $nextOrder
        ]);
    }


    // Remove Exercise from Workout
    // ----------------------------
    public function removeExerciseFromWorkout(Request $request, $workout_id)
    {
        $request->validate([
            'exercise_id' => 'required|integer|exists:exercise,exercise_id',
        ]);

        $workout = Workout::findOrFail($workout_id);

        DB::beginTransaction();

        try {

            // Check if exercise exists in workout
            $exists = DB::table('workout_exercises')
                ->where('workout_id', $workout_id)
                ->where('exercise_id', $request->exercise_id)
                ->exists();

            if (!$exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Exercise not found in workout'
                ], 404);
            }

            // Remove exercise from workout
            DB::table('workout_exercises')
                ->where('workout_id', $workout_id)
                ->where('exercise_id', $request->exercise_id)
                ->delete();

            // AUTO-FIX order (1,2,3...)
            $this->fixExerciseOrder($workout_id);

            // 🔥 Update workout duration
            $this->updateWorkoutDuration($workout_id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Exercise removed from workout successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove exercise'
            ], 500);
        }
    }

    // Update Workout Duration After Add or Remove Exercise
    // ----------------------------------------------------
    private function updateWorkoutDuration($workout_id)
    {
        $totalSeconds = \DB::table('workout_exercises')
            ->join('exercise', 'exercise.exercise_id', '=', 'workout_exercises.exercise_id')
            ->where('workout_exercises.workout_id', $workout_id)
            ->sum('exercise.exercise_duration_second');

        $minutes = ceil($totalSeconds / 60);

        \DB::table('workout')
            ->where('workout_id', $workout_id)
            ->update([
                'workout_duration_minute' => $minutes
            ]);
    }

    // Reorder Exercise in Workout
    // ---------------------------
    public function reorderWorkoutExercises(Request $request, $workout_id)
    {
        $request->validate([
            'exercises' => 'required|array|min:1',
            'exercises.*.exercise_id' => 'required|integer|exists:exercise,exercise_id',
            'exercises.*.exercise_order' => 'required|integer|min:1',
        ]);

        $workout = Workout::findOrFail($workout_id);

        DB::beginTransaction();

        try {

            // Update orders sent by frontend
            foreach ($request->exercises as $item) {
                DB::table('workout_exercises')
                    ->where('workout_id', $workout_id)
                    ->where('exercise_id', $item['exercise_id'])
                    ->update([
                        'exercise_order' => $item['exercise_order']
                    ]);
            }

            // AUTO-FIX order gaps (1,2,3...)
            $this->fixExerciseOrder($workout_id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Exercise order updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Reorder failed'
            ], 500);
        }
    }
 

    // AUTO-FIX order gaps (1,2,3...)
    // ------------------------------
    private function fixExerciseOrder($workout_id)
    {
        $exercises = DB::table('workout_exercises')
            ->where('workout_id', $workout_id)
            ->orderBy('exercise_order')
            ->get();

        $order = 1;

        foreach ($exercises as $exercise) {
            DB::table('workout_exercises')
                ->where('workout_id', $workout_id)
                ->where('exercise_id', $exercise->exercise_id)
                ->update([
                    'exercise_order' => $order
                ]);

            $order++;
        }
    }


    // Delete Workout
    // --------------
    public function deleteWorkout($workout_id)
    {
        DB::beginTransaction();

        try {
            $workout = Workout::findOrFail($workout_id);

            // Delete workout image if exists
            if ($workout->workout_image && Storage::disk('public')->exists($workout->workout_image)) {
                Storage::disk('public')->delete($workout->workout_image);
            }

            // Delete pivot / related records
            $workout->exercises()->detach();
            $workout->goals()->detach();

            // Delete workout
            $workout->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Workout and image deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Workout delete failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }


}
