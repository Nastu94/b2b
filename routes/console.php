<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Laravel Queue
|--------------------------------------------------------------------------
|
| Processa i job in coda senza tenere un worker permanente attivo.
| Parte ogni minuto, svuota la coda e poi si chiude.
|
*/
Schedule::command('queue:work database --queue=default --stop-when-empty --tries=3 --timeout=60')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->appendOutputTo(storage_path('logs/queue-worker.log'));

/*
|--------------------------------------------------------------------------
| Prestashop Sync
|--------------------------------------------------------------------------
|
| Crea/sincronizza su PrestaShop i vendor mancanti.
|
*/
Schedule::command('vendors:sync-prestashop --only-missing')
    ->hourly()
    ->withoutOverlapping(60)
    ->appendOutputTo(storage_path('logs/vendors-sync-prestashop.log'));

/*
|--------------------------------------------------------------------------
| BookingBridge Webhooks
|--------------------------------------------------------------------------
|
| Aggiorna BookingBridge / PrestaShop con i dati vendor.
|
*/
Schedule::command('vendors:push-webhooks')
    ->everyFifteenMinutes()
    ->withoutOverlapping(30)
    ->appendOutputTo(storage_path('logs/vendors-push-webhooks.log'));
