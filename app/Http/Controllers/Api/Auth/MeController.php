<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint di autenticazione / identità.
 *
 * @group Auth
 *
 * Endpoint utili per verificare che il token Sanctum sia valido e che il chiamante
 * (es. bridge PrestaShop) stia autenticandosi correttamente.
 */
class MeController extends Controller
{
    /**
     * Restituisce l'utente autenticato via Sanctum.
     *
     * Questo endpoint è un “smoke test” per:
     * - verificare che il middleware auth:sanctum funzioni;
     * - verificare che il token inviato dal client sia valido.
     *
     * @authenticated
     *
     * @response 200 scenario="OK" {"id":1,"name":"Audit Test 2","email":"audit.test@example.com"}
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Ritorna l'utente associato al token Sanctum (se valido).
        return response()->json($request->user());
    }
}