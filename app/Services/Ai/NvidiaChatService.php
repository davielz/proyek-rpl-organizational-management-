<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class NvidiaChatService
{
    /**
     * Send user message to NVIDIA AI API (OpenAI compatible endpoint).
     *
     * @param string $message
     * @param array $history Array of previous messages [['role' => 'user'|'assistant', 'content' => string]]
     * @param string $userName
     * @param string $userRole
     * @return array{success: bool, reply: string, error: string|null}
     */
    public function chat(string $message, array $history = [], string $userName = 'Pengguna', string $userRole = 'Anggota'): array
    {
        $apiKey = config('ai.api_key') ?: env('NVIDIA_API_KEY');
        $baseUrl = rtrim(config('ai.base_url', 'https://integrate.api.nvidia.com/v1'), '/');
        $model = config('ai.model', 'meta/llama-3.2-11b-vision-instruct');
        $timeout = config('ai.timeout', 15);

        if (empty($apiKey)) {
            Log::warning('[NVIDIA AI Chat] API Key tidak dikonfigurasi dalam .env');
            return [
                'success' => false,
                'reply' => 'Maaf, API Key NVIDIA AI belum dikonfigurasi pada server.',
                'error' => 'API Key missing',
            ];
        }

        $systemPrompt = "Anda adalah 'Asisten AI Kampus' pintar dan ramah pada Sistem Pengelolaan Organisasi Mahasiswa Kampus.
Pengguna yang sedang berkonsultasi bernama {$userName} dengan peranan: {$userRole}.

Tugas Anda:
1. Membantu pengguna seputar pengelolaan kegiatan mahasiswa, penyusunan proposal & LPJ, pendaftaran kegiatan, dan analisis ulasan sentimen.
2. Memberikan jawaban yang informatif, solutif, ringkas, dan profesional dalam Bahasa Indonesia.
3. Gunakan format Markdown yang rapi (bold, bullet points, atau numbering jika diperlukan).
4. Jika ditanya tentang cara buat proposal, terangkan bahwa sistem memiliki fitur '✨ Generate Draf dengan AI' saat membuat proposal baru.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        // Append recent conversation history (max 6 messages to keep context concise)
        $trimmedHistory = array_slice($history, -6);
        foreach ($trimmedHistory as $item) {
            if (isset($item['role'], $item['content']) && in_array($item['role'], ['user', 'assistant'])) {
                $messages[] = [
                    'role' => $item['role'],
                    'content' => (string) $item['content'],
                ];
            }
        }

        // Append current user message
        $messages[] = [
            'role' => 'user',
            'content' => $message,
        ];

        Log::info('[NVIDIA AI Chat] Sending chat request', [
            'model' => $model,
            'user' => $userName,
            'role' => $userRole,
        ]);

        try {
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$baseUrl}/chat/completions", [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'max_tokens' => 800,
                ]);

            if (!$response->successful()) {
                Log::error('[NVIDIA AI Chat] HTTP Error response', [
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                ]);

                return [
                    'success' => false,
                    'reply' => 'Maaf, layanan NVIDIA AI sedang mengalami kendala teknis. Silakan coba beberapa saat lagi.',
                    'error' => "HTTP {$response->status()}",
                ];
            }

            $json = $response->json();
            $reply = $json['choices'][0]['message']['content'] ?? null;

            if (empty($reply)) {
                return [
                    'success' => false,
                    'reply' => 'Maaf, AI tidak memberikan respon.',
                    'error' => 'Empty AI reply',
                ];
            }

            return [
                'success' => true,
                'reply' => trim($reply),
                'error' => null,
            ];

        } catch (ConnectionException $e) {
            Log::error('[NVIDIA AI Chat] Connection timeout', ['message' => $e->getMessage()]);

            return [
                'success' => false,
                'reply' => 'Maaf, koneksi ke layanan NVIDIA AI mengalami timeout. Silakan periksa jaringan Anda.',
                'error' => 'Timeout',
            ];
        } catch (Throwable $e) {
            Log::error('[NVIDIA AI Chat] Exception error', ['message' => $e->getMessage()]);

            return [
                'success' => false,
                'reply' => 'Terjadi kesalahan sistem saat memproses pesan Anda.',
                'error' => $e->getMessage(),
            ];
        }
    }
}
