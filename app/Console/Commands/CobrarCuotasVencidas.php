<?php

namespace App\Console\Commands;

use App\Models\Cuota;
use App\Models\MetodoPagoUsuario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\CardException;
use Stripe\StripeClient;

class CobrarCuotasVencidas extends Command
{
    protected $signature = 'pagos:cobrar-cuotas';

    protected $description = 'Cobra automáticamente (off-session) las cuotas vencidas con estado PENDIENTE.';

    public function handle(StripeClient $stripe): int
    {
        $rate = (float) config('services.stripe.bob_usd_rate');
        $currency = config('services.stripe.currency', 'usd');

        $cuotas = Cuota::where('estado', 'PENDIENTE')
            ->where('fecha_vencimiento', '<=', today())
            ->where('num_cuota', '>', 1) // la cuota 1 ya se cobró on-session al crear el plan
            ->with('pago.venta')
            ->get();

        $this->info("Cuotas vencidas a procesar: {$cuotas->count()}");

        foreach ($cuotas as $cuota) {
            $cuota->refresh();
            if ($cuota->estado !== 'PENDIENTE') {
                continue;
            }

            $usuarioId = $cuota->pago->venta->usuario_id;
            $metodo = MetodoPagoUsuario::where('usuario_id', $usuarioId)
                ->where('es_principal', true)
                ->where('activo', true)
                ->first();

            if (!$metodo) {
                Log::warning("Cuota #{$cuota->id} sin método de pago principal para usuario {$usuarioId}");
                continue;
            }

            try {
                $stripe->paymentIntents->create([
                    'amount'         => (int) round(($cuota->monto / $rate) * 100),
                    'currency'       => $currency,
                    'customer'       => $metodo->stripe_customer_id,
                    'payment_method' => $metodo->stripe_pm_id,
                    'off_session'    => true,
                    'confirm'        => true,
                    'metadata'       => [
                        'pago_id'  => $cuota->pago_id,
                        'cuota_id' => $cuota->id,
                    ],
                ]);

                Log::info("Cobro off-session enviado para cuota #{$cuota->id}");
            } catch (CardException $e) {
                // cuota.estado solo admite PENDIENTE/PAGADO (CHECK constraint de BD) — queda
                // PENDIENTE y se reintentará en la siguiente corrida del comando.
                Log::warning("Cobro de cuota #{$cuota->id} rechazado: {$e->getMessage()}");
            } catch (\Throwable $e) {
                Log::error("Error inesperado cobrando cuota #{$cuota->id}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
