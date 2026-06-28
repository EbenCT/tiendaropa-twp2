<?php

namespace App\Services\Stripe;

use App\Models\Cuota;
use App\Models\MetodoPagoUsuario;
use App\Models\Pago;
use Illuminate\Support\Facades\Log;
use Stripe\Event;
use Stripe\PaymentMethod;
use Stripe\StripeClient;

class WebhookHandlerService
{
    public function __construct(private StripeClient $stripe)
    {
    }

    public function handle(Event $event): void
    {
        match ($event->type) {
            'checkout.session.completed'  => $this->handleCheckoutSessionCompleted($event),
            'payment_intent.succeeded'    => $this->handlePaymentIntentSucceeded($event),
            'payment_intent.payment_failed' => $this->handlePaymentIntentPaymentFailed($event),
            'setup_intent.succeeded'      => $this->handleSetupIntentSucceeded($event),
            default                       => Log::info("Stripe webhook ignorado: {$event->type}"),
        };
    }

    private function handleCheckoutSessionCompleted(Event $event): void
    {
        $session = $event->data->object;
        $ventaId = $session->metadata->venta_id ?? null;

        if (!$ventaId) {
            Log::warning('checkout.session.completed sin venta_id en metadata', ['session' => $session->id]);
            return;
        }

        $pago = Pago::where('venta_id', $ventaId)->first();
        if (!$pago) {
            Log::warning("checkout.session.completed: no existe Pago para venta_id={$ventaId}");
            return;
        }

        $pago->update([
            'stripe_payment_intent_id' => $session->payment_intent,
            'stripe_status'            => 'succeeded',
            'fecha_pago'               => now(),
        ]);

        $pago->venta?->pedido?->confirmarPorPago();

        Log::info("Pago único confirmado vía checkout.session.completed", ['pago_id' => $pago->id]);
    }

    private function handlePaymentIntentSucceeded(Event $event): void
    {
        $intent = $event->data->object;
        $cuotaId = $intent->metadata->cuota_id ?? null;

        if (!$cuotaId) {
            // No es parte de un plan de cuotas (probablemente ya manejado por checkout.session.completed).
            return;
        }

        $cuota = Cuota::with('pago.venta.pedido')->find($cuotaId);
        if (!$cuota) {
            Log::warning("payment_intent.succeeded: no existe Cuota id={$cuotaId}");
            return;
        }

        $cuota->update(['estado' => 'PAGADO', 'fecha_pago_real' => now()]);

        if ($cuota->num_cuota === 1) {
            $cuota->pago->update([
                'stripe_payment_intent_id' => $intent->id,
                'stripe_status'            => 'succeeded',
                'fecha_pago'               => now(),
            ]);
            $cuota->pago->venta?->pedido?->confirmarPorPago();
        }

        Log::info("Cuota #{$cuota->num_cuota} pagada", ['cuota_id' => $cuota->id]);
    }

    private function handlePaymentIntentPaymentFailed(Event $event): void
    {
        $intent = $event->data->object;
        $cuotaId = $intent->metadata->cuota_id ?? null;
        $ventaId = $intent->metadata->venta_id ?? null;

        if ($cuotaId) {
            // cuota.estado solo admite PENDIENTE/PAGADO (CHECK constraint de BD) — se deja
            // PENDIENTE y el comando programado la reintentará al día siguiente.
            Log::warning("Cobro de cuota falló, queda PENDIENTE para reintento", ['cuota_id' => $cuotaId]);
            return;
        }

        if ($ventaId) {
            Pago::where('venta_id', $ventaId)->update(['stripe_status' => 'failed']);
            Log::warning("Pago único falló", ['venta_id' => $ventaId]);
        }
    }

    private function handleSetupIntentSucceeded(Event $event): void
    {
        $setupIntent = $event->data->object;
        $usuarioId = $setupIntent->metadata->usuario_id ?? null;

        if (!$usuarioId) {
            Log::warning('setup_intent.succeeded sin usuario_id en metadata', ['setup_intent' => $setupIntent->id]);
            return;
        }

        $pm = PaymentMethod::retrieve($setupIntent->payment_method, ['api_key' => config('services.stripe.secret')]);

        $esPrimero = !MetodoPagoUsuario::where('usuario_id', $usuarioId)->where('activo', true)->exists();

        $existente = MetodoPagoUsuario::where('stripe_pm_id', $pm->id)->first();
        if ($existente) {
            $existente->update(['activo' => true]);
            return;
        }

        MetodoPagoUsuario::create([
            'usuario_id'         => $usuarioId,
            'stripe_customer_id' => $setupIntent->customer,
            'stripe_pm_id'       => $pm->id,
            'brand'              => $pm->card->brand ?? null,
            'last4'              => $pm->card->last4 ?? null,
            'es_principal'       => $esPrimero,
            'activo'             => true,
        ]);

        Log::info("Método de pago guardado para usuario {$usuarioId}", ['pm' => $pm->id]);
    }
}
