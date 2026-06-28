<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\MetodoPagoUsuario;
use App\Services\Stripe\MetodoPagoService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MetodoPagoController extends Controller
{
    public function __construct(private MetodoPagoService $metodoPagoService)
    {
    }

    public function index(Request $request)
    {
        $metodos = MetodoPagoUsuario::where('usuario_id', $request->user()->id)
            ->where('activo', true)
            ->get();

        return Inertia::render('MetodosPago/Index', ['metodos' => $metodos]);
    }

    public function crearSetupIntent(Request $request)
    {
        $setupIntent = $this->metodoPagoService->crearSetupIntent($request->user());

        return response()->json(['clientSecret' => $setupIntent->client_secret]);
    }

    public function marcarPrincipal(Request $request, int $id)
    {
        MetodoPagoUsuario::where('usuario_id', $request->user()->id)->where('id', $id)->firstOrFail();
        $this->metodoPagoService->marcarPrincipal($request->user(), $id);

        return back()->with('success', 'Método de pago actualizado como principal.');
    }

    public function eliminar(Request $request, int $id)
    {
        $metodo = MetodoPagoUsuario::where('usuario_id', $request->user()->id)->where('id', $id)->firstOrFail();
        $this->metodoPagoService->eliminar($metodo);

        return back()->with('success', 'Método de pago eliminado.');
    }
}
