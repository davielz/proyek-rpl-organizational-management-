<?php

namespace App\Http\Controllers;

use App\Models\DiscussionComment;
use App\Models\DiscussionPost;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DiscussionController extends Controller
{
    /**
     * Display a listing of discussion posts.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', DiscussionPost::class);

        $search = $request->get('search');

        $query = DiscussionPost::with(['user', 'organization'])->withCount('comments');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $posts = $query->orderByDesc('created_at')->paginate(10)->withQueryString();

        return view('discussions.index', compact('posts', 'search'));
    }

    /**
     * Show the form for creating a new discussion post.
     */
    public function create(): View
    {
        Gate::authorize('create', DiscussionPost::class);

        $organizations = Organization::all();

        return view('discussions.create', compact('organizations'));
    }

    /**
     * Store a newly created discussion post in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', DiscussionPost::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:10000'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
        ], [
            'title.required' => 'Judul diskusi wajib diisi.',
            'content.required' => 'Tulis isi diskusi terlebih dahulu.',
        ]);

        $post = DiscussionPost::create([
            'user_id' => $request->user()->id,
            'organization_id' => $validated['organization_id'] ?? null,
            'title' => $validated['title'],
            'content' => $validated['content'],
        ]);

        return redirect()->route('discussions.show', $post)->with('success', 'Diskusi berhasil dibuat.');
    }

    /**
     * Display the specified discussion post and its comments.
     */
    public function show(DiscussionPost $discussion): View
    {
        Gate::authorize('view', $discussion);

        $discussion->load(['user', 'organization']);
        $comments = $discussion->comments()->with('user')->orderBy('created_at', 'asc')->paginate(15);

        return view('discussions.show', [
            'post' => $discussion,
            'comments' => $comments,
        ]);
    }

    /**
     * Show the form for editing the specified discussion post.
     */
    public function edit(DiscussionPost $discussion): View
    {
        Gate::authorize('update', $discussion);

        $organizations = Organization::all();

        return view('discussions.edit', ['post' => $discussion, 'organizations' => $organizations]);
    }

    /**
     * Update the specified discussion post in storage.
     */
    public function update(Request $request, DiscussionPost $discussion): RedirectResponse
    {
        Gate::authorize('update', $discussion);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:10000'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
        ], [
            'title.required' => 'Judul diskusi wajib diisi.',
            'content.required' => 'Tulis isi diskusi terlebih dahulu.',
        ]);

        $discussion->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'organization_id' => $validated['organization_id'] ?? null,
        ]);

        return redirect()->route('discussions.show', $discussion)->with('success', 'Diskusi berhasil diperbarui.');
    }

    /**
     * Remove the specified discussion post from storage.
     */
    public function destroy(DiscussionPost $discussion): RedirectResponse
    {
        Gate::authorize('delete', $discussion);

        $discussion->delete();

        return redirect()->route('discussions.index')->with('success', 'Diskusi berhasil dihapus.');
    }

    /**
     * Store a comment on a discussion post.
     */
    public function storeComment(Request $request, DiscussionPost $discussion): RedirectResponse
    {
        Gate::authorize('create', DiscussionComment::class);

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:3000'],
        ], [
            'content.required' => 'Komentar tidak boleh kosong.',
        ]);

        DiscussionComment::create([
            'discussion_post_id' => $discussion->id,
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
        ]);

        return redirect()->route('discussions.show', $discussion)->with('success', 'Komentar berhasil ditambahkan.');
    }

    /**
     * Update a discussion comment.
     */
    public function updateComment(Request $request, DiscussionComment $comment): RedirectResponse
    {
        Gate::authorize('update', $comment);

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:3000'],
        ], [
            'content.required' => 'Komentar tidak boleh kosong.',
        ]);

        $comment->update([
            'content' => $validated['content'],
        ]);

        return redirect()->route('discussions.show', $comment->discussion_post_id)->with('success', 'Komentar berhasil diperbarui.');
    }

    /**
     * Delete a discussion comment.
     */
    public function destroyComment(DiscussionComment $comment): RedirectResponse
    {
        Gate::authorize('delete', $comment);

        $postId = $comment->discussion_post_id;
        $comment->delete();

        return redirect()->route('discussions.show', $postId)->with('success', 'Komentar berhasil dihapus.');
    }
}
