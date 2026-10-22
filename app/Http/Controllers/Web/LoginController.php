<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $request->validate([
            'identity' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'identity.required' => 'Email atau nomor telepon wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $identity = $request->input('identity');

        $user = User::where('email', $identity)
            ->orWhere('phone', $identity)
            ->first();

        if (
            !$user ||
            !Hash::check($request->input('password'), $user->password) ||
            !$user->is_active
        ) {
            return back()
                ->withErrors([
                    'identity' => 'Email/nomor telepon atau kata sandi salah, atau akun tidak aktif.',
                ])
                ->onlyInput('identity');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended('/')->with(
            'success',
            'Berhasil masuk ke SIGAP BENCANA!'
        );
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with(
            'success',
            'Kamu berhasil keluar dari akun.'
        );
    }
}
