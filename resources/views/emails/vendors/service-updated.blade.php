<x-mail::message>
# Servizio Inviato per Approvazione

Ciao {{ $profile->vendorAccount?->first_name ?? 'Partner' }},

Ti confermiamo che le modifiche al tuo servizio **{{ $profile->title ?? $profile->offering->name }}** sono state salvate correttamente.

Il servizio è tornato **in fase di approvazione** per permettere al nostro team di verificare i contenuti aggiornati e garantire la qualità della piattaforma. 

Appena un amministratore lo avrà revisionato e approvato, il servizio tornerà automaticamente visibile su PrestaShop per i tuoi clienti. Riceverai un'ulteriore email di notifica a pubblicazione avvenuta.

Se hai domande o necessiti di supporto, non esitare a contattarci!

<x-mail::button :url="route('vendor.dashboard')">
Vai alla tua Dashboard
</x-mail::button>

Cordiali Saluti,<br>
Il Team di {{ config('app.name') }}
</x-mail::message>
