<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_HIDDEN = 'hidden';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = ['user_id', 'category_id', 'title', 'description', 'location', 'price', 'status', 'moderation_note', 'image', 'image_public_id'];

    protected $attributes = [
        'status' => self::STATUS_APPROVED,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
