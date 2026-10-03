<?php

namespace App\Services\Ai;

use App\Models\Event;
use App\Services\Ai\Prompts\DocumentPromptBuilder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class LlmAiService
{
    public function __construct(
        protected DocumentPromptBuilder $promptBuilder
    ) {}

    /**
     * Call LLM API to generate structured draft document.
     *
     * @return array{
     *     success: bool,
     *     data: array|null,
     *     error: string|null
     * }
     */
    public function requestDraftGeneration(Event $event, string $type): array
    {
        $apiKey = config('ai.api_key');
        $baseUrl = rtrim(config('ai.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $model = config('ai.model', 'gemini-1.5-flash');
        $timeout = config('ai.timeout', 15);
        $retryTimes = config('ai.retry_times', 2);
        $retrySleep = config('ai.retry_sleep_ms', 500);

        if (empty($apiKey)) {
            Log::info('[AI Integration] API Key tidak dikonfigurasi. Mengalihkan ke Fallback Generator.');

            return [
                'success' => false,
                'data' => null,
                'error' => 'API key tidak dikonfigurasi dalam .env',
            ];
        }

        $prompt = $this->promptBuilder->build($event, $type);

        // Safe logging: log request attempt without revealing API Key
        Log::info('[AI Integration] Mengirim permintaan ke LLM API', [
            'provider' => config('ai.provider'),
            'model' => $model,
            'event_id' => $event->id,
            'document_type' => $type,
            'timeout_seconds' => $timeout,
        ]);

        $provider = config('ai.provider', 'nvidia');

        try {
            if ($provider === 'nvidia') {
                $endpointUrl = "{$baseUrl}/chat/completions";

                $response = Http::timeout($timeout)
                    ->retry($retryTimes, $retrySleep, function (Throwable $exception) {
                        Log::warning('[AI Integration] Retrying HTTP request due to exception: '.$exception->getMessage());
                        return true;
                    })
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->post($endpointUrl, [
                        'model' => $model,
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => 'Anda adalah asisten AI penyusun proposal/LPJ kampus. HANYA berikan respon dalam format JSON murni tanpa karakter tambahan di luar JSON.'
                            ],
                            [
                                'role' => 'user',
                                'content' => $prompt
                            ]
                        ],
                        'temperature' => 0.7,
                    ]);

                if (! $response->successful()) {
                    Log::error('[AI Integration] LLM API merespon dengan status error', [
                        'status_code' => $response->status(),
                        'error_body' => $response->json() ?? $response->body(),
                    ]);

                    return [
                        'success' => false,
                        'data' => null,
                        'error' => "LLM API Error (HTTP {$response->status()})",
                    ];
                }

                $responseData = $response->json();
                $rawText = $responseData['choices'][0]['message']['content'] ?? null;
            } else {
                $endpointUrl = "{$baseUrl}/models/{$model}:generateContent?key={$apiKey}";

                $response = Http::timeout($timeout)
                    ->retry($retryTimes, $retrySleep, function (Throwable $exception) {
                        Log::warning('[AI Integration] Retrying HTTP request due to exception: '.$exception->getMessage());
                        return true;
                    })
                    ->post($endpointUrl, [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt],
                                ],
                            ],
                        ],
                        'generationConfig' => [
                            'responseMimeType' => 'application/json',
                            'temperature' => 0.7,
                        ],
                    ]);

                if (! $response->successful()) {
                    Log::error('[AI Integration] LLM API merespon dengan status error', [
                        'status_code' => $response->status(),
                        'error_body' => $response->json() ?? $response->body(),
                    ]);

                    return [
                        'success' => false,
                        'data' => null,
                        'error' => "LLM API Error (HTTP {$response->status()})",
                    ];
                }

                $responseData = $response->json();
                $rawText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? null;
            }

            if (empty($rawText)) {
                Log::warning('[AI Integration] Respon LLM API kosong / tidak valid', [
                    'event_id' => $event->id,
                ]);

                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Respon LLM API kosong',
                ];
            }

            $parsedData = $this->cleanAndParseJson($rawText);

            if (! $parsedData || ! $this->validateDocumentStructure($parsedData)) {
                Log::warning('[AI Integration] Format JSON dari AI tidak sesuai struktur yang diharapkan', [
                    'raw_text' => $rawText,
                ]);

                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'Format JSON dari AI tidak valid atau tidak lengkap',
                ];
            }

            Log::info('[AI Integration] Berhasil menghasilkan draft AI', [
                'event_id' => $event->id,
                'document_type' => $type,
            ]);

            return [
                'success' => true,
                'data' => $parsedData,
                'error' => null,
            ];

        } catch (ConnectionException $e) {
            Log::error('[AI Integration] Timeout atau masalah koneksi jaringan HTTP', [
                'error_message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'data' => null,
                'error' => 'Koneksi ke AI API mengalami timeout atau gangguan jaringan.',
            ];
        } catch (Throwable $e) {
            Log::error('[AI Integration] Terjadi kesalahan saat memproses permintaan AI', [
                'error_message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'data' => null,
                'error' => 'Gagal menghubungi layanan AI: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Remove markdown formatting (```json ... ```) if present and parse JSON string.
     */
    protected function cleanAndParseJson(string $rawText): ?array
    {
        $cleanText = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($rawText));
        $decoded = json_decode($cleanText, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Validate that the array contains all required keys.
     */
    protected function validateDocumentStructure(array $data): bool
    {
        $requiredKeys = ['title', 'background', 'objectives', 'budget_plan', 'summary_content'];

        foreach ($requiredKeys as $key) {
            if (! array_key_exists($key, $data) || empty($data[$key])) {
                return false;
            }
        }

        return true;
    }
}
