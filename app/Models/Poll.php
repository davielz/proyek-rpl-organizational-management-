<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poll extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'created_by',
        'title',
        'description',
        'deadline',
        'status',
    ];

    protected $casts = [
        'deadline' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(PollOption::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed' || ($this->deadline && $this->deadline->isPast());
    }

    public function hasUserVoted(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->votes()->where('user_id', $user->id)->exists();
    }

    public function getUserVote(?User $user): ?PollVote
    {
        if (! $user) {
            return null;
        }

        return $this->votes()->where('user_id', $user->id)->first();
    }

    public function totalVotes(): int
    {
        return $this->votes()->count();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('deadline', '>', now());
    }
}
