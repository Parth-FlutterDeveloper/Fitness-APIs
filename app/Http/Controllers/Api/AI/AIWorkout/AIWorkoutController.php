<?php

namespace App\Http\Controllers\Api\AI\AIWorkout;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\GeminiService;
use App\Models\Exercise;
use App\Models\AIWorkout;
use App\Models\AIWorkoutExercise;
use Illuminate\Support\Facades\DB;
use App\Helpers\PromptHelper;

class AIWorkoutController extends Controller
{
    
    public function generateAIWorkout(Request $request, GeminiService $gemini)
    {
        $request->validate([
            'goal' => 'required|string',
            'focus_area' => 'required|string',
            'duration' => 'required|integer|min:10|max:120',
            'body_type' => 'required|in:ectomorph,mesomorph,endomorph',
            'difficulty' => 'required|in:beginner,intermediate,advanced'
        ]);

        $user = auth()->user();
        $userId = auth()->id();

        DB::beginTransaction();

        try {

            // 1. Fetch exercises
            $exerciseListRaw = DB::table('exercise as e')
                ->join('workout_exercises as we', 'e.exercise_id', '=', 'we.exercise_id')
                ->join('workout as w', 'we.workout_id', '=', 'w.workout_id')
                ->join('focus_areas as f', 'w.workout_focus_area_id', '=', 'f.focus_areas_id')
                ->select(
                    'e.exercise_id',
                    'e.exercise_name',
                    'f.focus_areas_name as focus_area'
                )
                ->get();

            $exerciseList = [];

            foreach ($exerciseListRaw as $row) {
                $id = $row->exercise_id;

                if (!isset($exerciseList[$id])) {
                    $exerciseList[$id] = [
                        'exercise_id' => $id,
                        'exercise_name' => $row->exercise_name,
                        'focus_areas' => []
                    ];
                }

                if (!in_array($row->focus_area, $exerciseList[$id]['focus_areas'])) {
                    $exerciseList[$id]['focus_areas'][] = $row->focus_area;
                }
            }

            $exerciseList = array_values($exerciseList);

            // 2. Prompt
            $prompt = PromptHelper::generateWorkoutPrompt($user, $request, $exerciseList);

            // 3. 🔁 Retry Gemini
            $retry = 0;
            $maxRetry = 3;
            $data = null;
            $content = '';

            do {
                $aiResponse = $gemini->generateWorkout($prompt);

                // Empty response
                if (empty($aiResponse) || empty($aiResponse['candidates'])) {
                    $retry++;
                    usleep(300000);
                    continue;
                }

                $content = '';

                foreach ($aiResponse['candidates'][0]['content']['parts'] ?? [] as $part) {
                    if (isset($part['text'])) {
                        $content .= $part['text'];
                    }
                }

                $content = trim($content);

                if (empty($content)) {
                    $retry++;
                    continue;
                }

                preg_match('/\{[\s\S]*\}/', $content, $matches);
                $json = $matches[0] ?? null;

                if (!$json) {
                    $retry++;
                    continue;
                }

                $data = json_decode($json, true);

                $retry++;

            } while (
                ($data === null || !isset($data['exercises'])) &&
                $retry < $maxRetry
            );

            // Fallback (NO FAIL SYSTEM)
            if (!$data || !isset($data['exercises'])) {

                $data = [
                    "workout_name" => "Quick Workout",
                    "difficulty" => ucfirst($request->difficulty),
                    "exercises" => collect($exerciseList)
                        ->take(5)
                        ->map(function ($ex) {
                            return [
                                "exercise_id" => $ex['exercise_id'],
                                "sets" => 3,
                                "reps" => 10,
                                "duration" => 30,
                                "xp" => 10
                            ];
                        })->values()->toArray()
                ];
            }

            // 4. Save workout
            $aiWorkout = AIWorkout::create([
                'user_id' => $userId,
                'workout_name' => $data['workout_name'] ?? 'AI Workout',
                'workout_goal' => $request->goal,
                'workout_focus_area' => $request->focus_area,
                'workout_duration' => $request->duration,
                'workout_difficulty' => $request->difficulty,
                'body_type' => $request->body_type,
                'prompt' => $prompt,
                'ai_response' => json_encode($data)
            ]);

            $validIds = collect($exerciseList)->pluck('exercise_id')->toArray();

            // 5. Save exercises
            $order = 1;

            foreach ($data['exercises'] as $ex) {

                if (!isset($ex['exercise_id']) || !in_array($ex['exercise_id'], $validIds)) {
                    continue;
                }

                $exercise = Exercise::find($ex['exercise_id']);
                if (!$exercise) continue;

                $sets = $ex['sets'] ?? 3;
                $reps = $ex['reps'] ?? 10;
                $duration = $ex['duration'] ?? 30;

                $xp = $ex['xp'] ?? round(($sets * $reps * 0.5) + ($duration * 0.2));

                AIWorkoutExercise::create([
                    'ai_workout_id' => $aiWorkout->ai_workout_id,
                    'exercise_id' => $exercise->exercise_id,
                    'exercise_name' => $exercise->exercise_name,
                    'exercise_sets' => $sets,
                    'exercise_reps' => $reps,
                    'exercise_duration_sec' => $duration,
                    'exercise_order' => $order++,
                    'exercise_xp' => $xp
                ]);
            }

            DB::commit();

            // 6. Response
            $exercises = AIWorkoutExercise::leftJoin(
                    'exercise',
                    'ai_workout_exercises.exercise_id',
                    '=',
                    'exercise.exercise_id'
                )
                ->where('ai_workout_id', $aiWorkout->ai_workout_id)
                ->select(
                    'ai_workout_exercises.*',
                    DB::raw("CONCAT('" . url('storage') . "/', exercise.exercise_gif_url) as exercise_gif")
                )
                ->orderBy('exercise_order')
                ->get();

            return response()->json([
                'status' => true,
                'ai_workout_id' => $aiWorkout->ai_workout_id,
                'workout_name' => $aiWorkout->workout_name,
                'difficulty' => $aiWorkout->workout_difficulty,
                'body_type' => $aiWorkout->body_type,
                'exercises' => $exercises
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
