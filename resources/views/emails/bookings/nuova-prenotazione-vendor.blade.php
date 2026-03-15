@component('mail::message')

{{-- Logo Party Legacy caricato da file pubblico --}}
<div style="text-align:center;margin-bottom:28px;">
    <img src="{{ asset('images/party-legacy-logo.svg') }}"
         alt="Party Legacy"
         width="200"
         height="40"
         style="display:inline-block;">
</div>

# Hai ricevuto una nuova prenotazione

Ciao **{{ $booking->vendorAccount->company_name }}**,

Hai ricevuto una nuova richiesta di prenotazione tramite **Party Legacy**. Accedi al gestionale per confermarla o rifiutarla.

---

## Dettagli prenotazione

| | |
|---|---|
| **Servizio** | {{ $booking->offering->name ?? '—' }} |
| **Data evento** | {{ $booking->event_date->format('d/m/Y') }} |
| **Fascia oraria** | {{ optional($booking->vendorSlot)->label ?? '—' }} |
| **Importo** | {{ number_format($booking->total_amount, 2, ',', '.') }} {{ $booking->currency }} |
| **Rif. ordine** | {{ $booking->prestashop_order_id }} |

---

## Dati cliente

| | |
|---|---|
| **Nome** | {{ data_get($booking->customer_data, 'firstname') }} {{ data_get($booking->customer_data, 'lastname') }} |
| **Email** | {{ data_get($booking->customer_data, 'email') }} |

---

{{-- Il link punta al login con redirect automatico alla prenotazione.
     Dopo il login Fortify segue il parametro ?redirect= e porta il vendor direttamente alla pagina. --}}
@component('mail::button', ['url' => route('login') . '?redirect=' . urlencode(route('vendor.bookings.show', $booking)), 'color' => 'green'])
Gestisci la prenotazione
@endcomponent

Hai **tempo limitato** per rispondere. Se non confermi entro i termini, la prenotazione potrebbe essere annullata automaticamente.

A presto,<br>
Il team di **Party Legacy**

@endcomponent