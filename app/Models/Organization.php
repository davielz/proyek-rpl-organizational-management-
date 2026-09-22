<?php

namespace App\Models;

use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'logo',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('role_in_org')->withTimestamps();
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
