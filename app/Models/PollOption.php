<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PollOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'poll_id',
        'option_text',
    ];

    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class, 'option_id');
    }

    public function votesCount(): int
    {
        return $this->votes()->count();
    }

    public function votePercentage(int $totalPollVotes): float
    {
        if ($totalPollVotes === 0) {
            return 0.0;
        }

        return round(($this->votesCount() / $totalPollVotes) * 100, 1);
    }
}
