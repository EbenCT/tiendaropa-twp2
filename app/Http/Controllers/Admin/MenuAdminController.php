<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class MenuAdminController extends Controller
{
    public function index()
    {
        $items = MenuItem::with('children')
            ->whereNull('parent_id')
            ->orderBy('orden')
            ->get();

        return Inertia::render('Admin/Menu/Index', compact('items'));
    }

    public function create()
    {
        $padres = MenuItem::whereNull('parent_id')->orderBy('orden')->get();
        return Inertia::render('Admin/Menu/Form', ['item' => null, 'padres' => $padres]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'label'             => 'required|string|max:100',
            'route_name'        => 'required|string|max:100',
            'icon'              => 'nullable|string|max:50',
            'role_nivel_minimo' => 'required|integer|min:0|max:4',
            'parent_id'         => 'nullable|exists:menu_item,id',
            'orden'             => 'required|integer|min:0',
        ], [
            'label.required'      => 'El nombre del ítem es obligatorio.',
            'route_name.required' => 'La ruta es obligatoria.',
            'orden.required'      => 'El orden es obligatorio.',
        ]);

        MenuItem::create(array_merge($request->only([
            'label', 'route_name', 'icon', 'role_nivel_minimo', 'parent_id', 'orden'
        ]), ['activo' => true]));

        Cache::forget('menu_nivel_0');
        Cache::forget('menu_nivel_1');
        Cache::forget('menu_nivel_2');
        Cache::forget('menu_nivel_3');
        Cache::forget('menu_nivel_4');

        return redirect()->route('admin.menu.index')
            ->with('success', 'Ítem de menú creado.');
    }

    public function edit(int $id)
    {
        $item   = MenuItem::findOrFail($id);
        $padres = MenuItem::whereNull('parent_id')->where('id', '!=', $id)->orderBy('orden')->get();

        return Inertia::render('Admin/Menu/Form', compact('item', 'padres'));
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'label'             => 'required|string|max:100',
            'route_name'        => 'required|string|max:100',
            'icon'              => 'nullable|string|max:50',
            'role_nivel_minimo' => 'required|integer|min:0|max:4',
            'parent_id'         => 'nullable|exists:menu_item,id',
            'orden'             => 'required|integer|min:0',
            'activo'            => 'boolean',
        ]);

        $item = MenuItem::findOrFail($id);
        $item->update($request->only([
            'label', 'route_name', 'icon', 'role_nivel_minimo', 'parent_id', 'orden', 'activo'
        ]));

        // Limpiar cache de menú
        for ($i = 0; $i <= 4; $i++) {
            Cache::forget("menu_nivel_{$i}");
        }

        return redirect()->route('admin.menu.index')
            ->with('success', 'Ítem de menú actualizado.');
    }

    public function toggleActivo(int $id)
    {
        $item = MenuItem::findOrFail($id);
        $nuevoEstado = !$item->activo;
        $item->update(['activo' => $nuevoEstado]);

        for ($i = 0; $i <= 4; $i++) {
            Cache::forget("menu_nivel_{$i}");
        }

        $msg = $nuevoEstado ? 'Ítem activado.' : 'Ítem desactivado.';
        return back()->with('success', $msg);
    }

    public function destroy(int $id)
    {
        $item = MenuItem::findOrFail($id);
        $item->update(['activo' => false]);

        for ($i = 0; $i <= 4; $i++) {
            Cache::forget("menu_nivel_{$i}");
        }

        return back()->with('success', 'Ítem de menú desactivado.');
    }
}
