<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\WhatsAppNotificationService;
use Illuminate\Http\Request;

class UserApprovalController extends Controller
{
    /**
     * Display a listing of user registrations pending approval or filter by status.
     */
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');
        $search = $request->input('search');

        $query = User::query();

        if ($status && in_array($status, ['pending', 'active', 'rejected'], true)) {
            $query->where('status', $status);
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(10)->withQueryString();

        $stats = [
            'pending' => User::where('status', 'pending')->count(),
            'active' => User::where('status', 'active')->count(),
            'rejected' => User::where('status', 'rejected')->count(),
            'total' => User::count(),
        ];

        return view('manage.approvals.index', compact('users', 'status', 'search', 'stats'));
    }

    /**
     * Approve (ACC) the specified user's registration.
     */
    public function approve(Request $request, User $user, WhatsAppNotificationService $waService)
    {
        if ($user->status === 'active') {
            return back()->with('info', "Akun {$user->name} sudah dalam keadaan aktif.");
        }

        $user->status = 'active';
        $user->save();

        $waSent = $waService->notifyUserAccountApproved($user);

        $msg = "Akun {$user->name} ({$user->email}) berhasil disetujui (ACC).";
        if ($waSent) {
            $msg .= " Pemberitahuan telah dikirimkan ke WhatsApp pemilik akun ({$user->phone}).";
        }

        return back()->with('success', $msg);
    }

    /**
     * Reject the specified user's registration.
     */
    public function reject(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menolak akun Anda sendiri.');
        }

        if ($user->isAdmin() && ! $request->user()->isAdmin()) {
            return back()->with('error', 'Pengurus tidak dapat menolak akun Administrator.');
        }

        $user->status = 'rejected';
        $user->save();

        return back()->with('success', "Pendaftaran akun {$user->name} ({$user->email}) telah ditolak.");
    }
}
