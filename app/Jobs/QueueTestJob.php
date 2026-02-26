<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;


class QueueTestJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Esegue il job.
     *
     * Questo job serve solo come test per verificare che:
     * - il driver "database" accetti il job (serializzazione OK);
     * - il worker lo processi correttamente;
     * - possiamo vedere un effetto misurabile (log).
     */
    public function handle(): void
    {
        // Scrive una riga di log per confermare l'esecuzione del job.
        \Log::info('QueueTestJob eseguito correttamente', [
            'at' => now()->toDateTimeString(),
        ]);
    }
}
