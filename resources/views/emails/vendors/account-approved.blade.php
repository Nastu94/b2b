@component('mail::message')

{{-- Logo Party Legacy caricato da file pubblico --}}
<div style="text-align:center;margin-bottom:28px;">
    <img src="{{ asset('images/party-legacy-logo.svg') }}"
         alt="Party Legacy"
         width="200"
         height="40"
         style="display:inline-block;">
</div>

# Il tuo Account Fornitore è Approvato!

Ciao **{{ $vendorAccount->first_name ?? $vendorAccount->company_name ?? 'Fornitore' }}**,

Siamo super felici di comunicarti che il tuo account su **Party Legacy** ha superato con successo i nostri controlli di qualità ed è stato approvato dal team!

Da questo momento sei **ufficialmente un fornitore attivo** sulla nostra piattaforma B2B.

@component('mail::button', ['url' => route('login'), 'color' => 'green'])
Accedi alla Dashboard
@endcomponent

Ti ricordiamo che il prossimo passo per iniziare a operare è compilare e mandare in approvazione le tue **Schede Servizio** dalla tua area riservata.

Ti auguriamo buon lavoro e tante collaborazioni di successo,

A presto,<br>
Il team di **Party Legacy**

@endcomponent
