<?php
namespace App\Http\Controllers\Api\Employee;

use App\Events\MessageSendEvent;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Room;
use App\Models\User;
use App\Traits\ApiResponse;
use function Laravel\Prompts\error;
use GrahamCampbell\ResultType\Success;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChatSystemController extends Controller
{
    use ApiResponse;

    public function get_rooms()
    {
        $user = auth('api')->user();

        $rooms = Room::where('first_user_id', $user->id)
            ->orWhere('second_user_id', $user->id)
            ->with(['first_user' => function ($query) {
                $query->select('id', 'role', 'name', 'avatar')
                    ->with(['company' => function ($q) {
                        $q->select('user_id', 'name', 'image_url');
                    }]);
            }, 'second_user' => function ($query) {
                $query->select('id', 'role', 'name', 'avatar')
                    ->with(['company' => function ($q) {
                        $q->select('user_id', 'name', 'image_url');
                    }]);

            }, 'chats' => function ($query) {
                $query->select('id', 'room_id', 'sender_id', 'text', 'created_at')
                    ->orderBy('created_at', 'desc')
                    ->limit(1); // Get the last message
            }])
            ->get();

        $data = $rooms->map(function ($room) use ($user) {

            $otherUser = $room->first_user_id == $user->id ? $room->second_user : $room->first_user;

            $last_message = $room->chats->first();

            return [
                'room_id'           => $room->id,
                'other_user'        => [
                    'id'        => $otherUser->id,
                    'role'      => $otherUser->role,
                    'name'      => $otherUser->role === 'employee'
                    ? $otherUser->name           // employye name from users table
                    : $otherUser->company->name, // company name from companies table

                    'image_url' => $otherUser->role === 'employee' ? $otherUser->avatar : $otherUser->company->image_url,
                ],
                'last_message'      => $last_message ? $last_message->text : null,
                'last_message_time' => $last_message ? $last_message->humanize_date : null,
                'message_count'     => Chat::where('room_id', $room->id)->count(),
            ];
        });

        return $this->success($data, 'Room list retrive successfully', 200);

    }

    public function get_room_meesage($room_id)
    {
        $user = auth('api')->user();

        // Check if the user is part of the room
        $room = Room::where('id', $room_id)
            ->where(function ($query) use ($user) {
                $query->where('first_user_id', $user->id)
                    ->orWhere('second_user_id', $user->id);
            })
            ->with(['first_user' => function ($query) {
                $query->select('id', 'role', 'name', 'avatar')
                    ->with(['company' => function ($q) {
                        $q->select('user_id', 'name', 'image_url');
                    }]);
            }, 'second_user' => function ($query) {
                $query->select('id', 'role', 'name', 'avatar')
                    ->with(['company' => function ($q) {
                        $q->select('user_id', 'name', 'image_url');
                    }]);
            }])
            ->firstOrFail();

        // Determine the other user
        $otherUser = $room->first_user_id == $user->id ? $room->second_user : $room->first_user;

        // Prepare receiver info
        $receiverInfo = [
            'id'        => $otherUser->id,
            'role'      => $otherUser->role,
            'name'      => $otherUser->role === 'employee' ? $otherUser->name : $otherUser->company->name,
            'image_url' => $otherUser->role === 'employee' ? $otherUser->avatar : $otherUser->company->image_url,
        ];

        // Fetch messages
        $messages = Chat::where('room_id', $room_id)
            ->with(['sender' => function ($query) {

                $query->select('id', 'role', 'name')
                    ->with(['company' => function ($q) {
                        $q->select('user_id', 'name');
                    }]);
            }])
            ->orderBy('created_at', 'asc')
            ->get();

        $data = [
            'receiver' => $receiverInfo,
            'messages' => $messages->map(function ($message) use ($otherUser, $user) {
                return [
                    'id'           => $message->id,
                    'sender_id'    => $message->sender_id,
                    'receiver_id'  => $user->id === $message->sender_id ? $otherUser->id : $user->id,
                    'receiver'     => [
                        'id'        => $user->id === $message->sender_id ? $otherUser->id : $user->id,
                        'role'      => $user->id === $message->sender_id ? $otherUser->role : $user->role,
                        'name'      => $user->id === $message->sender_id ? ($otherUser->role === 'employee' ? $otherUser->name : $otherUser->company->name) : ($user->role === 'employee' ? $user->name : $user->company->name),
                        'image_url' => $user->id === $message->sender_id ? ($otherUser->role === 'employee' ? $otherUser->avatar : $otherUser->company->image_url) : ($user->role === 'employee' ? $user->avatar : $user->company->image_url),
                    ],
                    'sender_name'  => $user->id === $message->sender_id ? ($user->role === 'employee' ? $user->name : $user->company->name) : ($otherUser->role === 'employee' ? $otherUser->name : $otherUser->company->name),
                    'message'      => $message->text,
                    'sender_image' => $user->id === $message->sender_id ? ($user->role === 'employee' ? $user->avatar : $user->company->image_url) : ($otherUser->role === 'employee' ? $otherUser->avatar : $otherUser->company->image_url),
                    'sent_at'      => $message->humanize_date,
                ];
            }),
        ];

        return $this->success($data, 'Room message retrive successfully', 200);

    }

    public function send_message(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|exists:users,id',
            'message'     => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors()->first(), 404);
        }

        // auth user as sender
        $user        = auth('api')->user();
        $sender_id   = $user->id;
        $receiver_id = $request->receiver_id;

        // check valid user for chat
        if ($sender_id == $receiver_id) {
            return $this->error([], 'You cannot send a message to yourself', 400);
        }

        // Check if receiver user exists (already handled in validation, but keeping for clarity)
        $receiver = User::find($receiver_id);
        if (! $receiver) {
            return $this->error([], 'Receiver not found.', 404);
        }

        // check room if sender and receiver exits or not
        $room = Room::where(function ($query) use ($sender_id, $receiver_id) {
            $query->where('first_user_id', $sender_id)->where('second_user_id', $receiver_id);
        })
            ->orWhere(function ($query) use ($sender_id, $receiver_id) {
                $query->where('first_user_id', $receiver_id)->where('second_user_id', $sender_id);

            })
            ->first();

        // create room for sender and receiver
        if (! $room) {
            $room = Room::create([
                'first_user_id'  => $sender_id,
                'second_user_id' => $receiver_id,
            ]);
        }

        // create chat for sender and receiver

        $chat = Chat::create([

            'sender_id'   => $sender_id,
            'receiver_id' => $receiver_id,
            'room_id'     => $room->id,
            'text'        => $request->message,
            'status'      => 'sent',

        ]);

        // load sender and receiver details
        $chat->load(['sender' => function ($query) {
            $query->select('id', 'role', 'name', 'avatar')
                ->with(['company' => function ($q) {
                    $q->select('user_id', 'name', 'image_url');
                }]);
        }, 'receiver' => function ($query) {
            $query->select('id', 'role', 'name', 'avatar')
                ->with(['company' => function ($q) {
                    $q->select('user_id', 'name', 'image_url');
                }]);
        }]);

        broadcast(new MessageSendEvent($chat))->toOthers();

        $data = [

            'room_id' => $room->id,
            'message' => [
                'id'          => $chat->id,
                'sender_id'   => $sender_id,
                'sender_name' => $user->role == 'employee' ? $user->name : $user->company->name,
                'message'     => $chat->text,
                'sent_at'     => $chat->humanize_date,
            ],

        ];

        return $this->success($data, 'Message send successfully', 202);

    }

    public function get_conversation($receiver_id)
    {
        $user = auth('api')->user();

        // Check if the receiver is a valid company
        $receiver = User::find($receiver_id);
        if (! $receiver) {
            return $this->error([], 'Receiver not found.', 404);
        }

        // If no conversation exists, create a room
        $room = Room::where(function ($query) use ($user, $receiver) {
                        $query->where('first_user_id', $user->id)->where('second_user_id', $receiver->id);
                    })
                    ->orWhere(function ($query) use ($user, $receiver) {
                        $query->where('first_user_id', $receiver->id)->where('second_user_id', $user->id);
                    })
                    ->first();

        // If no room found, create one for the employee-company conversation
        if (! $room) {
            $room = Room::create([
                'first_user_id'  => $user->id,
                'second_user_id' => $receiver->id,
            ]);
        }


        return $this->success($room, 'Conversation fetched successfully', 200);
    }

}
