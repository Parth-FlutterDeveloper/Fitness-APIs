<?php

namespace App\Helpers;

class DietPromptHelper{

    public static function generateDietPrompt($user, $request)
    {
    return "
You are an expert AI nutritionist.

Your task is to generate a SAFE, BALANCED, and STRUCTURED 7-day diet plan.

========================
USER DETAILS
========================
Age: {$user->age}
Weight: {$user->weight} kg
Height: {$user->height} cm
Body Type: {$request->body_type}

Goal: {$request->goal}
Daily Calories Target: {$request->calories}

========================
STRICT RULES (VERY IMPORTANT)
========================

1. Plan Structure:
- EXACTLY 7 days (day 1 to day 7)
- Each day MUST include EXACTLY:
  - breakfast
  - lunch
  - dinner
  - snack
- Total meals per day = 4 (no more, no less)

2. Meal Rules:
- Each meal MUST include:
  - meal_type
  - meal_name
  - calories
  - protein
  - carbs
  - fats
- Use realistic Indian foods (roti, dal, rice, paneer, eggs, oats, etc.)
- DO NOT repeat same meal within the same day

3. Nutrition Rules:
- Total calories per day should be CLOSE to {$request->calories} (±100 allowed)
- Maintain balanced macros:
  - Protein: moderate to high
  - Carbs: moderate
  - Fats: controlled
- All macro values must be realistic numbers

4. Goal Logic:
- Fat Loss → lower calories, high protein, low fat
- Muscle Gain → higher calories, higher protein
- Maintenance → balanced calories

5. Body Type Logic:
- ectomorph → higher carbs
- mesomorph → balanced macros
- endomorph → lower carbs, higher protein

6. Safety:
- Meals must be practical and edible
- Avoid extreme or unrealistic diet plans

7. Database Rules (VERY IMPORTANT):
- DO NOT skip any field
- Each meal MUST include:
  - meal_type
  - meal_name
  - meal_description (MANDATORY, never empty)
  - calories
  - protein
  - carbs
  - fats
  - meal_order (1 to 4 for each day)

IMPORTANT:
Return ONLY valid JSON.
Do NOT include any explanation, notes, or markdown.
Ensure JSON is properly formatted and parsable.

========================
OUTPUT FORMAT (STRICT JSON ONLY)
========================

- RETURN ONLY JSON
- NO explanation
- NO text before or after JSON
- NO markdown (no ```)
- Start directly with { and end with }

VALID JSON FORMAT:

{
  \"plan_name\": \"string\",
  \"days\": [
    {
      \"day\": 1,
      \"meals\": [
        {
          \"meal_type\": \"breakfast\",
          \"meal_name\": \"Oats with milk\",
          \"meal_description\": \"A healthy bowl of oats cooked in milk with nuts\",
          \"calories\": 300,
          \"protein\": 10,
          \"carbs\": 40,
          \"fats\": 8,
          \"meal_order\": 1
        },
        {
          \"meal_type\": \"lunch\",
          \"meal_name\": \"Dal roti\",
          \"meal_description\": \"2 whole wheat rotis with dal and salad\",
          \"calories\": 500,
          \"protein\": 18,
          \"carbs\": 65,
          \"fats\": 10,
          \"meal_order\": 2
        }
      ]
    }
  ]
}

========================
FINAL INSTRUCTION
========================

If exact rules cannot be followed:
- Try your best to generate a valid diet plan
- Slightly relax constraints if needed
- NEVER return empty JSON

You MUST always return a valid JSON diet plan.

Now generate the 7-day diet plan.
";
    }

}