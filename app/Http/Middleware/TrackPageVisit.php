<?php

namespace App\Http\Middleware;

use App\Models\PageVisit;
use Closure;
use Illuminate\Http\Request;

/**
 * Middleware que registra y actualiza el contador de visitas por página
 * en la tabla page_visit. Se ejecuta en cada request GET del grupo web.
 */
class TrackPageVisit
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        // Solo rastrear requests GET que no sean de Inertia parciales
        if ($request->isMethod('GET') && !$request->header('X-Inertia-Partial-Data')) {
            try {
                $url = $request->path() === '/' ? '/' : '/' . $request->path();
                $pageName = $this->resolvePageName($request);

                PageVisit::upsert(
                    [
                        'page_url'        => $url,
                        'page_name'       => $pageName,
                        'visit_count'     => 1,
                        'last_visited_at' => now(),
                    ],
                    uniqueBy: ['page_url'],
                    update: [
                        'visit_count'     => \DB::raw('page_visit.visit_count + 1'),
                        'last_visited_at' => now(),
                    ]
                );
            } catch (\Throwable $e) {
                // Nunca interrumpir la request por el contador
            }
        }

        return $response;
    }

    private function resolvePageName(Request $request): string
    {
        $route = $request->route();
        if ($route && $route->getName()) {
            return $route->getName();
        }
        return $request->path();
    }
}
