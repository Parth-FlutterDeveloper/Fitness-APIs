<?php

namespace App\Http\Controllers\Api\Admin\Exercise;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Exercise;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ExerciseController extends Controller
{
    
    // Get All Exercises
    // -----------------
    public function exercises()
    {
        $exercises = Exercise::orderBy('exercise_id', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Exercise list fetched successfully',
            'data'    => $exercises
        ], 200);
    }


    // Get Exercise By ID
    // ------------------
    public function getExercise($id)
    {
        $exercise = Exercise::where('exercise_id', $id)->first();

        if (!$exercise) {
            return response()->json([
                'success' => false,
                'message' => 'Exercise not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Exercise fetched successfully',
            'data'    => $exercise
        ], 200);
    }


    // Add new Exercise
    // ----------------
    public function addExercise(Request $request)
    {
        $request->validate([
            'exercise_name'             => 'required|string|max:30|unique:exercise,exercise_name',
            'exercise_description'      => 'required|string|max:100',
            'exercise_duration_second'  => 'required|integer|min:1',
            'exercise_sets'             => 'required|integer|min:1',
            'exercise_reps'             => 'required|integer|min:1',
            'exercise_calories_burn'    => 'required|numeric|min:0',
            'exercise_xp'               => 'required|integer|min:0',
            'exercise_gif'              => 'required|file|mimes:gif,mp4,webp|max:5120',
        ]);

        $file = $request->file('exercise_gif');
        $originalSize = $file->getSize();
        $extension = $file->getClientOriginalExtension();
        $fileName = time() . '_' . uniqid();
        $gifPath = '';

        // ----------------------------------------
        // Compress only if GIF or WebP and > 500KB
        // ----------------------------------------
        if (in_array($extension, ['gif', 'webp']) && $originalSize > 500 * 1024) {

            $manager = new ImageManager(new Driver());
            $image = $manager->read($file);

            // Resize width to 300px (keeps animation in v3)
            $image->scale(width: 300);

            $encoded = $image->encode(); // keep same format

            $gifPath = 'exercises_gif/' . $fileName . '.' . $extension;

            Storage::disk('public')->put($gifPath, (string) $encoded);

        } else {

            // Store normally (for mp4 or small file)
            $gifPath = $file->store('exercises_gif', 'public');
        }

        // Create exercise
        $exercise = Exercise::create([
            'exercise_name'             => $request->exercise_name,
            'exercise_description'      => $request->exercise_description,
            'exercise_duration_second'  => $request->exercise_duration_second,
            'exercise_sets'             => $request->exercise_sets,
            'exercise_reps'             => $request->exercise_reps,
            'exercise_calories_burn'    => $request->exercise_calories_burn,
            'exercise_gif_url'          => $gifPath,
            'exercise_xp'               => $request->exercise_xp,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Exercise added successfully',
            'data'    => $exercise
        ], 201);
    }

    // public function addExercise(Request $request)
    // {
    //     $request->validate([
    //         'exercise_name'             => 'required|string|max:30|unique:exercise,exercise_name',
    //         'exercise_description'      => 'required|string|max:100',
    //         'exercise_duration_second'  => 'required|integer|min:1',
    //         'exercise_sets'             => 'required|integer|min:1',
    //         'exercise_reps'             => 'required|integer|min:1',
    //         'exercise_calories_burn'    => 'required|numeric|min:0',
    //         'exercise_xp'               => 'required|integer|min:0',
    //         'exercise_gif'              => 'required|file|mimes:gif,mp4,webp|max:5120',
    //     ]);

    //     // Upload GIF (always present now)
    //     $gifPath = $request->file('exercise_gif')
    //         ->store('exercises_gif', 'public');

    //     // Create exercise
    //     $exercise = Exercise::create([
    //         'exercise_name'             => $request->exercise_name,
    //         'exercise_description'      => $request->exercise_description,
    //         'exercise_duration_second'  => $request->exercise_duration_second,
    //         'exercise_sets'             => $request->exercise_sets,
    //         'exercise_reps'             => $request->exercise_reps,
    //         'exercise_calories_burn'    => $request->exercise_calories_burn,
    //         'exercise_gif_url'          => $gifPath,
    //         'exercise_xp'               => $request->exercise_xp,
    //     ]);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Exercise added successfully',
    //         'data'    => $exercise
    //     ], 201);
    // }


    // Update Exercise
    // ---------------
    public function updateExercise(Request $request, $id)
    {
        $exercise = Exercise::where('exercise_id', $id)->first();

        if (!$exercise) {
            return response()->json([
                'success' => false,
                'message' => 'Exercise not found'
            ], 404);
        }

        $request->validate([
            'exercise_name'            => 'sometimes|string|max:30|unique:exercise,exercise_name,' . $id . ',exercise_id',
            'exercise_description'     => 'sometimes|string|max:100',
            'exercise_duration_second' => 'sometimes|integer|min:1',
            'exercise_sets'            => 'sometimes|integer|min:1',
            'exercise_reps'            => 'sometimes|integer|min:1',
            'exercise_calories_burn'   => 'sometimes|numeric|min:0',
            'exercise_xp'              => 'sometimes|integer|min:0',
            'exercise_gif'             => 'sometimes|file|mimes:gif,webp,mp4|max:5120',
        ]);

        // 🔹 Handle GIF update (same compression logic as insert)
        if ($request->hasFile('exercise_gif')) {

            // Delete old file
            if ($exercise->exercise_gif_url && Storage::disk('public')->exists($exercise->exercise_gif_url)) {
                Storage::disk('public')->delete($exercise->exercise_gif_url);
            }

            $file = $request->file('exercise_gif');
            $extension = strtolower($file->getClientOriginalExtension());
            $originalSize = $file->getSize();
            $fileName = time();

            if (in_array($extension, ['gif', 'webp']) && $originalSize > 500 * 1024) {

                $manager = new ImageManager(new Driver());
                $image = $manager->read($file);

                // Resize width to 300px (keeps animation in v3)
                $image->scale(width: 300);

                $encoded = $image->encode(); // keep same format

                $gifPath = 'exercises_gif/' . $fileName . '.' . $extension;

                Storage::disk('public')->put($gifPath, (string) $encoded);

            } else {

                // Store normally (mp4 or small file)
                $gifPath = $file->store('exercises_gif', 'public');
            }

            $exercise->exercise_gif_url = $gifPath;
        }

        // 🔹 Update only other provided fields
        $exercise->update($request->except('exercise_gif'));

        return response()->json([
            'success' => true,
            'message' => 'Exercise updated successfully',
            'data'    => $exercise
        ], 200);
    }

    // public function updateExercise(Request $request, $id)
    // {
    //     $exercise = Exercise::where('exercise_id', $id)->first();

    //     if (!$exercise) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Exercise not found'
    //         ], 404);
    //     }

    //     $request->validate([
    //         'exercise_name'            => 'sometimes|string|max:30|unique:exercise,exercise_name,' . $id . ',exercise_id',
    //         'exercise_description'     => 'sometimes|string|max:100',
    //         'exercise_duration_second' => 'sometimes|integer|min:1',
    //         'exercise_sets'             => 'sometimes|integer|min:1',
    //         'exercise_reps'             => 'sometimes|integer|min:1',
    //         'exercise_calories_burn'    => 'sometimes|numeric|min:0',
    //         'exercise_xp'               => 'sometimes|integer|min:0',
    //         'exercise_gif'              => 'sometimes|file|mimes:gif,mp4,webp|max:5120',
    //     ]);

    //     // 🔹 Handle GIF update (only if sent)
    //     if ($request->hasFile('exercise_gif')) {

    //         // Delete old GIF if exists
    //         if ($exercise->exercise_gif_url && Storage::disk('public')->exists($exercise->exercise_gif_url)) {
    //             Storage::disk('public')->delete($exercise->exercise_gif_url);
    //         }

    //         // Store new GIF
    //         $gifPath = $request->file('exercise_gif')
    //             ->store('exercises_gif', 'public');

    //         $exercise->exercise_gif_url = $gifPath;
    //     }

    //     // 🔹 Update only provided fields
    //     $exercise->update($request->except('exercise_gif'));

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Exercise updated successfully',
    //         'data'    => $exercise
    //     ], 200);
    // }


    // Delete Exercise
    public function deleteExercise($exercise_id)
    {
        DB::beginTransaction();

        try {
            $exercise = Exercise::findOrFail($exercise_id);

            // Delete related records first
            $exercise->injuries()->delete();
            $exercise->workoutExercises()->delete();
            $exercise->userProgress()->delete();

            // Delete GIF file if exists
            if ($exercise->exercise_gif_url && Storage::disk('public')->exists($exercise->exercise_gif_url)) {
                Storage::disk('public')->delete($exercise->exercise_gif_url);
            }

            // Delete main exercise
            $exercise->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Exercise deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Exercise delete failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
