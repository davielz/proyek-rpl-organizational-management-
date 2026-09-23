<?php

namespace App\Http\Controllers;

use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Http\Requests\Event\UpdateEventStatusRequest;
use App\Models\Event;
use App\Models\Organization;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ManageEventController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('create', Event::class);

        $user = $request->user();
        $query = Event::with(['organization', 'creator', 'registrations']);

        if ($user->isPengurus()) {
            $orgIds = $user->organizations->pluck('id');
            $query->whereIn('organization_id', $orgIds);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%'.$request->search.'%');
        }

        $events = $query->latest()->paginate(10);

        return view('events.manage.index', compact('events'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', Event::class);

        $user = $request->user();
        $organizations = $user->isAdmin()
            ? Organization::all()
            : $user->organizations;

        return view('events.manage.create', compact('organizations'));
    }

    public function store(StoreEventRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $data['slug'] = Str::slug($data['title']).'-'.Str::lower(Str::random(5));

        Event::create($data);

        return redirect()->route('manage.events.index')->with('success', 'Kegiatan baru berhasil dibuat.');
    }

    public function edit(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $user = $request->user();
        $organizations = $user->isAdmin()
            ? Organization::all()
            : $user->organizations;

        return view('events.manage.edit', compact('event', 'organizations'));
    }

    public function update(UpdateEventRequest $request, Event $event)
    {
        $this->authorize('update', $event);

        $data = $request->validated();
        if ($data['title'] !== $event->title) {
            $data['slug'] = Str::slug($data['title']).'-'.Str::lower(Str::random(5));
        }

        $event->update($data);

        return redirect()->route('manage.events.index')->with('success', 'Data kegiatan berhasil diperbarui.');
    }

    public function destroy(Request $request, Event $event)
    {
        $this->authorize('delete', $event);

        $event->delete();

        return redirect()->route('manage.events.index')->with('success', 'Kegiatan berhasil dihapus.');
    }

    public function updateStatus(UpdateEventStatusRequest $request, Event $event)
    {
        $this->authorize('manageStatus', $event);

        $event->update(['status' => $request->status]);

        return back()->with('success', "Status kegiatan berhasil diubah menjadi {$request->status}.");
    }
}
