@component('mail::message')

{{-- Logo Party Legacy caricato da file pubblico --}}
<div style="text-align:center;margin-bottom:28px;">
    <img src="{{ asset('images/party-legacy-logo.svg') }}"
         alt="Party Legacy"
         width="200"
         height="40"
         style="display:inline-block;">
</div>

# Il tuo Servizio è Online!

Ciao **{{ $profile->vendorAccount?->first_name ?? $profile->vendorAccount?->company_name ?? 'Fornitore' }}**,

Ottime notizie! La tua scheda servizio **"{{ $profile->offering?->name ?? 'Servizio' }}"** è stata analizzata e formalmente approvata dal nostro team di moderazione.

La scheda è ora **visibile a tutti i clienti** sul catalogo ufficiale di Party Legacy ed è pronta a ricevere da subito le prime richieste di prenotazione.

@component('mail::button', ['url' => route('login'), 'color' => 'green'])
Gestisci i tuoi Servizi
@endcomponent

Ricorda che sarai sempre libero di aggiornare foto, testi e disponibilità accedendo alla tua area riservata.

Ti auguriamo tantissime prenotazioni,

A presto,<br>
Il team di **Party Legacy**

@endcomponent
