<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status');
        $search = $request->input('search');

        $query = User::query();

        if ($status && in_array($status, ['pending', 'active', 'rejected'], true)) {
            $query->where('status', $status);
        }

        if (!empty($search)) {
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

        return view('admin.users.index', compact('users', 'status', 'search', 'stats'));
    }

    public function updateRole(UpdateRoleRequest $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat mengubah role akun Anda sendiri.');
        }

        $oldRole = $user->role;
        $user->role = $request->role;
        $user->save();

        return back()->with('success', "Role pengguna {$user->name} berhasil diubah dari {$oldRole} menjadi {$user->role}.");
    }

    public function approve(Request $request, User $user, \App\Services\TelegramNotificationService $telegramService)
    {
        $user->status = 'active';
        $user->save();

        $telegramSent = $telegramService->notifyUserAccountApproved($user);

        $msg = "Akun pengguna {$user->name} ({$user->email}) berhasil disetujui (ACC).";
        if ($telegramSent) {
            $msg .= " Pemberitahuan telah dikirimkan ke Telegram pemilik akun.";
        }

        return back()->with('success', $msg);
    }

    public function reject(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menolak akun Anda sendiri.');
        }

        $user->status = 'rejected';
        $user->save();

        return back()->with('success', "Pendaftaran akun {$user->name} ({$user->email}) berhasil ditolak.");
    }
}
