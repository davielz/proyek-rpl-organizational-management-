<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Organization;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class EventController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $query = Event::with('organization')
            ->whereIn('status', ['active', 'completed']);

        if ($request->filled('search')) {
            $query->where('title', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        $events = $query->latest('start_time')->paginate(9);
        $organizations = Organization::all();

        return view('events.index', compact('events', 'organizations'));
    }

    public function show(Request $request, Event $event)
    {
        $this->authorize('view', $event);

        $event->load(['organization', 'creator', 'registrations.user', 'feedbacks.user']);

        $user = $request->user();
        $isRegistered = false;
        $userRegistration = null;
        $userFeedback = null;

        if ($user) {
            $userRegistration = $event->registrations->where('user_id', $user->id)->first();
            $isRegistered = $userRegistration && $userRegistration->status !== 'cancelled';
            $userFeedback = $event->feedbacks->where('user_id', $user->id)->first();
        }

        $registeredCount = $event->registrations->where('status', '!=', 'cancelled')->count();
        $isFull = $event->quota > 0 && $registeredCount >= $event->quota;
        $avgRating = $event->feedbacks->count() > 0 ? round($event->feedbacks->avg('rating'), 1) : 0;

        return view('events.show', compact('event', 'isRegistered', 'userRegistration', 'userFeedback', 'registeredCount', 'isFull', 'avgRating'));
    }

    public function register(Request $request, Event $event)
    {
        $this->authorize('register', $event);

        EventRegistration::updateOrCreate(
            [
                'event_id' => $event->id,
                'user_id' => $request->user()->id,
            ],
            [
                'status' => 'registered',
                'registered_at' => now(),
            ]
        );

        return back()->with('success', 'Anda berhasil mendaftar sebagai peserta kegiatan ini.');
    }

    public function cancelRegistration(Request $request, Event $event)
    {
        $this->authorize('cancelRegistration', $event);

        $event->registrations()
            ->where('user_id', $request->user()->id)
            ->update(['status' => 'cancelled']);

        return back()->with('success', 'Pendaftaran Anda untuk kegiatan ini telah dibatalkan.');
    }
}
