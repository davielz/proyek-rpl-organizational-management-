<?php

namespace App\Http\Controllers;

use App\Models\AiChatMessage;
use App\Services\Ai\NvidiaChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    /**
     * Get chat history for the authenticated user.
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        $messages = AiChatMessage::where('user_id', $user->id)
            ->latest('created_at')
            ->take(50)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($m) => [
                'id' => $m->id,
                'role' => $m->role,
                'content' => $m->content,
                'created_at' => $m->created_at->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'messages' => $messages,
        ]);
    }

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

        // 1. Persist user message to the database under their own account
        if ($user) {
            AiChatMessage::create([
                'user_id' => $user->id,
                'role' => 'user',
                'content' => $validated['message'],
            ]);
        }

        // 2. Build history strictly from this user's database messages or validated payload
        $history = [];
        if ($user) {
            $history = AiChatMessage::where('user_id', $user->id)
                ->latest('created_at')
                ->skip(1) // skip the newly created user message
                ->take(6)
                ->get()
                ->reverse()
                ->values()
                ->map(fn ($m) => [
                    'role' => $m->role,
                    'content' => $m->content,
                ])
                ->toArray();
        }

        if (empty($history) && ! empty($validated['history'])) {
            $history = $validated['history'];
        }

        // 3. Request AI Assistant response
        $result = $chatService->chat(
            message: $validated['message'],
            history: $history,
            userName: $userName,
            userRole: $userRole,
            user: $user
        );

        // 4. Persist AI Assistant response to the database under this user's account
        if ($user && ! empty($result['reply'])) {
            AiChatMessage::create([
                'user_id' => $user->id,
                'role' => 'assistant',
                'content' => $result['reply'],
            ]);
        }

        return response()->json([
            'success' => $result['success'],
            'reply' => $result['reply'],
            'error' => $result['error'] ?? null,
        ]);
    }

    /**
     * Clear all chat history for the authenticated user.
     */
    public function clear(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            AiChatMessage::where('user_id', $user->id)->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Riwayat percakapan AI Assistant berhasil dibersihkan.',
        ]);
    }
}
