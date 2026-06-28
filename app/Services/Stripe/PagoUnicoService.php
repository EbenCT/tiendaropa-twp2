<?php

namespace App\Services\Stripe;

use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Venta;
use Stripe\Checkout\Session;
use Stripe\StripeClient;

class PagoUnicoService
{
    public function __construct(private StripeClient $stripe)
    {
    }

    public function crearCheckoutSession(Pedido $pedido, Venta $venta): Session
    {
        $rate = (float) config('services.stripe.bob_usd_rate');
        $currency = config('services.stripe.currency', 'usd');

        $lineItems = $pedido->detalles->map(function ($detalle) use ($rate, $currency) {
            $precioUsd = ((float) $detalle->precio_unitario) / $rate;

            return [
                'price_data' => [
                    'currency'     => $currency,
                    'unit_amount'  => (int) round($precioUsd * 100),
                    'product_data' => [
                        'name' => $detalle->producto->nombre ?? "Producto #{$detalle->producto_id}",
                    ],
                ],
                'quantity' => $detalle->cantidad,
            ];
        })->all();

        Pago::updateOrCreate(
            ['venta_id' => $venta->id],
            [
                'modalidad'   => 'CONTADO',
                'monto_total' => $venta->total,
                'num_cuotas'  => 1,
                'metodo'      => 'tarjeta_unico',
                'stripe_status' => null,
            ]
        );

        $session = $this->stripe->checkout->sessions->create([
            'mode'        => 'payment',
            'line_items'  => $lineItems,
            'success_url' => route('pedidos.pago.exito') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('pedidos.pago.cancelado') . '?pedido_id=' . $pedido->id,
            'metadata'    => [
                'pedido_id' => $pedido->id,
                'venta_id'  => $venta->id,
            ],
        ]);

        return $session;
    }
}
