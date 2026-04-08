<?php

namespace App\Services;

use Illuminate\Support\Facades\Http; // ✅ IMPORTANT
use Illuminate\Support\Facades\Log;

class GeminiService
{

    // Workout Function
    // ================

    // public function generateWorkout($prompt)
    // {
    //     $apiKey = env('GEMINI_API_KEY');

    //     $response = Http::post(
    //         "https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key={$apiKey}",
    //         [
    //             "contents" => [
    //                 [
    //                     "parts" => [
    //                         ["text" => $prompt]
    //                     ]
    //                 ]
    //             ]
    //         ]
    //     );

    //     return $response->json();
    // }


    public function generateWorkout($prompt)
    {
        $apiKey = env('GEMINI_API_KEY');

        $response = Http::timeout(30)->post(
            "https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key={$apiKey}",
            [
                "contents" => [
                    [
                        "parts" => [
                            ["text" => $prompt]
                        ]
                    ]
                ]
            ]
        );

        $result = $response->json();

        // Debug log
        Log::info("Gemini RAW Response:", $result ?? []);

        // API error
        if (!$response->successful()) {
            Log::error("Gemini API Error:", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return [];
        }

        // Empty candidates
        if (empty($result['candidates'])) {
            Log::warning("Gemini returned empty candidates");
            return [];
        }

        // Safety block
        if (($result['candidates'][0]['finishReason'] ?? '') === 'SAFETY') {
            Log::warning("Gemini blocked by safety");
            return [];
        }

        return $result;
    }


    
    // Diet Function
    // =============

    public function generateDiet($prompt)
    {
        $apiKey = env('GEMINI_API_KEY');

        // $response = Http::timeout(30)->post(
        //     "https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key={$apiKey}",
        //     [
        //         "contents" => [
        //             [
        //                 "parts" => [
        //                     ["text" => $prompt]
        //                 ]
        //             ]
        //         ]
        //     ]
        // );

        $response = Http::timeout(90)
            ->retry(3, 2000) // 3 retries, 2 sec gap
            ->post(
                "https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key={$apiKey}",
                [
                    "contents" => [
                        [
                            "parts" => [
                                ["text" => $prompt]
                            ]
                        ]
                    ]
                ]
            );

        $result = $response->json();

        \Log::info("Gemini Diet RAW Response:", $result ?? []);

        if (!$response->successful()) {
            \Log::error("Gemini Diet API Error:", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return [];
        }

        if (empty($result['candidates'])) {
            \Log::warning("Gemini Diet empty candidates");
            return [];
        }

        if (($result['candidates'][0]['finishReason'] ?? '') === 'SAFETY') {
            \Log::warning("Gemini Diet blocked");
            return [];
        }

        return $result;
    }
    

}