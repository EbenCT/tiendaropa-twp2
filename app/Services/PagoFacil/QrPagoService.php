<?php

namespace App\Services\PagoFacil;

use App\Models\Cuota;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Venta;
use Illuminate\Support\Collection;

class QrPagoService
{
    public function __construct(private PagoFacilClient $client)
    {
    }

    public function generarQrPagoUnico(Pedido $pedido, Venta $venta): array
    {
        $pago = Pago::updateOrCreate(
            ['venta_id' => $venta->id],
            [
                'modalidad'   => 'CONTADO',
                'monto_total' => $venta->total,
                'num_cuotas'  => 1,
                'metodo'      => 'qr_pagofacil',
                'gateway'     => 'pagofacil',
            ]
        );

        $paymentNumber = "P{$pedido->id}-U";
        $values = $this->generar($pedido, (float) $venta->total, $paymentNumber, $pedido->detalles);

        $pago->update([
            'pagofacil_transaction_id' => (string) $values['transactionId'],
            'pagofacil_status'         => null,
            'pagofacil_qr_base64'      => $values['qrBase64'],
            'pagofacil_expira_en'      => $values['expirationDate'],
        ]);

        return $this->respuesta($paymentNumber, $values);
    }

    public function generarQrCuota(Cuota $cuota, Pedido $pedido): array
    {
        $paymentNumber = "P{$pedido->id}-C{$cuota->num_cuota}";
        $values = $this->generar($pedido, (float) $cuota->monto, $paymentNumber, new Collection());

        $cuota->update([
            'pagofacil_transaction_id' => (string) $values['transactionId'],
            'pagofacil_status'         => null,
            'pagofacil_qr_base64'      => $values['qrBase64'],
            'pagofacil_expira_en'      => $values['expirationDate'],
        ]);

        return $this->respuesta($paymentNumber, $values);
    }

    public function consultarEstado(string $paymentNumber): array
    {
        return $this->client->queryTransaction($paymentNumber);
    }

    /**
     * PagoFácil valida que callbackUrl tenga un dominio público resoluble (rechaza localhost,
     * 127.0.0.1 y dominios que no resuelven) — confirmado empíricamente contra el sandbox. En
     * local, sin un túnel público (ngrok/localtunnel), se envía un dominio público real como
     * placeholder solo para no bloquear la generación del QR; el callback no llegará a esta app,
     * por eso "Ya pagué, verificar" (query-transaction) y el comando programado son la fuente de
     * verdad real en desarrollo. En un host público (ej. tecnoweb.org.bo) se usa la URL real.
     */
    private function callbackUrl(): string
    {
        $callback = route('pagofacil.callback');
        $host = parse_url($callback, PHP_URL_HOST);

        if (in_array($host, ['localhost', '127.0.0.1'], true)) {
            return 'https://www.tecnoweb.org.bo/pagofacil-callback-no-disponible-en-local';
        }

        return preg_replace('/^http:/', 'https:', $callback);
    }

    private function respuesta(string $paymentNumber, array $values): array
    {
        return [
            'paymentNumber'  => $paymentNumber,
            'qrBase64'       => $values['qrBase64'],
            'expirationDate' => $values['expirationDate'],
        ];
    }

    private function generar(Pedido $pedido, float $monto, string $paymentNumber, Collection $detalles): array
    {
        $usuario = $pedido->usuario;

        $orderDetail = $detalles->isNotEmpty()
            ? $detalles->values()->map(fn ($d, $i) => [
                'serial'   => $i + 1,
                'product'  => $d->producto->nombre ?? "Producto #{$d->producto_id}",
                'quantity' => $d->cantidad,
                'price'    => (float) $d->precio_unitario,
                'discount' => 0,
                'total'    => round((float) $d->precio_unitario * $d->cantidad, 2),
            ])->all()
            : [[
                'serial'   => 1,
                'product'  => "Pedido #{$pedido->id}",
                'quantity' => 1,
                'price'    => round($monto, 2),
                'discount' => 0,
                'total'    => round($monto, 2),
            ]];

        return $this->client->generateQr([
            'paymentMethod' => config('services.pagofacil.payment_method_id'),
            'clientName'    => trim(($usuario->nombre ?? '') . ' ' . ($usuario->apellido ?? '')) ?: 'Cliente',
            'documentType'  => 1,
            'documentId'    => $usuario->ci ?? '0',
            'phoneNumber'   => $usuario->telefono ?: '00000000',
            'email'         => $usuario->email,
            'paymentNumber' => $paymentNumber,
            'amount'        => round($monto, 2),
            'currency'      => config('services.pagofacil.currency'),
            'clientCode'    => (string) $usuario->id,
            'callbackUrl'   => $this->callbackUrl(),
            'orderDetail'   => $orderDetail,
        ]);
    }
}
