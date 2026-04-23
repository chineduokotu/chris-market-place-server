<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    public const STATUS_VISIBLE = 'visible';

    public const STATUS_HIDDEN = 'hidden';

    public const STATUS_FLAGGED = 'flagged';

    protected $fillable = [
        'booking_id',
        'service_id',
        'provider_id',
        'seeker_id',
        'rating',
        'comment',
        'status',
        'moderation_note',
    ];

    protected $attributes = [
        'status' => self::STATUS_VISIBLE,
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function seeker()
    {
        return $this->belongsTo(User::class, 'seeker_id');
    }
}
