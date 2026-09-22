<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::latest()->paginate(10);

        return view('admin.users.index', compact('users'));
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
}
