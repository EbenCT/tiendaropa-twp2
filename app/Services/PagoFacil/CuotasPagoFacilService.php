<?php

namespace App\Services\PagoFacil;

use App\Models\Cuota;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Venta;

class CuotasPagoFacilService
{
    public function __construct(private QrPagoService $qrPagoService)
    {
    }

    public function crearPlanCuotas(Pedido $pedido, Venta $venta, int $numCuotas): array
    {
        $pago = Pago::updateOrCreate(
            ['venta_id' => $venta->id],
            [
                'modalidad'   => 'CREDITO',
                'monto_total' => $venta->total,
                'num_cuotas'  => $numCuotas,
                'metodo'      => 'qr_pagofacil_cuotas',
                'gateway'     => 'pagofacil',
            ]
        );

        // Si el cliente ya había iniciado este plan antes (reintento), no duplicar las filas Cuota.
        if (Cuota::where('pago_id', $pago->id)->doesntExist()) {
            $total = (float) $venta->total;
            $montoBase = floor(($total / $numCuotas) * 100) / 100;

            for ($i = 1; $i <= $numCuotas; $i++) {
                $monto = $i === $numCuotas
                    ? round($total - $montoBase * ($numCuotas - 1), 2)
                    : $montoBase;

                Cuota::create([
                    'pago_id'           => $pago->id,
                    'num_cuota'         => $i,
                    'monto'             => $monto,
                    'fecha_vencimiento' => now()->addMonths($i - 1)->toDateString(),
                    'estado'            => 'PENDIENTE',
                ]);
            }
        }

        $primeraCuota = Cuota::where('pago_id', $pago->id)->where('num_cuota', 1)->first();
        $qr = $this->qrPagoService->generarQrCuota($primeraCuota, $pedido);

        return $qr;
    }
}
