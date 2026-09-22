<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'phone', 'avatar'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isPengurus(): bool
    {
        return $this->role === 'pengurus';
    }

    public function isAnggota(): bool
    {
        return $this->role === 'anggota';
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class)->withPivot('role_in_org')->withTimestamps();
    }

    public function events()
    {
        return $this->hasMany(Event::class, 'created_by');
    }

    public function eventRegistrations()
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function registeredEvents()
    {
        return $this->belongsToMany(Event::class, 'event_registrations')->withPivot('status', 'registered_at', 'attended_at')->withTimestamps();
    }

    public function submittedDocuments()
    {
        return $this->hasMany(Document::class, 'submitted_by');
    }

    public function approvedDocuments()
    {
        return $this->hasMany(Document::class, 'approved_by');
    }

    public function feedbacks()
    {
        return $this->hasMany(EventFeedback::class);
    }
}
