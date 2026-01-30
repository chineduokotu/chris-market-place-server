<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'user_one_id',
        'user_two_id',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    /**
     * Get the first participant.
     */
    public function userOne()
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    /**
     * Get the second participant.
     */
    public function userTwo()
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    /**
     * Get the associated booking (if any).
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get all messages in this conversation.
     */
    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get the latest message.
     */
    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * Scope to get conversations for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_one_id', $userId)
            ->orWhere('user_two_id', $userId);
    }

    /**
     * Get the other participant in the conversation.
     */
    public function getOtherUser($userId)
    {
        return $this->user_one_id === $userId ? $this->userTwo : $this->userOne;
    }

    /**
     * Get unread messages count for a user.
     */
    public function unreadCountFor($userId)
    {
        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Find or create a conversation between two users.
     */
    public static function findOrCreateBetween($userOneId, $userTwoId, $bookingId = null)
    {
        // Always store smaller ID first for consistency
        $ids = [$userOneId, $userTwoId];
        sort($ids);

        return static::firstOrCreate(
            [
                'user_one_id' => $ids[0],
                'user_two_id' => $ids[1],
            ],
            [
                'booking_id' => $bookingId,
            ]
        );
    }
}
