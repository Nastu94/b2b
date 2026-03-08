<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BookingBridgeAuth
{
    public function handle(Request $request, Closure $next)
    {
        $expected = config('services.booking_bridge.key');
        $provided = $request->header('X-Booking-Bridge-Key');

        if (!$expected || !$provided || !hash_equals($expected, $provided)) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized'
            ], 401);
        }

        return $next($request);
    }
}