<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'property_ownership',
        'house_type',
        'number_of_occupants',
        'number_of_bedrooms',
        'heating_type',
        'property_age',
    ];

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
        'number_of_occupants' => 'integer',
        'number_of_bedrooms' => 'integer',
    ];

    /**
     * Get the user's device analyses
     */
    public function deviceAnalyses()
    {
        return $this->hasMany(DeviceAnalysis::class);
    }

    /**
     * Get the user's saved devices
     */
    public function userDevices()
    {
        return $this->hasMany(UserDevice::class);
    }

    /**
     * Get the user's KEDS bill analyses
     */
    public function kedsBillAnalyses()
    {
        return $this->hasMany(KedsBillAnalysis::class);
    }
}
