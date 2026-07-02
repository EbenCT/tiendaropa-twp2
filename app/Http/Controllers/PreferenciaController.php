<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PreferenciaController extends Controller
{
    public function guardar(Request $request)
    {
        $request->validate([
            'tema'   => 'nullable|string|in:ninos,jovenes,adultos',
            'modo'   => 'nullable|string|in:auto,dia,noche',
            'escala' => 'nullable|numeric|min:0.8|max:1.4',
        ]);

        $user = $request->user();

        $user->update(array_filter([
            'pref_tema'   => $request->tema,
            'pref_modo'   => $request->modo,
            'pref_escala' => $request->escala,
        ], fn ($v) => $v !== null));

        return response()->json(['ok' => true]);
    }
}
