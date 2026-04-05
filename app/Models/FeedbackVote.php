<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeedbackVote extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'github_issue_number'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
