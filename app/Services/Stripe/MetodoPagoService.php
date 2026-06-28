<?php

namespace App\Services\Stripe;

use App\Models\MetodoPagoUsuario;
use App\Models\User;
use Stripe\SetupIntent;
use Stripe\StripeClient;

class MetodoPagoService
{
    public function __construct(private StripeClient $stripe)
    {
    }

    public function obtenerOcrearCustomer(User $user): string
    {
        $existente = MetodoPagoUsuario::where('usuario_id', $user->id)
            ->whereNotNull('stripe_customer_id')
            ->first();

        if ($existente) {
            return $existente->stripe_customer_id;
        }

        $customer = $this->stripe->customers->create([
            'email'    => $user->email,
            'name'     => $user->nombre_completo,
            'metadata' => ['usuario_id' => $user->id],
        ]);

        return $customer->id;
    }

    public function crearSetupIntent(User $user): SetupIntent
    {
        return $this->stripe->setupIntents->create([
            'customer'             => $this->obtenerOcrearCustomer($user),
            'payment_method_types' => ['card'],
            'usage'                => 'off_session',
            'metadata'             => ['usuario_id' => $user->id],
        ]);
    }

    public function marcarPrincipal(User $user, int $metodoPagoUsuarioId): void
    {
        MetodoPagoUsuario::where('usuario_id', $user->id)->update(['es_principal' => false]);
        MetodoPagoUsuario::where('usuario_id', $user->id)
            ->where('id', $metodoPagoUsuarioId)
            ->update(['es_principal' => true]);
    }

    public function eliminar(MetodoPagoUsuario $metodo): void
    {
        $metodo->update(['activo' => false]);

        try {
            if ($metodo->stripe_pm_id) {
                $this->stripe->paymentMethods->detach($metodo->stripe_pm_id);
            }
        } catch (\Throwable $e) {
            // Best-effort: no debe romper el soft-delete local si Stripe falla.
        }
    }
}
