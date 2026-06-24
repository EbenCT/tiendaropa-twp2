<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class RegisterController extends Controller
{
    public function create()
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request)
    {
        $request->validate([
            'ci'       => 'required|string|max:20|unique:usuario,ci',
            'nombre'   => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'email'    => 'required|email|max:255|unique:usuario,email',
            'telefono' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'ci.required'        => 'El carnet de identidad es obligatorio.',
            'ci.unique'          => 'Este CI ya está registrado.',
            'nombre.required'    => 'El nombre es obligatorio.',
            'apellido.required'  => 'El apellido es obligatorio.',
            'email.required'     => 'El correo electrónico es obligatorio.',
            'email.email'        => 'El correo electrónico debe tener un formato válido.',
            'email.unique'       => 'Este correo ya está registrado.',
            'password.required'  => 'La contraseña es obligatoria.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $user = User::create([
            'ci'       => $request->ci,
            'nombre'   => $request->nombre,
            'apellido' => $request->apellido,
            'email'    => $request->email,
            'telefono' => $request->telefono,
            'password' => Hash::make($request->password),
            'rol'      => 'CLIENTE',
            'rol_nuevo'=> 'cliente',
            'activo'   => true,
        ]);

        Auth::login($user);

        return redirect()->route('home')
            ->with('success', '¡Bienvenido, ' . $user->nombre . '! Tu cuenta ha sido creada.');
    }
}
