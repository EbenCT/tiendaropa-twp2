<?php

namespace App\Http\Controllers;

use App\Services\Stripe\WebhookHandlerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handle(Request $request, WebhookHandlerService $webhookHandlerService)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, config('services.stripe.webhook_secret'));
        } catch (SignatureVerificationException $e) {
            Log::warning('Webhook de Stripe con firma inválida', ['error' => $e->getMessage()]);
            return response('Invalid signature', 400);
        }

        try {
            $webhookHandlerService->handle($event);
        } catch (\Throwable $e) {
            // Nunca dejar escapar un 500: Stripe reintenta indefinidamente si no recibe 200.
            Log::error('Error procesando webhook de Stripe', [
                'event' => $event->type,
                'error' => $e->getMessage(),
            ]);
        }

        return response('OK', 200);
    }
}
