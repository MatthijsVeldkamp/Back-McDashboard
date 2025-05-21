<?php

namespace App\Http\Controllers;

use App\Models\ErrorMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ErrorMessageController extends Controller
{
    /**
     * Store a newly created error message in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'location' => 'required|string',
            'context' => 'nullable|array',
        ]);

        $errorMessage = ErrorMessage::create([
            'user_id' => Auth::id(),
            'message' => $validated['message'],
            'location' => $validated['location'],
            'context' => $validated['context'] ?? [],
        ]);

        return response()->json($errorMessage, 201);
    }

    /**
     * Display a listing of error messages.
     */
    public function index()
    {
        $errorMessages = ErrorMessage::with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($errorMessages);
    }

    /**
     * Display the specified error message.
     */
    public function show(ErrorMessage $errorMessage)
    {
        return response()->json($errorMessage->load('user'));
    }

    /**
     * Remove the specified error message from storage.
     */
    public function destroy(ErrorMessage $errorMessage)
    {
        $errorMessage->delete();
        return response()->json(null, 204);
    }

    /**
     * Get all error messages for a specific user.
     */
    public function userNotifications($userId)
    {
        $errorMessages = ErrorMessage::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($errorMessages);
    }

    /**
     * Clear all error messages for a specific user.
     */
    public function clearNotifications($userId)
    {
        ErrorMessage::where('user_id', $userId)->delete();
        return response()->json(['message' => 'Notifications cleared successfully']);
    }
} 