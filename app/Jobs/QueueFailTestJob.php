<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class QueueFailTestJob implements ShouldQueue
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
     * Numero massimo di tentativi prima di finire in failed_jobs.
     *
     * In un confirm reale potresti usare 3-5 tentativi, non infinito.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * Secondi di attesa prima dei retry.
     *
     * Può essere un int (stesso backoff per tutti) o un array per backoff progressivo.
     *
     * @var int|array<int>
     */
    public int|array $backoff = [5, 15, 60];

    /**
     * Limite temporale oltre il quale NON ritentare più.
     *
     * Utile per evitare retry dopo ore/giorni su operazioni che hanno senso solo entro una finestra.
     */
    public function retryUntil(): \DateTimeInterface
    {
        // Ritenta al massimo per 5 minuti da "adesso" (solo test).
        return now()->addMinutes(5);
    }

    /**
     * Esegue il job.
     *
     * Questo job fallisce volutamente per testare:
     * - inserimento in failed_jobs
     * - retry manuale con queue:retry
     */
    public function handle(): void
    {
        // Logghiamo prima, così sappiamo che il worker lo ha preso in carico.
        \Log::warning('QueueFailTestJob sta per fallire (test)', [
            'at' => now()->toDateTimeString(),
        ]);

        // Fallimento intenzionale.
        throw new \RuntimeException('QueueFailTestJob fallito intenzionalmente (test).');
    }
}
