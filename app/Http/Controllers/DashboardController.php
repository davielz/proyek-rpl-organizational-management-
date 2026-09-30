<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Event;
use App\Models\EventFeedback;
use App\Models\EventRegistration;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // 1. ADMIN DASHBOARD
        if ($user->isAdmin()) {
            $stats = [
                'total_members' => User::where('role', 'anggota')->count(),
                'total_users' => User::count(),
                'total_organizations' => Organization::count(),
                'total_events' => Event::count(),
                'pending_reviews_count' => Document::where('status', 'submitted')->count(),
                'total_feedbacks' => EventFeedback::count(),
                'avg_rating' => EventFeedback::count() > 0 ? round(EventFeedback::avg('rating'), 1) : 0,
                'positive_feedbacks' => EventFeedback::where('sentiment', 'positive')->count(),
                'neutral_feedbacks' => EventFeedback::where('sentiment', 'neutral')->count(),
                'negative_feedbacks' => EventFeedback::where('sentiment', 'negative')->count(),
            ];

            $upcomingEvents = Event::with('organization')
                ->where('start_time', '>=', now())
                ->orderBy('start_time')
                ->take(5)
                ->get();

            $pendingDocuments = Document::with(['event.organization', 'submitter'])
                ->where('status', 'submitted')
                ->latest()
                ->take(5)
                ->get();

            $recentFeedbacks = EventFeedback::with(['event', 'user'])
                ->latest()
                ->take(5)
                ->get();

            return view('dashboard.admin', compact('stats', 'upcomingEvents', 'pendingDocuments', 'recentFeedbacks'));
        }

        // 2. PENGURUS DASHBOARD
        if ($user->isPengurus()) {
            $userOrgs = $user->organizations;
            $orgIds = $userOrgs->pluck('id');

            $orgEventsQuery = Event::whereIn('organization_id', $orgIds);
            $orgEventIds = (clone $orgEventsQuery)->pluck('id');

            $orgFeedbacksQuery = EventFeedback::whereIn('event_id', $orgEventIds);
            $totalFeedbacks = (clone $orgFeedbacksQuery)->count();

            $stats = [
                'total_events' => (clone $orgEventsQuery)->count(),
                'active_events' => (clone $orgEventsQuery)->where('status', 'active')->count(),
                'completed_events' => (clone $orgEventsQuery)->where('status', 'completed')->count(),
                'total_registrations' => EventRegistration::whereIn('event_id', $orgEventIds)->where('status', '!=', 'cancelled')->count(),
                'total_documents' => Document::whereIn('event_id', $orgEventIds)->count(),
                'pending_documents' => Document::whereIn('event_id', $orgEventIds)->where('status', 'submitted')->count(),
                'revision_documents' => Document::whereIn('event_id', $orgEventIds)->where('status', 'revision_requested')->count(),
                'approved_documents' => Document::whereIn('event_id', $orgEventIds)->where('status', 'approved')->count(),
                'total_feedbacks' => $totalFeedbacks,
                'avg_rating' => $totalFeedbacks > 0 ? round((clone $orgFeedbacksQuery)->avg('rating'), 1) : 0,
                'positive_feedbacks' => (clone $orgFeedbacksQuery)->where('sentiment', 'positive')->count(),
                'neutral_feedbacks' => (clone $orgFeedbacksQuery)->where('sentiment', 'neutral')->count(),
                'negative_feedbacks' => (clone $orgFeedbacksQuery)->where('sentiment', 'negative')->count(),
            ];

            $managedEvents = Event::with(['organization', 'registrations'])
                ->whereIn('organization_id', $orgIds)
                ->latest()
                ->take(5)
                ->get();

            $recentDocuments = Document::with(['event', 'submitter'])
                ->whereIn('event_id', $orgEventIds)
                ->latest()
                ->take(5)
                ->get();

            $recentFeedbacks = EventFeedback::with(['event', 'user'])
                ->whereIn('event_id', $orgEventIds)
                ->latest()
                ->take(5)
                ->get();

            return view('dashboard.pengurus', compact('stats', 'userOrgs', 'managedEvents', 'recentDocuments', 'recentFeedbacks'));
        }

        // 3. ANGGOTA DASHBOARD
        $myRegistrations = EventRegistration::with(['event.organization'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        $stats = [
            'total_followed' => $myRegistrations->where('status', '!=', 'cancelled')->count(),
            'attended' => $myRegistrations->where('status', 'attended')->count(),
            'registered' => $myRegistrations->where('status', 'registered')->count(),
            'cancelled' => $myRegistrations->where('status', 'cancelled')->count(),
        ];

        // Upcoming active events for catalog
        $upcomingEvents = Event::with('organization')
            ->where('status', 'active')
            ->where('start_time', '>=', now())
            ->orderBy('start_time')
            ->take(5)
            ->get();

        // Pending feedback events: Events the user registered for, but has NOT submitted feedback yet
        $submittedFeedbackEventIds = EventFeedback::where('user_id', $user->id)->pluck('event_id');

        $pendingFeedbackRegistrations = EventRegistration::with('event.organization')
            ->where('user_id', $user->id)
            ->where('status', '!=', 'cancelled')
            ->whereNotIn('event_id', $submittedFeedbackEventIds)
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard.anggota', compact('stats', 'myRegistrations', 'upcomingEvents', 'pendingFeedbackRegistrations'));
    }
}
