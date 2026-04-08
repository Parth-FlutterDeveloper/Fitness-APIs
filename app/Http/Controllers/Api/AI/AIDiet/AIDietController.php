<?php

namespace App\Http\Controllers\Api\AI\AIDiet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\GeminiService;
use Illuminate\Support\Facades\DB;
use App\Helpers\DietPromptHelper;
use App\Models\AIDietDays;
use App\Models\AIDietMeals;
use App\Models\AIDietPlans;

class AIDietController extends Controller
{
    
    public function generateDiet(Request $request, GeminiService $gemini)
    {

        set_time_limit(180);

        $request->validate([
            'goal' => 'required|string',
            'body_type' => 'required|in:ectomorph,mesomorph,endomorph',
            'calories' => 'required|integer|min:1000|max:4000'
        ]);

        $user = auth()->user();
        $userId = auth()->id();

        DB::beginTransaction();

        try {

            // 1. Generate Prompt
            $prompt = DietPromptHelper::generateDietPrompt($user, $request);

            // 2. Call Gemini with Retry
            $retry = 0;
            $data = null;

            do {
                $aiResponse = $gemini->generateDiet($prompt);

                $content = $aiResponse['candidates'][0]['content']['parts'][0]['text'] ?? '';

                // Clean response
                $content = trim($content);
                $content = str_replace(['```json', '```'], '', $content);

                // Try direct decode
                $data = json_decode($content, true);

                // Fallback regex extract
                if (!$data) {
                    preg_match('/\{.*\}/s', $content, $matches);
                    $json = $matches[0] ?? null;
                    $data = json_decode($json, true);
                }

                $retry++;

            } while (
                ($data === null || empty($data) || !isset($data['days']))
                && $retry < 2
            );

            // Invalid AI response
            if (
                !$data ||
                !isset($data['days']) ||
                count($data['days']) !== 7
            ) {
                throw new \Exception("Invalid AI response structure");
            }

            // 3. Save Plan
            $plan = AIDietPlans::create([
                'user_id' => $userId,
                'plan_name' => $data['plan_name'] ?? 'AI Diet Plan',
                'plan_goal' => $request->goal,
                'body_type' => $request->body_type,
                'daily_calories' => $request->calories,
                'duration_days' => 7,
                'ai_prompt' => $prompt,
                'ai_response' => json_encode($data),
                'created_at' => now()
            ]);

            // 4. Save Days & Meals
            foreach ($data['days'] as $day) {

                // Validate meals count
                if (!isset($day['meals']) || count($day['meals']) !== 4) {
                    throw new \Exception("Invalid meals count in day " . ($day['day'] ?? 'unknown'));
                }

                $dayModel = AIDietDays::create([
                    'ai_diet_plan_id' => $plan->ai_diet_plan_id,
                    'day_number' => $day['day'],
                    'created_at' => now()
                ]);

                $order = 1;

                foreach ($day['meals'] as $meal) {

                    AIDietMeals::create([
                        'diet_day_id' => $dayModel->diet_day_id,
                        'meal_type' => $meal['meal_type'] ?? 'breakfast',
                        'meal_name' => $meal['meal_name'] ?? 'Meal',
                        'meal_description' => $meal['meal_description'] ?? $meal['meal_name'] ?? 'Healthy meal',                        'calories' => $meal['calories'] ?? 0,
                        'protein' => $meal['protein'] ?? 0,
                        'carbs' => $meal['carbs'] ?? 0,
                        'fats' => $meal['fats'] ?? 0,
                        'meal_order' => $order++,
                        'created_at' => now()
                    ]);
                }
            }

            DB::commit();

            $planData = AIDietPlans::with(['days.meals'])
                ->find($plan->ai_diet_plan_id);

            return response()->json([
                'status' => true,
                'message' => 'Diet plan generated successfully',
                'data' => $planData
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
