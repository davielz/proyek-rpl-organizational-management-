<?php

namespace App\Models;

use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'created_by',
        'title',
        'slug',
        'description',
        'location',
        'start_time',
        'end_time',
        'quota',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'quota' => 'integer',
        ];
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function registrations()
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'event_registrations')->withPivot('status', 'registered_at', 'attended_at')->withTimestamps();
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function proposal()
    {
        return $this->hasOne(Document::class)->where('type', 'proposal');
    }

    public function lpj()
    {
        return $this->hasOne(Document::class)->where('type', 'lpj');
    }

    public function feedbacks()
    {
        return $this->hasMany(EventFeedback::class);
    }
}
