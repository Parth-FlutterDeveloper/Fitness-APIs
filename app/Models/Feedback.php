<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Feedback extends Model
{

    protected $table = 'user_feedback';
    protected $primaryKey = 'feedback_id';
    public $timestamps = false; // because you only have created_at manually

    protected $fillable = [
        'user_id',
        'feedback_type',
        'feedback_subject',
        'feedback_message',
        'status',
        'admin_reply',
        'created_at'
    ];

    // Relationship
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

}
