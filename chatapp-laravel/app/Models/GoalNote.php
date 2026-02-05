<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoalNote extends Model
{
    protected $table = 'goal_notes';

    // goal_notesテーブルにはupdated_atカラムがないため、タイムスタンプを無効化
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'room_id',
        'message_id',
        'note_text',
        'story_date',
        'story_image',
        'image_comment',
        'created_at',
    ];

    protected $dates = [
        'story_date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function scopeStoryNotes($query)
    {
        return $query->whereNotNull('story_date');
    }

    public function scopeGoalNotes($query)
    {
        return $query->whereNull('story_date');
    }
}
