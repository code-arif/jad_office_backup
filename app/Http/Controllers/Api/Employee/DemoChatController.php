<?php

namespace App\Http\Controllers;

use App\Events\MessageSendEvent;
use App\Models\Chat;
use App\Models\Room;
use App\Models\User;
use App\Models\ChatMessage;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    // Fetch all rooms for the authenticated user
    public function getRooms(Request $request)
    {
        $user = $request->user();

        $rooms = Room::where('user1_id', $user->id)
            ->orWhere('user2_id', $user->id)
            ->with(['user1' => function ($query) {
                $query->select('id', 'role')
                      ->with(['employee' => function ($q) {
                          $q->select('user_id', 'name');
                      }, 'company' => function ($q) {
                          $q->select('user_id', 'company_name');
                      }]);
            }, 'user2' => function ($query) {
                $query->select('id', 'role')
                      ->with(['employee' => function ($q) {
                          $q->select('user_id', 'name');
                      }, 'company' => function ($q) {
                          $q->select('user_id', 'company_name');
                      }]);
            }])
            ->get();

        return response()->json([
            'rooms' => $rooms->map(function ($room) use ($user) {
                $otherUser = $room->user1_id == $user->id ? $room->user2 : $room->user1;
                return [
                    'room_id' => $room->id,
                    'other_user' => [
                        'id' => $otherUser->id,
                        'role' => $otherUser->role,
                        'name' => $otherUser->role === 'employee'
                            ? $otherUser->employee->name
                            : $otherUser->company->company_name,
                    ],
                ];
            }),
        ]);
    }

    // Fetch chat history and recipient info for a specific room
    public function getChat(Request $request, $roomId)
    {
        $user = $request->user();
        $room = Room::where('id', $roomId)
            ->where(function ($query) use ($user) {
                $query->where('user1_id', $user->id)
                      ->orWhere('user2_id', $user->id);
            })
            ->firstOrFail();

        $otherUser = $room->user1_id == $user->id ? $room->user2()->first() : $room->user1()->first();

        $receiverInfo = [
            'id' => $otherUser->id,
            'role' => $otherUser->role,
            'name' => $otherUser->role === 'employee'
                ? $otherUser->employee->name
                : $otherUser->company->company_name,
            'details' => $otherUser->role === 'employee' ? [
                'phone' => $otherUser->employee->phone,
                'skills' => $otherUser->employee->skills,
            ] : [
                'address' => $otherUser->company->address,
                'industry' => $otherUser->company->industry,
            ],
        ];

        $messages = Chat::where('room_id', $roomId)
            ->with(['sender' => function ($query) {
                $query->select('id', 'role')
                      ->with(['employee' => function ($q) {
                          $q->select('user_id', 'name');
                      }, 'company' => function ($q) {
                          $q->select('user_id', 'company_name');
                      }]);
            }])
            ->orderBy('sent_at', 'asc')
            ->get();

        return response()->json([
            'receiver' => $receiverInfo,
            'messages' => $messages->map(function ($message) {
                return [
                    'id' => $message->id,
                    'sender_id' => $message->sender_id,
                    'sender_name' => $message->sender->role === 'employee'
                        ? $message->sender->employee->name
                        : $message->sender->company->company_name,
                    'message' => $message->message,
                    'sent_at' => $message->sent_at,
                ];
            }),
        ]);
    }

    // Send a message to a room
    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string',
        ]);

        $user = $request->user();
        $receiverId = $request->receiver_id;

        // Ensure sender and receiver roles are different
        $receiver = User::findOrFail($receiverId);
        if ($user->role === $receiver->role) {
            return response()->json(['message' => 'Cannot chat with same role'], 422);
        }

        // Find or create a room
        $room = Room::where(function ($query) use ($user, $receiverId) {
            $query->where('user1_id', $user->id)->where('user2_id', $receiverId);
        })->orWhere(function ($query) use ($user, $receiverId) {
            $query->where('user1_id', $receiverId)->where('user2_id', $user->id);
        })->first();

        if (!$room) {
            $room = Room::create([
                'user1_id' => min($user->id, $receiverId),
                'user2_id' => max($user->id, $receiverId),
            ]);
        }

        $message = Chat::create([
            'room_id' => $room->id,
            'sender_id' => $user->id,
            'receiver_id' => $receiverId,
            'message' => $request->message,
            'sent_at' => now(),
        ]);

        event(new MessageSendEvent($message));

        return response()->json([
            'message' => 'Message sent successfully',
            'data' => [
                'room_id' => $room->id,
                'message' => [
                    'id' => $message->id,
                    'sender_id' => $message->sender_id,
                    'sender_name' => $user->role === 'employee'
                        ? $user->employee->name
                        : $user->company->company_name,
                    'message' => $message->message,
                    'sent_at' => $message->sent_at,
                ],
            ],
        ], 201);
    }
}
