<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UsuarioAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('nombre', 'ilike', '%' . $request->q . '%')
                  ->orWhere('apellido', 'ilike', '%' . $request->q . '%')
                  ->orWhere('email', 'ilike', '%' . $request->q . '%');
            });
        }
        if ($request->filled('rol')) {
            $query->where('rol_nuevo', $request->rol);
        }

        $usuarios = $query->orderByDesc('id')->paginate(15)->withQueryString();

        return Inertia::render('Admin/Usuarios/Index', compact('usuarios'));
    }

    public function create()
    {
        $rolesDisponibles = $this->getRolesDisponibles(request()->user());
        return Inertia::render('Admin/Usuarios/Form', [
            'usuario'           => null,
            'rolesDisponibles'  => $rolesDisponibles,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'ci'        => 'required|string|max:20|unique:usuario,ci',
            'nombre'    => 'required|string|max:100',
            'apellido'  => 'required|string|max:100',
            'email'     => 'required|email|unique:usuario,email',
            'telefono'  => 'nullable|string|max:20',
            'password'  => 'required|string|min:8',
            'rol_nuevo' => 'required|in:cliente,vendedor,propietario,admin',
        ], [
            'ci.required'       => 'El CI es obligatorio.',
            'ci.unique'         => 'Este CI ya está registrado.',
            'nombre.required'   => 'El nombre es obligatorio.',
            'apellido.required' => 'El apellido es obligatorio.',
            'email.required'    => 'El email es obligatorio.',
            'email.unique'      => 'Este email ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min'      => 'La contraseña debe tener al menos 8 caracteres.',
            'rol_nuevo.required'=> 'El rol es obligatorio.',
        ]);

        User::create([
            'ci'        => $request->ci,
            'nombre'    => $request->nombre,
            'apellido'  => $request->apellido,
            'email'     => $request->email,
            'telefono'  => $request->telefono,
            'password'  => Hash::make($request->password),
            'rol'       => $this->rolLegado($request->rol_nuevo),
            'rol_nuevo' => $request->rol_nuevo,
            'activo'    => true,
        ]);

        return redirect()->route('admin.usuarios.index')
            ->with('success', 'Usuario creado exitosamente.');
    }

    public function edit(int $id)
    {
        $usuario = User::findOrFail($id);
        $rolesDisponibles = $this->getRolesDisponibles(request()->user());

        return Inertia::render('Admin/Usuarios/Form', compact('usuario', 'rolesDisponibles'));
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'nombre'    => 'required|string|max:100',
            'apellido'  => 'required|string|max:100',
            'email'     => 'required|email|unique:usuario,email,' . $id,
            'telefono'  => 'nullable|string|max:20',
            'password'  => 'nullable|string|min:8',
            'rol_nuevo' => 'required|in:cliente,vendedor,propietario,admin',
            'activo'    => 'boolean',
        ], [
            'nombre.required'   => 'El nombre es obligatorio.',
            'apellido.required' => 'El apellido es obligatorio.',
            'email.required'    => 'El email es obligatorio.',
            'email.unique'      => 'Este email ya está registrado.',
        ]);

        $usuario = User::findOrFail($id);
        $data = $request->only(['nombre', 'apellido', 'email', 'telefono', 'rol_nuevo']);
        $data['rol'] = $this->rolLegado($request->rol_nuevo);
        $data['activo'] = $request->boolean('activo', true);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $usuario->update($data);

        return redirect()->route('admin.usuarios.index')
            ->with('success', 'Usuario actualizado.');
    }

    public function destroy(int $id)
    {
        $usuario = User::findOrFail($id);
        $usuario->update(['activo' => false]);

        return back()->with('success', 'Usuario desactivado.');
    }

    /**
     * La columna heredada 'rol' tiene un CHECK constraint que solo permite
     * PROPIETARIO/VENDEDOR/CLIENTE (el proyecto Java nunca tuvo rol admin),
     * por lo que 'admin' debe mapearse a un valor permitido para no violar la BD.
     */
    private function rolLegado(string $rolNuevo): string
    {
        return $rolNuevo === 'admin' ? 'PROPIETARIO' : strtoupper($rolNuevo);
    }

    private function getRolesDisponibles(User $currentUser): array
    {
        $nivel = $currentUser->nivel_rol;
        $roles = [['value' => 'cliente', 'label' => 'Cliente']];

        if ($nivel >= 3) {
            $roles[] = ['value' => 'vendedor', 'label' => 'Vendedor'];
        }
        if ($nivel >= 4) {
            $roles[] = ['value' => 'propietario', 'label' => 'Propietario'];
        }

        return $roles;
    }
}
