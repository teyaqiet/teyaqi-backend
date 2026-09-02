<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Friend;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FriendController extends Controller
{
    /**
     * Get list of accepted friends.
     */
    public function index()
    {
        // auth()->user() works if using Sanctum/Passport middleware
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Assumes your User model has a 'friends' relationship/attribute
        $friends = $user->friends; 
        
        return response()->json([
            'success' => true,
            'data' => UserResource::collection($friends)
        ]);
    }

    /**
     * Send a friend request.
     */
    public function sendRequest(Request $request)
    {
        // 1. Validation
        if (!$request->has('friend_id')) {
            return response()->json([
                'success' => false, 
                'message' => 'Target node ID (friend_id) is missing.',
            ], 422);
        }

        $receiverId = $request->friend_id;
        $senderId = auth()->id();

        // 2. Prevent self-linking
        if ($senderId == $receiverId) {
            return response()->json(['success' => false, 'message' => 'You cannot link to your own node.'], 400);
        }

        // 3. Verify target exists
        if (!User::where('id', $receiverId)->exists()) {
            return response()->json(['success' => false, 'message' => 'User not found in the matrix.'], 404);
        }

        // 4. Check for existing link (Bidirectional check)
        $alreadyExists = Friend::where(function($q) use ($senderId, $receiverId) {
            $q->where('user_id', $senderId)->where('friend_id', $receiverId);
        })->orWhere(function($q) use ($senderId, $receiverId) {
            $q->where('user_id', $receiverId)->where('friend_id', $senderId);
        })->exists();

        if ($alreadyExists) {
            return response()->json([
                'success' => false, 
                'message' => 'Connection already exists or is pending.'
            ], 400);
        }

        // 5. Create Request
        Friend::create([
            'user_id' => $senderId,
            'friend_id' => $receiverId,
            'status' => 'pending'
        ]);

        return response()->json(['success' => true, 'message' => 'Friend request sent!']);
    }

    /**
     * Accept a friend request.
     */
    public function acceptRequest(Request $request)
    {
        // sender_id here is the ID of the person who SENT the request
        $request->validate(['sender_id' => 'required|exists:users,id']);

        $friendship = Friend::where('user_id', $request->sender_id)
            ->where('friend_id', auth()->id())
            ->where('status', 'pending')
            ->first();

        if (!$friendship) {
            return response()->json(['success' => false, 'message' => 'Request not found.'], 404);
        }

        $friendship->update(['status' => 'accepted']);

        return response()->json(['success' => true, 'message' => 'Node connection established.']);
    }

    /**
     * Remove a friend or cancel a request.
     */
    public function removeFriend($id)
    {
        $authId = auth()->id();

        // Delete any relationship between these two users
        $deleted = Friend::where(function($q) use ($authId, $id) {
            $q->where('user_id', $authId)->where('friend_id', $id);
        })->orWhere(function($q) use ($authId, $id) {
            $q->where('user_id', $id)->where('friend_id', $authId);
        })->delete();

        if ($deleted) {
            return response()->json(['success' => true, 'message' => 'Connection severed.']);
        }

        return response()->json(['success' => false, 'message' => 'No active connection found.'], 404);
    }

    /**
     * Get list of pending inbound requests.
     */
    public function pendingRequests()
{
    $user = auth()->user();

    // OUTGOING: Map to UserResource and inject the friendship record ID
    $outgoing = \App\Models\Friend::where('user_id', $user->id)
    ->where('status', 'pending')
    ->with('receiver') 
    ->get()
    ->map(function ($relationship) {
        $u = $relationship->receiver;
        if ($u) {
            // Attach the ID of the 'friends' table row
            $u->friendship_id = $relationship->id; 
        }
        return $u;
    })->filter();

    // INCOMING: Same logic
    $incoming = \App\Models\Friend::where('friend_id', $user->id)
        ->where('status', 'pending')
        ->with('sender')
        ->get()
        ->map(function ($relationship) {
            $user = $relationship->sender;
            if (!$user) return null;
            $user->friendship_id = $relationship->id;
            return $user;
        })
        ->filter();

    return response()->json([
        'success' => true,
        'data' => [
            'incoming' => UserResource::collection($incoming),
            'outgoing' => UserResource::collection($outgoing),
        ]
    ]);
}

    /**
     * Search for users to add.
     */
    public function searchUsers(Request $request)
    {
        $query = $request->query('username');

        if (!$query || strlen($query) < 2) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $users = User::where('username', 'LIKE', "%{$query}%")
            ->where('id', '!=', auth()->id()) 
            ->select('id', 'username', 'name', 'total_xp') 
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    // app/Http/Controllers/FriendController.php

public function deleteRequest($id)
{
    $userId = auth()->id();

    // Use 'Friend' model instead of 'FriendRequest'
    // Use 'user_id' and 'friend_id' to match your existing schema
    $request = \App\Models\Friend::where('id', $id)
        ->where(function ($query) use ($userId) {
            $query->where('user_id', $userId)      // User is the one who sent it
                  ->orWhere('friend_id', $userId); // User is the one who received it
        })
        ->first();

    if (!$request) {
        return response()->json([
            'success' => false, 
            'message' => 'Request not found or unauthorized'
        ], 404);
    }

    $request->delete();

    return response()->json([
        'success' => true, 
        'message' => 'Request purged from matrix'
    ]);
}

}