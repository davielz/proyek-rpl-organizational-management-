<?php

namespace App\Models;

use Database\Factories\EventFeedbackFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventFeedback extends Model
{
    /** @use HasFactory<EventFeedbackFactory> */
    use HasFactory;

    protected $table = 'event_feedbacks';

    protected $fillable = [
        'event_id',
        'user_id',
        'rating',
        'comment',
        'sentiment',
        'sentiment_score',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'sentiment_score' => 'float',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
