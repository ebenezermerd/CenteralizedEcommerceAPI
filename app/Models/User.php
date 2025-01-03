<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'firstName', 'lastName', 'email', 'phone', 'sex', 
        'address', 'password', 'mfa_secret', 'is_mfa_enabled', 'mfa_verified_at', 'company_id', 'country', 'region', 'city',
        'verified', 'email_verified_at', 'image', 'status', 'zip_code', 'email_otp', 'email_otp_expires_at' // Add these fields
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'userId' => $this->id,
            'role' => $this->getRoleNames()->first(),
        ];
    }

    public function getJWTClaims()
    {
        return [];
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_verified' => 'boolean',
        'verified' => 'boolean',
        'email_otp_expires_at' => 'datetime', // Add this line
    ];

    // Add accessor for name
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->firstName . ' ' . $this->lastName
        );
    }

    // Add accessor for avatarUrl
    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image ? url(Storage::url($this->image)) : null
        );
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    /**
     * Scope a query to only include users of a given role.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $role
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRole($query, $role)
    {
        return $query->whereHas('roles', function ($query) use ($role) {
            $query->where('name', $role);
        });
    }

}
