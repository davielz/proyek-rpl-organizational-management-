<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SentimentAnalysisService
{
    /**
     * Analyze sentiment and extract topics from a feedback comment string.
     *
     * @return array{
     *     sentiment: string,
     *     sentiment_score: float,
     *     topics: array<string>,
     *     analysis_status: string
     * }
     */
    public function analyze(string $comment, int $rating = 3): array
    {
        $apiKey = Config::get('ai.api_key');
        $baseUrl = rtrim(Config::get('ai.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $model = Config::get('ai.model', 'gemini-1.5-flash');
        $timeout = Config::get('ai.timeout', 15);
        $isMock = Config::get('ai.mock', false);

        // 1. If mock mode or missing API key, fallback gracefully to rule-based analysis
        if ($isMock || empty($apiKey)) {
            Log::info('[Sentiment Service] Menggunakan Fallback Rule-Based Analyzer (Mock/Missing Key).');

            return $this->fallbackRuleBasedAnalysis($comment, $rating, 'mock_or_unconfigured');
        }

        $prompt = <<<PROMPT
Anda adalah asisten AI analis sentimen profesional untuk ulasan kegiatan mahasiswa kampus.
Analisis komentar berikut dan klasifikasikan sentimennya secara akurat.

KOMENTAR ULASAN:
"{$comment}"

RATING USERS: {$rating} / 5

PETUNJUK OUTPUT:
Respon HARUS berupa JSON murni tanpa markdown formatting (tanpa ```json ... ```) dengan format tepat sebagai berikut:
{
  "sentiment": "positive|neutral|negative",
  "sentiment_score": 0.85,
  "topics": ["topik1", "topik2"]
}

Kriteria Sentimen:
- "positive": jika ulasan menyampaikan kepuasan, pujian, manfaat, atau apreasiasi.
- "negative": jika ulasan menyampaikan kekecewaan, kritik keras, keluhan, atau ketidakpuasan.
- "neutral": jika ulasan bersifat datar, seimbang, atau hanya pertanyaan/saran minor.
- sentiment_score: angka desimal antara 0.00 hingga 1.00 yang menggambarkan tingkat keyakinan sentimen.
- topics: maksimal 3 kata kunci topik utama yang dibahas (contoh: ["fasilitas", "pemateri", "waktu", "konsumsi"]).
PROMPT;

        try {
            $endpointUrl = "{$baseUrl}/models/{$model}:generateContent?key={$apiKey}";

            $response = Http::timeout($timeout)->post($endpointUrl, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'temperature' => 0.2,
                ],
            ]);

            if (! $response->successful()) {
                Log::warning('[Sentiment Service] AI API Error (HTTP '.$response->status().'). Menggunakan fallback analyzer.');

                return $this->fallbackRuleBasedAnalysis($comment, $rating, 'api_error_'.$response->status());
            }

            $responseData = $response->json();
            $rawText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (empty($rawText)) {
                Log::warning('[Sentiment Service] AI API Respon Kosong. Menggunakan fallback analyzer.');

                return $this->fallbackRuleBasedAnalysis($comment, $rating, 'empty_response');
            }

            $cleanText = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($rawText));
            $parsed = json_decode($cleanText, true);

            if (! is_array($parsed) || ! isset($parsed['sentiment'])) {
                Log::warning('[Sentiment Service] Respon JSON AI tidak valid: '.$rawText);

                return $this->fallbackRuleBasedAnalysis($comment, $rating, 'invalid_json');
            }

            $sentiment = in_array(strtolower($parsed['sentiment']), ['positive', 'neutral', 'negative'])
                ? strtolower($parsed['sentiment'])
                : 'neutral';

            $score = isset($parsed['sentiment_score']) ? (float) $parsed['sentiment_score'] : 0.75;
            $topics = isset($parsed['topics']) && is_array($parsed['topics']) ? array_slice($parsed['topics'], 0, 5) : [];

            return [
                'sentiment' => $sentiment,
                'sentiment_score' => round(max(0.00, min(1.00, $score)), 2),
                'topics' => $topics,
                'analysis_status' => 'completed',
            ];

        } catch (ConnectionException $e) {
            Log::warning('[Sentiment Service] Timeout/Gangguan Jaringan HTTP. Menggunakan fallback analyzer.');

            return $this->fallbackRuleBasedAnalysis($comment, $rating, 'timeout');
        } catch (Throwable $e) {
            Log::error('[Sentiment Service] Kesalahan terdeteksi: '.$e->getMessage());

            return $this->fallbackRuleBasedAnalysis($comment, $rating, 'failed');
        }
    }

    /**
     * Fallback rule-based sentiment classifier using keywords and rating.
     */
    protected function fallbackRuleBasedAnalysis(string $comment, int $rating, string $statusReason = 'fallback'): array
    {
        $lowercaseComment = strtolower($comment);

        $positiveKeywords = ['bagus', 'mantap', 'hebat', 'bermanfaat', 'sangat baik', 'senang', 'puas', 'seru', 'suka', 'keren', 'terbaik', 'terima kasih', 'rekomen'];
        $negativeKeywords = ['buruk', 'kecewa', 'jelek', 'kurang', 'gagal', 'parah', 'lambat', 'membosankan', 'rusak', 'tidak suka', 'rugi', 'kecewa'];

        $posHits = 0;
        $negHits = 0;

        foreach ($positiveKeywords as $kw) {
            if (str_contains($lowercaseComment, $kw)) {
                $posHits++;
            }
        }

        foreach ($negativeKeywords as $kw) {
            if (str_contains($lowercaseComment, $kw)) {
                $negHits++;
            }
        }

        if ($posHits > $negHits || $rating >= 4) {
            $sentiment = 'positive';
            $score = 0.80;
        } elseif ($negHits > $posHits || $rating <= 2) {
            $sentiment = 'negative';
            $score = 0.85;
        } else {
            $sentiment = 'neutral';
            $score = 0.60;
        }

        // Basic topic extractor from common words
        $topics = [];
        if (str_contains($lowercaseComment, 'materi') || str_contains($lowercaseComment, 'pemateri') || str_contains($lowercaseComment, 'narasumber')) {
            $topics[] = 'pemateri';
        }
        if (str_contains($lowercaseComment, 'fasilitas') || str_contains($lowercaseComment, 'tempat') || str_contains($lowercaseComment, 'ruangan') || str_contains($lowercaseComment, 'ac')) {
            $topics[] = 'fasilitas';
        }
        if (str_contains($lowercaseComment, 'waktu') || str_contains($lowercaseComment, 'jadwal') || str_contains($lowercaseComment, 'molor')) {
            $topics[] = 'waktu & jadwal';
        }
        if (str_contains($lowercaseComment, 'konsumsi') || str_contains($lowercaseComment, 'makanan') || str_contains($lowercaseComment, 'snack')) {
            $topics[] = 'konsumsi';
        }
        if (empty($topics)) {
            $topics[] = 'umum';
        }

        return [
            'sentiment' => $sentiment,
            'sentiment_score' => $score,
            'topics' => array_values(array_unique($topics)),
            'analysis_status' => $statusReason,
        ];
    }
}
