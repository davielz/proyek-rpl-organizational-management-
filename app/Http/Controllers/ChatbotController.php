<?php

namespace App\Http\Controllers;

use App\Services\Ai\NvidiaChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    /**
     * Handle incoming chatbot message from dashboard.
     */
    public function send(Request $request, NvidiaChatService $chatService): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'history' => ['nullable', 'array', 'max:10'],
            'history.*.role' => ['required_with:history', 'string', 'in:user,assistant'],
            'history.*.content' => ['required_with:history', 'string', 'max:2000'],
        ]);

        $user = $request->user();
        $userName = $user ? $user->name : 'Pengguna';
        $userRole = $user ? ucfirst($user->role) : 'Anggota';

        $result = $chatService->chat(
            message: $validated['message'],
            history: $validated['history'] ?? [],
            userName: $userName,
            userRole: $userRole
        );

        return response()->json([
            'success' => $result['success'],
            'reply' => $result['reply'],
            'error' => $result['error'],
        ]);
    }
}
