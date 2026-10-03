<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscussionPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'organization_id',
        'title',
        'content',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(DiscussionComment::class);
    }

    public function commentsCount(): int
    {
        return $this->comments()->count();
    }
}
