<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\WhatsAppNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request)
    {
        if (Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            /** @var User $user */
            $user = Auth::user();

            if ($user->isPending()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Akun Anda sedang menunggu persetujuan (acc) dari Admin atau Pengurus. Silakan tunggu hingga disetujui.',
                ])->onlyInput('email');
            }

            if ($user->isRejected()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Pendaftaran akun Anda telah ditolak oleh Admin/Pengurus. Silakan hubungi pihak pengurus.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();

            return redirect()->intended('/dashboard')->with('success', 'Selamat datang kembali!');
        }

        return back()->withErrors([
            'email' => 'Email atau password yang Anda masukkan tidak cocok.',
        ])->onlyInput('email');
    }

    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request, WhatsAppNotificationService $waService)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'anggota',
            'status' => 'pending',
        ]);

        $waService->notifyNewUserRegistration($user);

        return redirect()->route('login')->with([
            'success' => 'Pendaftaran akun berhasil! Akun Anda sedang menunggu persetujuan (acc) dari Admin sebelum dapat digunakan untuk masuk.',
            'whatsapp_notify' => true,
            'registered_phone' => $user->phone,
            'registered_name' => $user->name,
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'Anda telah berhasil keluar.');
    }
}
