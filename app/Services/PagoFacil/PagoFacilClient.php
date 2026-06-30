<?php

namespace App\Services\PagoFacil;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PagoFacilClient
{
    private const CACHE_KEY = 'pagofacil_access_token';

    public function login(bool $forzar = false): string
    {
        if (!$forzar && Cache::has(self::CACHE_KEY)) {
            return Cache::get(self::CACHE_KEY);
        }

        $response = $this->http()
            ->withHeaders([
                'tcTokenService' => config('services.pagofacil.token_service'),
                'tcTokenSecret'  => config('services.pagofacil.token_secret'),
            ])
            ->post('/login');

        $data = $response->json();

        if (!$response->successful() || ($data['error'] ?? 1) !== 0) {
            throw new RuntimeException('PagoFácil: fallo de autenticación — ' . ($data['message'] ?? $response->body()));
        }

        $token = $data['values']['accessToken'];
        $minutos = max(1, (int) floor(($data['values']['expiresInMinutes'] ?? 10) - 1));

        Cache::put(self::CACHE_KEY, $token, now()->addMinutes($minutos));

        return $token;
    }

    public function listEnabledServices(): array
    {
        return $this->autenticado(fn ($token) => $this->http()
            ->withToken($token)
            ->post('/list-enabled-services'));
    }

    public function generateQr(array $payload): array
    {
        return $this->autenticado(fn ($token) => $this->http()
            ->withToken($token)
            ->post('/generate-qr', $payload));
    }

    public function queryTransaction(string $paymentNumber): array
    {
        return $this->autenticado(fn ($token) => $this->http()
            ->withToken($token)
            ->post('/query-transaction', ['companyTransactionId' => $paymentNumber]));
    }

    /**
     * Cliente HTTP con el bundle CA del proyecto (storage/app/cacert.pem) — necesario porque esta
     * instalación de PHP en Windows no trae configurado curl.cainfo/openssl.cafile a nivel sistema.
     */
    private function http()
    {
        return Http::baseUrl(config('services.pagofacil.url'))
            ->withOptions(['verify' => storage_path('app/cacert.pem')]);
    }

    private function autenticado(callable $request): array
    {
        $token = $this->login();
        $response = $request($token);

        if ($response->status() === 401) {
            // El accessToken cacheado expiró antes de lo previsto — reintenta una vez con login forzado.
            $token = $this->login(forzar: true);
            $response = $request($token);
        }

        $data = $response->json();

        if (!$response->successful() || ($data['error'] ?? 1) !== 0) {
            throw new RuntimeException('PagoFácil: ' . ($data['message'] ?? $response->body()));
        }

        return $data['values'];
    }
}
