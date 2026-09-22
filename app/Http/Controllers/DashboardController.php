<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            $stats = [
                'total_users' => User::count(),
                'total_organizations' => Organization::count(),
                'total_events' => Event::count(),
                'pending_proposals' => Document::where('type', 'proposal')->where('status', 'submitted')->count(),
            ];

            return view('dashboard.admin', compact('stats'));
        }

        if ($user->isPengurus()) {
            $userOrgs = $user->organizations;
            $orgIds = $userOrgs->pluck('id');

            $stats = [
                'total_org_events' => Event::whereIn('organization_id', $orgIds)->count(),
                'pending_documents' => Document::whereIn('event_id', Event::whereIn('organization_id', $orgIds)->select('id'))
                    ->whereIn('status', ['draft', 'submitted', 'revision_requested'])
                    ->count(),
            ];

            $recentEvents = Event::whereIn('organization_id', $orgIds)->latest()->take(5)->get();

            return view('dashboard.pengurus', compact('stats', 'userOrgs', 'recentEvents'));
        }

        // Default: Anggota
        $registeredEvents = $user->registeredEvents()->latest()->take(5)->get();
        $upcomingEvents = Event::where('status', 'active')->where('start_time', '>=', now())->take(5)->get();

        return view('dashboard.anggota', compact('registeredEvents', 'upcomingEvents'));
    }
}
