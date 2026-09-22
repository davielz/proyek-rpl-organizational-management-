<?php

namespace App\Models;

use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    protected $fillable = [
        'event_id',
        'submitted_by',
        'approved_by',
        'type',
        'title',
        'background',
        'objectives',
        'budget_plan',
        'summary_content',
        'ai_generated_draft',
        'status',
        'notes',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
