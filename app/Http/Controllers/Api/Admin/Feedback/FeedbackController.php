<?php

namespace App\Http\Controllers\Api\Admin\Feedback;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Feedback;

class FeedbackController extends Controller
{

    // Get All Feedback
    // ----------------
    public function getAllFeedback()    
    {
         $feedbacks = Feedback::with('user:user_id,user_email')
        ->orderBy('created_at', 'desc')
        ->get();

        return response()->json([
            'success' => true,
            'total' => $feedbacks->count(),
            'data' => $feedbacks
        ], 200);
    }
    
    // Reply Feedback
    // --------------
    public function replyFeedback(Request $request, $id)
    {
        // Validate input
        $request->validate([
            'admin_reply' => 'required|string'
        ]);

        // Find feedback
        $feedback = DB::table('user_feedback')->where('feedback_id', $id)->first();

        if (!$feedback) {
            return response()->json([
                'success' => false,
                'message' => 'Feedback not found'
            ], 404);
        }

        // Update reply and status
        DB::table('user_feedback')
            ->where('feedback_id', $id)
            ->update([
                'admin_reply' => $request->admin_reply,
                'status' => 'resolved'
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Reply sent successfully'
        ], 200);
    }

}
