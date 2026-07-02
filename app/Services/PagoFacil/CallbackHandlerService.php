<?php

namespace App\Services\PagoFacil;

use App\Models\Pedido;
use App\Services\BitacoraService;
use Illuminate\Support\Facades\Log;

class CallbackHandlerService
{
    // Códigos documentados para el producto "Botón de Pago CheckOut" (Estado: 1=PENDIENTE,
    // 2=PAGADO, 3=REVERTIDO, 4=ANULADO). Se asumen válidos también para el callback de la API
    // MasterQR hasta confirmarlo con una notificación real — el valor crudo siempre se loguea.
    private const ESTADO_PAGADO = 2;

    public function handle(array $payload): bool
    {
        $paymentNumber = (string) ($payload['PedidoID'] ?? '');
        $estadoCrudo = (int) ($payload['Estado'] ?? 0);

        Log::info('Callback PagoFácil recibido', ['payload' => $payload]);

        if (!preg_match('/^P(?<pedidoId>\d+)-(?<tipo>U|C(?<numCuota>\d+))$/', $paymentNumber, $m)) {
            Log::warning('Callback PagoFácil con PedidoID no reconocible', ['paymentNumber' => $paymentNumber]);
            return false;
        }

        $pedido = Pedido::with('venta.pagos.cuotas')->find((int) $m['pedidoId']);
        if (!$pedido || !$pedido->venta) {
            Log::warning("Callback PagoFácil: no existe pedido/venta para {$paymentNumber}");
            return false;
        }

        $pago = $pedido->venta->pagos->firstWhere('gateway', 'pagofacil');
        if (!$pago) {
            Log::warning("Callback PagoFácil: no existe Pago PagoFácil para {$paymentNumber}");
            return false;
        }

        $pagado = $estadoCrudo === self::ESTADO_PAGADO;

        if ($m['tipo'] === 'U') {
            return $this->procesarPagoUnico($pedido, $pago, $estadoCrudo, $pagado);
        }

        return $this->procesarCuota($pedido, $pago, (int) $m['numCuota'], $estadoCrudo, $pagado);
    }

    private function procesarPagoUnico(Pedido $pedido, $pago, int $estadoCrudo, bool $pagado): bool
    {
        $pago->update([
            'pagofacil_status' => (string) $estadoCrudo,
            'fecha_pago'       => $pagado ? now() : $pago->fecha_pago,
        ]);

        if ($pagado) {
            $pedido->confirmarPorPago();
            BitacoraService::pago(
                $pedido->usuario_id,
                "Pago único PagoFácil confirmado — Pedido #{$pedido->id}"
            );
        }

        Log::info("Pago único PagoFácil actualizado", ['pago_id' => $pago->id, 'estado' => $estadoCrudo]);

        return true;
    }

    private function procesarCuota(Pedido $pedido, $pago, int $numCuota, int $estadoCrudo, bool $pagado): bool
    {
        $cuota = $pago->cuotas->firstWhere('num_cuota', $numCuota);
        if (!$cuota) {
            Log::warning("Callback PagoFácil: no existe Cuota #{$numCuota} para pago_id={$pago->id}");
            return false;
        }

        $cuota->update([
            'pagofacil_status' => (string) $estadoCrudo,
            // cuota.estado solo admite PENDIENTE/PAGADO (CHECK constraint de BD) — si no está
            // pagada se deja PENDIENTE, igual que en el flujo de Stripe.
            'estado'           => $pagado ? 'PAGADO' : $cuota->estado,
            'fecha_pago_real'  => $pagado ? now() : $cuota->fecha_pago_real,
        ]);

        if ($pagado && $numCuota === 1) {
            $pedido->confirmarPorPago();
        }

        if ($pagado) {
            BitacoraService::pago(
                $pedido->usuario_id,
                "Pago cuota #{$numCuota} PagoFácil confirmado — Pedido #{$pedido->id}"
            );
        }

        Log::info("Cuota PagoFácil #{$numCuota} actualizada", ['cuota_id' => $cuota->id, 'estado' => $estadoCrudo]);

        return true;
    }
}
