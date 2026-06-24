<?php

namespace App\Http\Middleware;

use App\Models\MenuItem;
use App\Models\PageVisit;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     * Disponibles en TODAS las páginas Vue via usePage().props
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $rolNivel = $this->getRolNivel($user?->rol_nuevo ?? strtolower($user?->rol ?? ''));
        $currentUrl = '/' . $request->path();

        return array_merge(parent::share($request), [
            // Usuario autenticado con datos básicos
            'auth' => [
                'user' => $user ? [
                    'id'       => $user->id,
                    'nombre'   => $user->nombre,
                    'apellido' => $user->apellido,
                    'email'    => $user->email,
                    'rol'      => $user->rol_nuevo ?? strtolower($user->rol ?? 'invitado'),
                    'nivel'    => $rolNivel,
                ] : null,
            ],

            // Menú dinámico filtrado por nivel de rol
            'menu' => $this->buildMenu($rolNivel),

            // Visitas de la página actual
            'pageVisits' => $this->getPageVisits($currentUrl),

            // Flash messages
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
                'info'    => fn () => $request->session()->get('info'),
            ],
        ]);
    }

    /**
     * Construye el menú filtrado por nivel de rol del usuario.
     */
    private function buildMenu(int $nivelUsuario): array
    {
        try {
            // Cache del menú por nivel de rol — 5 minutos
            return \Cache::remember("menu_nivel_{$nivelUsuario}", 300, function () use ($nivelUsuario) {
                return MenuItem::with('children')
                    ->whereNull('parent_id')
                    ->where('activo', true)
                    ->where('role_nivel_minimo', '<=', $nivelUsuario)
                    ->orderBy('orden')
                    ->get()
                    ->map(fn ($item) => [
                        'id'    => $item->id,
                        'label' => $item->label,
                        'route' => $item->route_name,
                        'icon'  => $item->icon,
                        'hijos' => $item->children
                            ->filter(fn ($h) => $h->activo && $h->role_nivel_minimo <= $nivelUsuario)
                            ->map(fn ($h) => [
                                'id'    => $h->id,
                                'label' => $h->label,
                                'route' => $h->route_name,
                                'icon'  => $h->icon,
                            ])->values(),
                    ])->toArray();
            });
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Obtiene el contador de visitas para la URL actual.
     */
    private function getPageVisits(string $url): int
    {
        try {
            return PageVisit::where('page_url', $url)->value('visit_count') ?? 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Retorna el nivel numérico del rol del usuario.
     */
    private function getRolNivel(?string $rol): int
    {
        return match ($rol) {
            'admin'       => 4,
            'propietario' => 3,
            'vendedor'    => 2,
            'cliente'     => 1,
            default       => 0, // invitado / sin sesión
        };
    }
}
