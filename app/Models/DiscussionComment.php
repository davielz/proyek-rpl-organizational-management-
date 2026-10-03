<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscussionComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'discussion_post_id',
        'user_id',
        'content',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(DiscussionPost::class, 'discussion_post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
