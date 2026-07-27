<?php

return [
    'key' => env('BOOKING_BRIDGE_KEY'),
    'inbound_key' => env('BOOKING_BRIDGE_INBOUND_KEY', env('BOOKING_BRIDGE_KEY')),
    'hmac_secret_inbound' => env('BOOKING_BRIDGE_HMAC_SECRET_INBOUND'),
    'hmac_secret_outbound' => env('BOOKING_BRIDGE_HMAC_SECRET_OUTBOUND'),
    'hmac_mode' => env('BOOKING_BRIDGE_HMAC_MODE', 'legacy'), // legacy, off, optional, required
    'distance_mode' => env('BOOKING_BRIDGE_DISTANCE_MODE', 'legacy'), // legacy, shadow, enforce
    'geocoding_fallback_mode' => env('BOOKING_BRIDGE_GEOCODING_FALLBACK_MODE', 'legacy'), // legacy, strict
    'allow_expired_hold_reacquire' => env('BOOKING_BRIDGE_ALLOW_EXPIRED_HOLD_REACQUIRE', false),
    'api_contract_version' => env('BOOKING_BRIDGE_API_CONTRACT_VERSION', 1),
    'chat_page_size' => env('BOOKING_BRIDGE_CHAT_PAGE_SIZE', 50),
    'chat_max_page_size' => env('BOOKING_BRIDGE_CHAT_MAX_PAGE_SIZE', 100),
    'commission' => [
        'maximum_rate' => env('BOOKING_BRIDGE_COMMISSION_MAX_RATE', 20),
    ],
];
