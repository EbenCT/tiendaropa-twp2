<?php

namespace App\Console\Commands;

use App\Models\Cuota;
use App\Models\Pago;
use App\Services\PagoFacil\CallbackHandlerService;
use App\Services\PagoFacil\QrPagoService;
use Illuminate\Console\Command;

class SincronizarPagoFacil extends Command
{
    protected $signature = 'pagos:sincronizar-pagofacil';

    protected $description = 'Pre-genera QR de cuotas PagoFácil vencidas y sincroniza el estado de transacciones pendientes (red de seguridad si el callback no llega)';

    public function __construct(
        private QrPagoService $qrPagoService,
        private CallbackHandlerService $callbackHandlerService,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->preGenerarQrCuotasVencidas();
        $this->sincronizarPendientes();
    }

    private function preGenerarQrCuotasVencidas(): void
    {
        $cuotas = Cuota::where('estado', 'PENDIENTE')
            ->where('fecha_vencimiento', '<=', now()->toDateString())
            ->whereNull('pagofacil_transaction_id')
            ->whereHas('pago', fn ($q) => $q->where('gateway', 'pagofacil'))
            ->with('pago.venta.pedido')
            ->get();

        foreach ($cuotas as $cuota) {
            $pedido = $cuota->pago->venta->pedido ?? null;
            if (!$pedido) {
                continue;
            }

            try {
                $this->qrPagoService->generarQrCuota($cuota, $pedido);
                $this->info("QR generado para cuota #{$cuota->num_cuota} (pago_id={$cuota->pago_id})");
            } catch (\Throwable $e) {
                $this->error("Error generando QR de cuota id={$cuota->id}: {$e->getMessage()}");
            }
        }
    }

    private function sincronizarPendientes(): void
    {
        $pagos = Pago::where('gateway', 'pagofacil')
            ->whereNotNull('pagofacil_transaction_id')
            ->where(fn ($q) => $q->whereNull('pagofacil_status')->orWhere('pagofacil_status', '!=', '2'))
            ->with('venta.pedido')
            ->get();

        foreach ($pagos as $pago) {
            $pedido = $pago->venta->pedido ?? null;
            if ($pedido) {
                $this->consultarYAplicar("P{$pedido->id}-U");
            }
        }

        $cuotas = Cuota::where('estado', 'PENDIENTE')
            ->whereNotNull('pagofacil_transaction_id')
            ->whereHas('pago', fn ($q) => $q->where('gateway', 'pagofacil'))
            ->with('pago.venta.pedido')
            ->get();

        foreach ($cuotas as $cuota) {
            $pedido = $cuota->pago->venta->pedido ?? null;
            if ($pedido) {
                $this->consultarYAplicar("P{$pedido->id}-C{$cuota->num_cuota}");
            }
        }
    }

    private function consultarYAplicar(string $paymentNumber): void
    {
        try {
            $estado = $this->qrPagoService->consultarEstado($paymentNumber);
            $this->callbackHandlerService->handle([
                'PedidoID' => $paymentNumber,
                'Estado'   => $estado['paymentStatus'] ?? null,
            ]);
        } catch (\Throwable $e) {
            $this->error("Error consultando estado de {$paymentNumber}: {$e->getMessage()}");
        }
    }
}
