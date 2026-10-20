<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'mfa_enrolled_at' => 'datetime',
            'mfa_enabled' => 'boolean',
            'mfa_recovery_hashes' => 'array',
            'mfa_locked_until' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Lifecycle gate (user_lifecycle_status migration): only `active`
     * users authenticate. Absence historically meant active, so null
     * reads as active — the migration default covers new rows.
     */
    public function isActive(): bool
    {
        return ($this->status ?? 'active') === 'active';
    }
}
