<?php

namespace App\Services\Stripe;

use App\Models\Cuota;
use App\Models\MetodoPagoUsuario;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Venta;
use Stripe\StripeClient;

class CuotasService
{
    public function __construct(private StripeClient $stripe)
    {
    }

    public function crearPlanCuotas(Pedido $pedido, Venta $venta, int $numCuotas, MetodoPagoUsuario $metodo): Pago
    {
        $pago = Pago::updateOrCreate(
            ['venta_id' => $venta->id],
            [
                'modalidad'   => 'CREDITO',
                'monto_total' => $venta->total,
                'num_cuotas'  => $numCuotas,
                'metodo'      => 'tarjeta_cuotas',
                'stripe_status' => null,
            ]
        );

        $total = (float) $venta->total;
        $montoBase = floor(($total / $numCuotas) * 100) / 100;
        $cuotas = [];

        for ($i = 1; $i <= $numCuotas; $i++) {
            $monto = $i === $numCuotas
                ? round($total - $montoBase * ($numCuotas - 1), 2)
                : $montoBase;

            $cuotas[] = Cuota::create([
                'pago_id'           => $pago->id,
                'num_cuota'         => $i,
                'monto'             => $monto,
                'fecha_vencimiento' => now()->addMonths($i - 1)->toDateString(),
                'estado'            => 'PENDIENTE',
            ]);
        }

        $rate = (float) config('services.stripe.bob_usd_rate');
        $currency = config('services.stripe.currency', 'usd');
        $primeraCuota = $cuotas[0];

        $intent = $this->stripe->paymentIntents->create([
            'amount'         => (int) round(($primeraCuota->monto / $rate) * 100),
            'currency'       => $currency,
            'customer'       => $metodo->stripe_customer_id,
            'payment_method' => $metodo->stripe_pm_id,
            'off_session'    => false,
            'confirm'        => true,
            'metadata'       => [
                'pago_id'   => $pago->id,
                'cuota_id'  => $primeraCuota->id,
                'venta_id'  => $venta->id,
                'pedido_id' => $pedido->id,
            ],
        ]);

        // El webhook payment_intent.succeeded es la única fuente de verdad — no se marca
        // la cuota/pago como exitosos aquí, ni siquiera si $intent->status === 'succeeded',
        // por el caso de 3D Secure (requires_action) que puede resolverse después.

        return $pago;
    }
}
