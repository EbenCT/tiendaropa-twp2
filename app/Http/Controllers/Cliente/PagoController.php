<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\MetodoPagoUsuario;
use App\Models\Pedido;
use App\Services\Stripe\CuotasService;
use App\Services\Stripe\PagoUnicoService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Stripe\Checkout\Session;

class PagoController extends Controller
{
    public function __construct(
        private PagoUnicoService $pagoUnicoService,
        private CuotasService $cuotasService,
    ) {
    }

    public function mostrarPago(Request $request, int $id)
    {
        $pedido = Pedido::where('usuario_id', $request->user()->id)
            ->with(['detalles.producto', 'venta.pagos'])
            ->findOrFail($id);

        if ($pedido->venta?->pagos->contains(fn ($p) => $p->stripe_status === 'succeeded')) {
            return redirect()->route('pedidos.show', $pedido->id)
                ->with('info', 'Este pedido ya fue pagado.');
        }

        $rate = (float) config('services.stripe.bob_usd_rate');
        $metodosPago = MetodoPagoUsuario::where('usuario_id', $request->user()->id)
            ->where('activo', true)
            ->get();

        return Inertia::render('Pedidos/Pagar', [
            'pedido'       => $pedido,
            'totalUsd'     => round(((float) $pedido->venta->total) / $rate, 2),
            'metodosPago'  => $metodosPago,
        ]);
    }

    public function iniciarPagoUnico(Request $request, int $id)
    {
        $pedido = Pedido::where('usuario_id', $request->user()->id)->with('detalles.producto')->findOrFail($id);
        $venta = $pedido->venta;

        $session = $this->pagoUnicoService->crearCheckoutSession($pedido, $venta);

        return response()->json(['url' => $session->url]);
    }

    public function iniciarPagoCuotas(Request $request, int $id)
    {
        $request->validate([
            'num_cuotas'             => 'required|integer|in:2,3,6',
            'metodo_pago_usuario_id' => 'required|exists:metodo_pago_usuario,id',
        ]);

        $pedido = Pedido::where('usuario_id', $request->user()->id)->with('venta')->findOrFail($id);
        $metodo = MetodoPagoUsuario::where('usuario_id', $request->user()->id)
            ->where('id', $request->metodo_pago_usuario_id)
            ->where('activo', true)
            ->firstOrFail();

        $this->cuotasService->crearPlanCuotas($pedido, $pedido->venta, (int) $request->num_cuotas, $metodo);

        return redirect()->route('pedidos.show', $pedido->id)
            ->with('success', 'Plan de cuotas creado. Se cobró la primera cuota.');
    }

    public function exito(Request $request)
    {
        $pedidoId = null;

        if ($request->session_id) {
            try {
                $session = Session::retrieve($request->session_id, ['api_key' => config('services.stripe.secret')]);
                $pedidoId = $session->metadata->pedido_id ?? null;
            } catch (\Throwable $e) {
                // Solo lectura/informativo — el webhook es la fuente de verdad del pago.
            }
        }

        return $pedidoId
            ? redirect()->route('pedidos.show', $pedidoId)->with('success', '¡Pago recibido! Procesando confirmación...')
            : redirect()->route('pedidos.historial')->with('success', '¡Pago recibido! Procesando confirmación...');
    }

    public function cancelado(Request $request)
    {
        $pedidoId = $request->query('pedido_id');

        return $pedidoId
            ? redirect()->route('pedidos.show', $pedidoId)->with('error', 'Pago cancelado, puedes intentarlo de nuevo.')
            : redirect()->route('pedidos.historial')->with('error', 'Pago cancelado.');
    }
}
