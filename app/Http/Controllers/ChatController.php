<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Events\MessageSent;
use App\Events\MessageRead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    /**
     * Get all conversations for the authenticated user.
     */
    public function index(Request $request)
    {
        $userId = Auth::id();

        $conversations = Conversation::forUser($userId)
            ->with(['userOne', 'userTwo', 'latestMessage.sender'])
            ->withCount([
                'messages as unread_count' => function ($query) use ($userId) {
                    $query->where('sender_id', '!=', $userId)->whereNull('read_at');
                }
            ])
            ->orderByDesc('last_message_at')
            ->get()
            ->map(function ($conversation) use ($userId) {
                $otherUser = $conversation->getOtherUser($userId);
                return [
                    'id' => $conversation->id,
                    'booking_id' => $conversation->booking_id,
                    'other_user' => [
                        'id' => $otherUser->id,
                        'name' => $otherUser->name,
                    ],
                    'last_message' => $conversation->latestMessage ? [
                        'body' => $conversation->latestMessage->decrypted_body,
                        'sender_id' => $conversation->latestMessage->sender_id,
                        'created_at' => $conversation->latestMessage->created_at,
                    ] : null,
                    'unread_count' => $conversation->unread_count,
                    'last_message_at' => $conversation->last_message_at,
                ];
            });

        return response()->json($conversations);
    }

    /**
     * Get messages for a specific conversation.
     */
    public function show(Request $request, $id)
    {
        $userId = Auth::id();

        $conversation = Conversation::forUser($userId)
            ->with(['userOne', 'userTwo'])
            ->findOrFail($id);

        // Mark messages as read
        $conversation->messages()
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = $conversation->messages()
            ->with('sender:id,name')
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'body' => $message->decrypted_body,
                    'type' => $message->type,
                    'attachment_url' => $message->attachment_url,
                    'sender' => [
                        'id' => $message->sender->id,
                        'name' => $message->sender->name,
                    ],
                    'read_at' => $message->read_at,
                    'created_at' => $message->created_at,
                ];
            });

        $otherUser = $conversation->getOtherUser($userId);

        return response()->json([
            'id' => $conversation->id,
            'booking_id' => $conversation->booking_id,
            'other_user' => [
                'id' => $otherUser->id,
                'name' => $otherUser->name,
            ],
            'messages' => $messages,
        ]);
    }

    /**
     * Start a new conversation or return existing one.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'booking_id' => 'nullable|exists:bookings,id',
        ]);

        $currentUserId = Auth::id();
        $otherUserId = $request->user_id;

        // Cannot start conversation with yourself
        if ($currentUserId === $otherUserId) {
            return response()->json(['error' => 'Cannot start conversation with yourself'], 400);
        }

        $conversation = Conversation::findOrCreateBetween(
            $currentUserId,
            $otherUserId,
            $request->booking_id
        );

        $otherUser = $conversation->getOtherUser($currentUserId);

        return response()->json([
            'id' => $conversation->id,
            'other_user' => [
                'id' => $otherUser->id,
                'name' => $otherUser->name,
            ],
        ], 201);
    }

    /**
     * Send a message in a conversation.
     */
    public function sendMessage(Request $request, $conversationId)
    {
        $request->validate([
            'body' => 'required|string|max:5000',
            'type' => 'nullable|in:text,image,file',
            'attachment_url' => 'nullable|url',
        ]);

        $userId = Auth::id();
        $conversation = Conversation::forUser($userId)->findOrFail($conversationId);

        $message = $conversation->messages()->create([
            'sender_id' => $userId,
            'body' => $request->body,
            'type' => $request->type ?? 'text',
            'attachment_url' => $request->attachment_url,
        ]);

        // Update conversation timestamp
        $conversation->update(['last_message_at' => now()]);

        // Load sender relationship
        $message->load('sender:id,name');

        // Broadcast the message
        broadcast(new MessageSent($message, $conversation))->toOthers();

        return response()->json([
            'id' => $message->id,
            'body' => $message->decrypted_body,
            'type' => $message->type,
            'attachment_url' => $message->attachment_url,
            'sender' => [
                'id' => $message->sender->id,
                'name' => $message->sender->name,
            ],
            'read_at' => $message->read_at,
            'created_at' => $message->created_at,
        ], 201);
    }

    /**
     * Mark a message as read.
     */
    public function markRead(Request $request, $messageId)
    {
        $userId = Auth::id();

        $message = Message::whereHas('conversation', function ($query) use ($userId) {
            $query->forUser($userId);
        })
            ->where('sender_id', '!=', $userId)
            ->findOrFail($messageId);

        $message->markAsRead();

        // Broadcast read receipt
        broadcast(new MessageRead($message))->toOthers();

        return response()->json(['success' => true]);
    }
}
