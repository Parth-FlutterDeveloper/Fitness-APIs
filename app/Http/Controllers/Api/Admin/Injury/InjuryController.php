<?php

namespace App\Http\Controllers\Api\Admin\Injury;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Injury;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class InjuryController extends Controller
{
    

    // Get All Injuries
    // ----------------
    public function getInjuries()
    {
        $injuries = Injury::with(['exercise', 'focusArea'])->get();

        return response()->json([
            'success' => true,
            'message' => 'All injuries fetched successfully',
            'data' => $injuries
        ], 200);
    }

    // Get Single Injury by ID
    // -----------------------
    public function getInjuryById($injury_id)
    {
        $injury = Injury::with(['exercise', 'focusArea'])
            ->where('injury_id', $injury_id)
            ->first();

        if (!$injury) {
            return response()->json([
                'success' => false,
                'message' => 'Injury not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Injury fetched successfully',
            'data' => $injury
        ], 200);
    }


    // Insert Injury
    // -------------
    public function insertInjury(Request $request)
    {
        $request->validate([
            'injury_name'        => 'required|string|max:30|unique:injuries,injury_name',
            'injury_description' => 'required|string|max:100',

            'injury_image'       => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
            'injury_wrong_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
            'injury_right_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',

            'prevention_steps'   => 'nullable|string',
            'recovery_tips'      => 'nullable|string',

            'exercise_id'        => 'required|integer|exists:exercise,exercise_id|unique:injuries,exercise_id',
            'focus_area_id'      => 'required|integer|exists:focus_areas,focus_areas_id',
        ]);

        DB::beginTransaction();

        try {

            $manager = new ImageManager(new Driver());

            // ==========================
            // 1️⃣ Main Injury Image
            // ==========================
            $injuryImage = $request->file('injury_image');
            $injuryImageName = time() . '_main_' . uniqid();
            $injuryImagePath = '';

            if ($injuryImage->getSize() > 200 * 1024) {

                $img = $manager->read($injuryImage);
                $img->scale(width: 600);
                $encoded = $img->toWebp(75);

                $injuryImagePath = 'injuries/main/' . $injuryImageName . '.webp';
                Storage::disk('public')->put($injuryImagePath, (string) $encoded);

            } else {

                $ext = $injuryImage->getClientOriginalExtension();
                $injuryImagePath = 'injuries/main/' . $injuryImageName . '.' . $ext;

                Storage::disk('public')->put(
                    $injuryImagePath,
                    file_get_contents($injuryImage)
                );
            }

            // ==========================
            // 2️⃣ Wrong Exercise Image
            // ==========================
            $wrongImage = $request->file('injury_wrong_image');
            $wrongImageName = time() . '_wrong_' . uniqid();
            $wrongImagePath = '';

            if ($wrongImage->getSize() > 200 * 1024) {

                $img = $manager->read($wrongImage);
                $img->scale(width: 600);
                $encoded = $img->toWebp(75);

                $wrongImagePath = 'injuries/wrong/' . $wrongImageName . '.webp';
                Storage::disk('public')->put($wrongImagePath, (string) $encoded);

            } else {

                $ext = $wrongImage->getClientOriginalExtension();
                $wrongImagePath = 'injuries/wrong/' . $wrongImageName . '.' . $ext;

                Storage::disk('public')->put(
                    $wrongImagePath,
                    file_get_contents($wrongImage)
                );
            }

            // ==========================
            // 3️⃣ Right Exercise Image
            // ==========================
            $rightImage = $request->file('injury_right_image');
            $rightImageName = time() . '_right_' . uniqid();
            $rightImagePath = '';

            if ($rightImage->getSize() > 200 * 1024) {

                $img = $manager->read($rightImage);
                $img->scale(width: 600);
                $encoded = $img->toWebp(75);

                $rightImagePath = 'injuries/right/' . $rightImageName . '.webp';
                Storage::disk('public')->put($rightImagePath, (string) $encoded);

            } else {

                $ext = $rightImage->getClientOriginalExtension();
                $rightImagePath = 'injuries/right/' . $rightImageName . '.' . $ext;

                Storage::disk('public')->put(
                    $rightImagePath,
                    file_get_contents($rightImage)
                );
            }

            // Create injury record
            $injury = Injury::create([
                'injury_name'        => $request->injury_name,
                'injury_description' => $request->injury_description,
                'injury_image'       => $injuryImagePath,
                'injury_wrong_image' => $wrongImagePath,
                'injury_right_image' => $rightImagePath,
                'prevention_steps'   => $request->prevention_steps,
                'recovery_tips'      => $request->recovery_tips,
                'exercise_id'        => $request->exercise_id,
                'focus_area_id'      => $request->focus_area_id,
                'created_at'         => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Injury created successfully',
                'data'    => $injury
            ], 201);

        } catch (\Exception $e) {

            DB::rollBack();

            if (isset($injuryImagePath)) {
                Storage::disk('public')->delete($injuryImagePath);
            }
            if (isset($wrongImagePath)) {
                Storage::disk('public')->delete($wrongImagePath);
            }
            if (isset($rightImagePath)) {
                Storage::disk('public')->delete($rightImagePath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to create injury',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // public function insertInjury(Request $request)
    // {
    //     $request->validate([
    //         'injury_name'        => 'required|string|max:30|unique:injuries,injury_name',
    //         'injury_description' => 'required|string|max:100',

    //         'injury_image'       => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
    //         'injury_wrong_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
    //         'injury_right_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',

    //         'prevention_steps'   => 'nullable|string',
    //         'recovery_tips'      => 'nullable|string',

    //         'exercise_id'        => 'required|integer|exists:exercise,exercise_id|unique:injuries,exercise_id',
    //         'focus_area_id'      => 'required|integer|exists:focus_areas,focus_areas_id',
    //     ]);

    //     DB::beginTransaction();

    //     try {

    //         // Store images in separate folders
    //         $injuryImagePath = $request->file('injury_image')
    //             ->store('injuries/main', 'public');

    //         $wrongImagePath = $request->file('injury_wrong_image')
    //             ->store('injuries/wrong', 'public');

    //         $rightImagePath = $request->file('injury_right_image')
    //             ->store('injuries/right', 'public');

    //         // Create injury record
    //         $injury = Injury::create([
    //             'injury_name'        => $request->injury_name,
    //             'injury_description' => $request->injury_description,
    //             'injury_image'       => $injuryImagePath,
    //             'injury_wrong_image' => $wrongImagePath,
    //             'injury_right_image' => $rightImagePath,
    //             'prevention_steps'   => $request->prevention_steps,
    //             'recovery_tips'      => $request->recovery_tips,
    //             'exercise_id'        => $request->exercise_id,
    //             'focus_area_id'      => $request->focus_area_id,
    //             'created_at'         => now()
    //         ]);

    //         DB::commit();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Injury created successfully',
    //             'data'    => $injury
    //         ], 201);

    //     } catch (\Exception $e) {

    //         DB::rollBack();

    //         // Delete uploaded images if error occurs
    //         if (isset($injuryImagePath)) {
    //             Storage::disk('public')->delete($injuryImagePath);
    //         }
    //         if (isset($wrongImagePath)) {
    //             Storage::disk('public')->delete($wrongImagePath);
    //         }
    //         if (isset($rightImagePath)) {
    //             Storage::disk('public')->delete($rightImagePath);
    //         }

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to create injury',
    //             'error'   => $e->getMessage()
    //         ], 500);
    //     }
    // }
    

    // Update Injury
    // -------------
    public function updateInjury(Request $request, $injury_id)
    {
        $request->validate([
            'injury_name'        => 'sometimes|string|max:30|unique:injuries,injury_name,' . $injury_id . ',injury_id',
            'injury_description' => 'sometimes|string|max:100',

            'injury_image'       => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:4096',
            'injury_wrong_image' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:4096',
            'injury_right_image' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:4096',

            'prevention_steps'   => 'sometimes|nullable|string',
            'recovery_tips'      => 'sometimes|nullable|string',

            'exercise_id'        => 'sometimes|integer|exists:exercise,exercise_id|unique:injuries,exercise_id,' . $injury_id . ',injury_id',
            'focus_area_id'      => 'sometimes|integer|exists:focus_areas,focus_areas_id',
        ]);

        DB::beginTransaction();

        try {

            $injury = Injury::findOrFail($injury_id);
            $manager = new ImageManager(new Driver());

            // ==========================
            // MAIN IMAGE
            // ==========================
            if ($request->hasFile('injury_image')) {

                $oldImage = $injury->injury_image;
                $image = $request->file('injury_image');
                $imageName = time() . '_main_' . uniqid();
                $imagePath = '';

                if ($image->getSize() > 200 * 1024) {

                    $img = $manager->read($image);
                    $img->scale(width: 600);
                    $encoded = $img->toWebp(75);

                    $imagePath = 'injuries/main/' . $imageName . '.webp';
                    Storage::disk('public')->put($imagePath, (string) $encoded);

                } else {

                    $ext = $image->getClientOriginalExtension();
                    $imagePath = 'injuries/main/' . $imageName . '.' . $ext;

                    Storage::disk('public')->put(
                        $imagePath,
                        file_get_contents($image)
                    );
                }

                if ($oldImage && Storage::disk('public')->exists($oldImage)) {
                    Storage::disk('public')->delete($oldImage);
                }

                $injury->injury_image = $imagePath;
            }

            // ==========================
            // WRONG IMAGE
            // ==========================
            if ($request->hasFile('injury_wrong_image')) {

                $oldImage = $injury->injury_wrong_image;
                $image = $request->file('injury_wrong_image');
                $imageName = time() . '_wrong_' . uniqid();
                $imagePath = '';

                if ($image->getSize() > 200 * 1024) {

                    $img = $manager->read($image);
                    $img->scale(width: 600);
                    $encoded = $img->toWebp(75);

                    $imagePath = 'injuries/wrong/' . $imageName . '.webp';
                    Storage::disk('public')->put($imagePath, (string) $encoded);

                } else {

                    $ext = $image->getClientOriginalExtension();
                    $imagePath = 'injuries/wrong/' . $imageName . '.' . $ext;

                    Storage::disk('public')->put(
                        $imagePath,
                        file_get_contents($image)
                    );
                }

                if ($oldImage && Storage::disk('public')->exists($oldImage)) {
                    Storage::disk('public')->delete($oldImage);
                }

                $injury->injury_wrong_image = $imagePath;
            }

            // ==========================
            // RIGHT IMAGE
            // ==========================
            if ($request->hasFile('injury_right_image')) {

                $oldImage = $injury->injury_right_image;
                $image = $request->file('injury_right_image');
                $imageName = time() . '_right_' . uniqid();
                $imagePath = '';

                if ($image->getSize() > 200 * 1024) {

                    $img = $manager->read($image);
                    $img->scale(width: 600);
                    $encoded = $img->toWebp(75);

                    $imagePath = 'injuries/right/' . $imageName . '.webp';
                    Storage::disk('public')->put($imagePath, (string) $encoded);

                } else {

                    $ext = $image->getClientOriginalExtension();
                    $imagePath = 'injuries/right/' . $imageName . '.' . $ext;

                    Storage::disk('public')->put(
                        $imagePath,
                        file_get_contents($image)
                    );
                }

                if ($oldImage && Storage::disk('public')->exists($oldImage)) {
                    Storage::disk('public')->delete($oldImage);
                }

                $injury->injury_right_image = $imagePath;
            }

            // Update only provided fields
            $injury->fill($request->only([
                'injury_name',
                'injury_description',
                'prevention_steps',
                'recovery_tips',
                'exercise_id',
                'focus_area_id'
            ]));

            if ($injury->isDirty()) {
                $injury->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Injury updated successfully',
                'data'    => $injury
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Injury update failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // public function updateInjury(Request $request, $injury_id)
    // {
    //     $request->validate([
    //         'injury_name'        => 'sometimes|string|max:30|unique:injuries,injury_name,' . $injury_id . ',injury_id',
    //         'injury_description' => 'sometimes|string|max:100',

    //         'injury_image'       => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:2048',
    //         'injury_wrong_image' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:2048',
    //         'injury_right_image' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:2048',

    //         'prevention_steps'   => 'sometimes|nullable|string',
    //         'recovery_tips'      => 'sometimes|nullable|string',

    //         // must exist in exercises AND must not repeat except current injury
    //         'exercise_id'        => 'sometimes|integer|exists:exercise,exercise_id|unique:injuries,exercise_id,' . $injury_id . ',injury_id',
    //         'focus_area_id'      => 'sometimes|integer|exists:focus_areas,focus_areas_id',
    //     ]);

    //     DB::beginTransaction();

    //     try {

    //         $injury = Injury::findOrFail($injury_id);

    //         // MAIN INJURY IMAGE
    //         if ($request->hasFile('injury_image')) {

    //             if (
    //                 $injury->injury_image &&
    //                 Storage::disk('public')->exists($injury->injury_image)
    //             ) {
    //                 Storage::disk('public')->delete($injury->injury_image);
    //             }

    //             $injury->injury_image = $request->file('injury_image')
    //                 ->store('injuries/main', 'public');
    //         }

    //         // WRONG IMAGE
    //         if ($request->hasFile('injury_wrong_image')) {

    //             if (
    //                 $injury->injury_wrong_image &&
    //                 Storage::disk('public')->exists($injury->injury_wrong_image)
    //             ) {
    //                 Storage::disk('public')->delete($injury->injury_wrong_image);
    //             }

    //             $injury->injury_wrong_image = $request->file('injury_wrong_image')
    //                 ->store('injuries/wrong', 'public');
    //         }

    //         // RIGHT IMAGE
    //         if ($request->hasFile('injury_right_image')) {

    //             if (
    //                 $injury->injury_right_image &&
    //                 Storage::disk('public')->exists($injury->injury_right_image)
    //             ) {
    //                 Storage::disk('public')->delete($injury->injury_right_image);
    //             }

    //             $injury->injury_right_image = $request->file('injury_right_image')
    //                 ->store('injuries/right', 'public');
    //         }

    //         // Update only provided fields
    //         $injury->fill($request->only([
    //             'injury_name',
    //             'injury_description',
    //             'prevention_steps',
    //             'recovery_tips',
    //             'exercise_id',
    //             'focus_area_id'
    //         ]));

    //         // Save only if something changed
    //         if ($injury->isDirty()) {
    //             $injury->save();
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Injury updated successfully',
    //             'data'    => $injury
    //         ], 200);

    //     } catch (\Exception $e) {

    //         DB::rollBack();

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Injury update failed',
    //             'error'   => $e->getMessage()
    //         ], 500);
    //     }
    // }


    // Delete Injury
    // -------------
    public function deleteInjury($injury_id)
    {
        DB::beginTransaction();
    
        try {

            $injury = Injury::findOrFail($injury_id);

            // Delete main injury image
            if (
                $injury->injury_image &&
                Storage::disk('public')->exists($injury->injury_image)
            ) {
                Storage::disk('public')->delete($injury->injury_image);
            }

            // Delete wrong posture image
            if (
                $injury->injury_wrong_image &&
                Storage::disk('public')->exists($injury->injury_wrong_image)
            ) {
                Storage::disk('public')->delete($injury->injury_wrong_image);
            }

            // Delete right posture image
            if (
                $injury->injury_right_image &&
                Storage::disk('public')->exists($injury->injury_right_image)
            ) {
                Storage::disk('public')->delete($injury->injury_right_image);
            }

            // Delete injury record
            $injury->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Injury deleted successfully'
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete injury',
                'error'   => $e->getMessage()
            ], 500);
        }
    }



}
