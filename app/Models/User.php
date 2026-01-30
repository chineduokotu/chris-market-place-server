<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'current_role',
        'phone',
        'whatsapp_number',
    ];

    protected $appends = ['whatsapp_link'];

    protected $attributes = [
        'current_role' => 'seeker',
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
            'password' => 'hashed',
        ];
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function bookingsAsSeeker()
    {
        return $this->hasMany(Booking::class, 'seeker_id');
    }

    public function bookingsAsProvider()
    {
        return $this->hasMany(Booking::class, 'provider_id');
    }

    public function getWhatsappLinkAttribute()
    {
        if (!$this->whatsapp_number) {
            return null;
        }
        // Remove non-numeric characters for the link
        $cleanNumber = preg_replace('/[^0-9]/', '', $this->whatsapp_number);
        return "https://wa.me/{$cleanNumber}";
    }
}
