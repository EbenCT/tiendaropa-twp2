<?php

namespace App\Http\Controllers;

use App\Services\PagoFacil\CallbackHandlerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PagoFacilCallbackController extends Controller
{
    public function handle(Request $request, CallbackHandlerService $callbackHandlerService)
    {
        try {
            $callbackHandlerService->handle($request->all());
        } catch (\Throwable $e) {
            // Nunca devolver un error real: PagoFácil reintentaría indefinidamente.
            Log::error('Error procesando callback de PagoFácil', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'error'   => 0,
            'status'  => 1,
            'message' => 'Notificación procesada correctamente',
            'values'  => true,
        ]);
    }
}
