<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function create()
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required'    => 'El correo electrónico es obligatorio.',
            'email.email'       => 'Ingresa un correo electrónico válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        $credenciales = [
            'email'    => $request->email,
            'password' => $request->password,
            'activo'   => true,
        ];

        if (!Auth::attempt($credenciales, $request->boolean('recordarme'))) {
            return back()->withErrors([
                'email' => 'Las credenciales ingresadas no son correctas.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('home'))
            ->with('success', '¡Bienvenido, ' . Auth::user()->nombre . '!');
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home')
            ->with('info', 'Has cerrado sesión correctamente.');
    }
}
