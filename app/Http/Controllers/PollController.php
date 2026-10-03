<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PollController extends Controller
{
    /**
     * Display a listing of polls.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Poll::class);

        $statusFilter = $request->get('status', 'all');
        $search = $request->get('search');

        $query = Poll::with(['creator', 'organization', 'options', 'votes']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($statusFilter === 'active') {
            $query->where('status', 'active')->where('deadline', '>', now());
        } elseif ($statusFilter === 'closed') {
            $query->where(function ($q) {
                $q->where('status', 'closed')->orWhere('deadline', '<=', now());
            });
        }

        $polls = $query->orderByDesc('created_at')->paginate(10)->withQueryString();

        return view('polls.index', compact('polls', 'statusFilter', 'search'));
    }

    /**
     * Show the form for creating a new poll.
     */
    public function create(): View
    {
        Gate::authorize('create', Poll::class);

        $organizations = Organization::all();

        return view('polls.create', compact('organizations'));
    }

    /**
     * Store a newly created poll in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Poll::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'deadline' => ['required', 'date', 'after:now'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'options' => ['required', 'array', 'min:2'],
            'options.*' => ['required', 'string', 'max:255', 'distinct'],
        ], [
            'title.required' => 'Judul polling wajib diisi.',
            'deadline.required' => 'Batas waktu (deadline) wajib diisi.',
            'deadline.after' => 'Batas waktu harus berupa tanggal dan waktu di masa depan.',
            'options.required' => 'Daftar pilihan jawaban wajib diisi.',
            'options.min' => 'Polling harus memiliki minimal 2 pilihan jawaban.',
            'options.*.required' => 'Pilihan jawaban tidak boleh kosong.',
            'options.*.distinct' => 'Pilihan jawaban tidak boleh ganda/duplikat.',
        ]);

        DB::transaction(function () use ($request, $validated) {
            $poll = Poll::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'deadline' => $validated['deadline'],
                'organization_id' => $validated['organization_id'] ?? null,
                'created_by' => $request->user()->id,
                'status' => 'active',
            ]);

            foreach ($validated['options'] as $optionText) {
                if (! empty(trim($optionText))) {
                    $poll->options()->create([
                        'option_text' => trim($optionText),
                    ]);
                }
            }
        });

        return redirect()->route('polls.index')->with('success', 'Polling baru berhasil dibuat dan diterbitkan.');
    }

    /**
     * Display the specified poll and its results.
     */
    public function show(Poll $poll): View
    {
        Gate::authorize('view', $poll);

        $poll->load(['creator', 'organization', 'options.votes', 'votes.user']);
        $user = auth()->user();

        $hasVoted = $poll->hasUserVoted($user);
        $userVote = $poll->getUserVote($user);
        $totalVotes = $poll->totalVotes();

        return view('polls.show', compact('poll', 'hasVoted', 'userVote', 'totalVotes'));
    }

    /**
     * Show the form for editing the specified poll.
     */
    public function edit(Poll $poll): View
    {
        Gate::authorize('update', $poll);

        $poll->load(['options', 'votes']);
        $hasVotes = $poll->totalVotes() > 0;
        $organizations = Organization::all();

        return view('polls.edit', compact('poll', 'hasVotes', 'organizations'));
    }

    /**
     * Update the specified poll in storage.
     */
    public function update(Request $request, Poll $poll): RedirectResponse
    {
        Gate::authorize('update', $poll);

        $hasVotes = $poll->totalVotes() > 0;

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'deadline' => ['required', 'date'],
            'status' => ['required', 'in:active,closed,draft'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
        ];

        if (! $hasVotes) {
            $rules['options'] = ['required', 'array', 'min:2'];
            $rules['options.*'] = ['required', 'string', 'max:255', 'distinct'];
        }

        $validated = $request->validate($rules, [
            'title.required' => 'Judul polling wajib diisi.',
            'deadline.required' => 'Batas waktu wajib diisi.',
            'options.min' => 'Polling harus memiliki minimal 2 pilihan jawaban.',
        ]);

        DB::transaction(function () use ($poll, $validated, $hasVotes) {
            $poll->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'deadline' => $validated['deadline'],
                'status' => $validated['status'],
                'organization_id' => $validated['organization_id'] ?? null,
            ]);

            if (! $hasVotes && isset($validated['options'])) {
                $poll->options()->delete();
                foreach ($validated['options'] as $optionText) {
                    if (! empty(trim($optionText))) {
                        $poll->options()->create([
                            'option_text' => trim($optionText),
                        ]);
                    }
                }
            }
        });

        return redirect()->route('polls.show', $poll)->with('success', 'Data polling berhasil diperbarui.');
    }

    /**
     * Remove the specified poll from storage.
     */
    public function destroy(Poll $poll): RedirectResponse
    {
        Gate::authorize('delete', $poll);

        $poll->delete();

        return redirect()->route('polls.index')->with('success', 'Polling berhasil dihapus.');
    }

    /**
     * Manually close an active poll.
     */
    public function close(Poll $poll): RedirectResponse
    {
        Gate::authorize('close', $poll);

        $poll->update(['status' => 'closed']);

        return redirect()->route('polls.show', $poll)->with('success', 'Polling telah ditutup.');
    }

    /**
     * Submit a vote for the specified poll.
     */
    public function vote(Request $request, Poll $poll): RedirectResponse
    {
        Gate::authorize('vote', $poll);

        $validated = $request->validate([
            'option_id' => ['required', 'integer', 'exists:poll_options,id'],
        ], [
            'option_id.required' => 'Silakan pilih salah satu opsi jawaban sebelum mengirim suara.',
            'option_id.exists' => 'Pilihan jawaban yang dipilih tidak valid.',
        ]);

        $user = $request->user();

        // Security check: verify that option_id belongs strictly to THIS poll
        $option = PollOption::where('id', $validated['option_id'])
            ->where('poll_id', $poll->id)
            ->first();

        if (! $option) {
            return redirect()->back()->withErrors([
                'option_id' => 'Pilihan jawaban yang dipilih bukan merupakan opsi dari polling ini.',
            ]);
        }

        // Additional Server-Side Checks
        if ($poll->isClosed()) {
            return redirect()->back()->with('error', 'Gagal memberikan suara: Masa berlaku polling sudah berakhir atau telah ditutup.');
        }

        if ($poll->hasUserVoted($user)) {
            return redirect()->back()->with('error', 'Anda telah memberikan suara pada polling ini sebelumnya.');
        }

        try {
            PollVote::create([
                'poll_id' => $poll->id,
                'option_id' => $option->id,
                'user_id' => $user->id,
                'voted_at' => now(),
            ]);
        } catch (QueryException $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan atau Anda sudah memberikan suara sebelumnya.');
        }

        return redirect()->route('polls.show', $poll)->with('success', 'Terima kasih! Suara Anda berhasil disimpan.');
    }
}
