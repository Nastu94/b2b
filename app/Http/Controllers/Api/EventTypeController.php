<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventType;
use Illuminate\Http\JsonResponse;

class EventTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $eventTypes = EventType::where('is_active', true)
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => $eventTypes
        ]);
    }
}
