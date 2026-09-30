<?php

namespace App\Http\Controllers;

use App\Http\Requests\Feedback\StoreFeedbackRequest;
use App\Models\Event;
use App\Models\EventFeedback;
use App\Services\Ai\SentimentAnalysisService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Throwable;

class FeedbackController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected SentimentAnalysisService $sentimentService
    ) {}

    /**
     * Store feedback submitted by user/anggota.
     */
    public function store(StoreFeedbackRequest $request)
    {
        $user = $request->user();
        $event = Event::findOrFail($request->event_id);

        // 1. Perform Sentiment Analysis with Fallback Resilience
        try {
            $analysis = $this->sentimentService->analyze($request->comment, (int) $request->rating);
        } catch (Throwable $e) {
            // Safety rule: Feedback submission MUST NEVER FAIL even if AI fails!
            $analysis = [
                'sentiment' => $request->rating >= 4 ? 'positive' : ($request->rating <= 2 ? 'negative' : 'neutral'),
                'sentiment_score' => 0.50,
                'topics' => ['umum'],
                'analysis_status' => 'failed',
            ];
        }

        // 2. Save Feedback Record to DB
        $feedback = EventFeedback::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'sentiment' => $analysis['sentiment'],
            'sentiment_score' => $analysis['sentiment_score'],
            'topics' => $analysis['topics'],
            'analysis_status' => $analysis['analysis_status'],
        ]);

        return redirect()->route('events.show', $event->slug)
            ->with('success', 'Ulasan dan masukan Anda berhasil terkirim. Terima kasih atas partisipasinya!');
    }

    /**
     * Admin & Pengurus feedback & sentiment analysis dashboard.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', EventFeedback::class);

        $user = $request->user();
        $query = EventFeedback::with(['event.organization', 'user']);

        // Scope to Pengurus organization events
        if ($user->isPengurus()) {
            $orgIds = $user->organizations->pluck('id');
            $query->whereHas('event', function ($q) use ($orgIds) {
                $q->whereIn('organization_id', $orgIds);
            });
        }

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->event_id);
        }

        if ($request->filled('sentiment')) {
            $query->where('sentiment', $request->sentiment);
        }

        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('comment', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('event', function ($eq) use ($search) {
                        $eq->where('title', 'like', "%{$search}%");
                    });
            });
        }

        // Calculate Analytics Stats before pagination
        $statsQuery = clone $query;
        $totalFeedbacks = $statsQuery->count();
        $avgRating = $totalFeedbacks > 0 ? round($statsQuery->avg('rating'), 1) : 0;

        $positiveCount = (clone $query)->where('sentiment', 'positive')->count();
        $neutralCount = (clone $query)->where('sentiment', 'neutral')->count();
        $negativeCount = (clone $query)->where('sentiment', 'negative')->count();

        $stats = [
            'total' => $totalFeedbacks,
            'avg_rating' => $avgRating,
            'positive_count' => $positiveCount,
            'positive_pct' => $totalFeedbacks > 0 ? round(($positiveCount / $totalFeedbacks) * 100, 1) : 0,
            'neutral_count' => $neutralCount,
            'neutral_pct' => $totalFeedbacks > 0 ? round(($neutralCount / $totalFeedbacks) * 100, 1) : 0,
            'negative_count' => $negativeCount,
            'negative_pct' => $totalFeedbacks > 0 ? round(($negativeCount / $totalFeedbacks) * 100, 1) : 0,
        ];

        $feedbacks = $query->latest()->paginate(12)->withQueryString();

        // Get available events list for dropdown filter
        $eventsQuery = Event::query();
        if ($user->isPengurus()) {
            $eventsQuery->whereIn('organization_id', $user->organizations->pluck('id'));
        }
        $events = $eventsQuery->orderBy('title')->get();

        return view('feedback.index', compact('feedbacks', 'stats', 'events'));
    }
}
