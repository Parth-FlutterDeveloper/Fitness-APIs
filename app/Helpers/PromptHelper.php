<?php

namespace App\Helpers;

class PromptHelper
{
    public static function generateWorkoutPrompt($user, $request, $exerciseList)
    {
    return "
You are an expert AI fitness trainer.

Your task is to generate a SAFE, EFFECTIVE, and STRUCTURED workout plan.

========================
USER DETAILS
========================
Age: {$user->age} 
Weight: {$user->weight} kg
Height: {$user->height} cm
Body Type: {$request->body_type}

Goal: {$request->goal}
Focus Area: {$request->focus_area}
Difficulty: {$request->difficulty}
Workout Duration: {$request->duration} minutes

========================
AVAILABLE EXERCISES
========================
Each exercise has:
- exercise_id
- exercise_name
- focus_areas (array)

IMPORTANT:
You MUST ONLY select exercises from this list.

" . json_encode($exerciseList) . "

========================
STRICT RULES (VERY IMPORTANT)
========================

1. Exercise Selection:
- ONLY use exercises from the given list
- DO NOT create new exercises
- Each exercise has 'focus_areas' (array)
- ONLY select exercises where user's focus_area exists inside focus_areas

2. ID Validation:
- ALWAYS use correct exercise_id from list
- DO NOT guess or modify IDs

3. Workout Structure:
- You MUST strictly follow the user's difficulty: {$request->difficulty}
- Beginner: 4–5 exercises
- Intermediate: 5–7 exercises
- Advanced: 7–10 exercises

4. Body Type Logic:
- ectomorph → higher sets, moderate reps (muscle gain)
- mesomorph → balanced sets and reps
- endomorph → higher reps, include fat-burning style

5. Time Constraint:
- Total workout must fit within {$request->duration} minutes
- Each exercise duration must be realistic (20–60 seconds)

6. XP (Experience Points):
- Each exercise MUST include XP
- XP should be based on difficulty:
    - Easy exercise → 5 to 10 XP
    - Medium exercise → 10 to 20 XP
    - Hard exercise → 20 to 40 XP
- Higher sets/reps/duration = higher XP
- XP must be realistic and balanced

7. Safety:
- Do not repeat the same exercise
- Keep workout practical and realistic

========================
OUTPUT FORMAT (STRICT JSON ONLY)
========================

- RETURN ONLY JSON
- NO explanation
- NO text before or after JSON
- NO markdown (no ```)
- Return ONLY pure JSON.
- Do NOT include any text, explanation, or markdown.
- Start directly with { and end with }

VALID JSON FORMAT:

{
  \"workout_name\": \"string\",
  \"difficulty\": \"Beginner\" | \"Intermediate\" | \"Advanced\",
  \"exercises\": [
    {
      \"exercise_id\": number,
      \"sets\": number,
      \"reps\": number,
      \"duration\": number,
      \"xp\": number
    }
  ]
}

========================
FINAL INSTRUCTION
========================
If exact rules cannot be followed:
- Try your best to generate a valid workout
- Slightly relax constraints if needed
- NEVER return empty JSON

You MUST always return a valid workout JSON.

Now generate the workout plan.
";
    }
}