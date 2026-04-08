<?php

namespace App\Http\Controllers\Api\User\Feedback;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Feedback;

class FeedbackController extends Controller
{
    
    // Get Feedback By User ID
    // -----------------------
    public function getUserFeedback(Request $request)
    {
        $feedbacks = Feedback::where('user_id', $request->user()->user_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'total' => $feedbacks->count(),
            'data' => $feedbacks
        ], 200);
    }


    // Insert Feedback
    // ----------------
    public function insertFeedback(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:user,user_id',
            'feedback_type' => 'required|in:bug,suggestion,complaint,other',
            'feedback_subject' => 'nullable|string|max:30',
            'feedback_message' => 'required|string|max:100'
        ]);

        $feedback = Feedback::create([
            'user_id' => $request->user_id,
            'feedback_type' => $request->feedback_type,
            'feedback_subject' => $request->feedback_subject,
            'feedback_message' => $request->feedback_message,
            'status' => 'pending',
            'created_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Feedback submitted successfully',
            'data' => $feedback
        ], 201);
    }


}
