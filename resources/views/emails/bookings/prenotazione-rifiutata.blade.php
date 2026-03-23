@component('mail::message')

<div style="text-align:center;margin-bottom:28px;">
    <img src="{{ asset('images/party-legacy-logo.svg') }}"
         alt="Party Legacy"
         width="200"
         height="40"
         style="display:inline-block;">
</div>

# Prenotazione non disponibile

Ciao **{{ data_get($booking->customer_data, 'firstname') }}**,

Siamo spiacenti di informarti che il fornitore non ha potuto confermare la tua prenotazione per il **{{ $booking->event_date->format('d/m/Y') }}**.

---

## Dettagli prenotazione rifiutata

| | |
|---|---|
| **Servizio** | {{ $booking->offering->name ?? '—' }} |
| **Data evento** | {{ $booking->event_date->format('d/m/Y') }} |
| **Fascia oraria** | {{ optional($booking->vendorSlot)->label ?? '—' }} |
| **Rif. ordine** | {{ $booking->prestashop_order_id }} |

@if($booking->decline_reason)

---

## Motivazione del fornitore

> {{ $booking->decline_reason }}

@endif

---

## Cosa fare ora

Il tuo pagamento verrà rimborsato secondo le nostre politiche di rimborso. Ti consigliamo di cercare un altro fornitore disponibile per la tua data.

@component('mail::button', ['url' => config('app.url'), 'color' => 'primary'])
Cerca altri fornitori
@endcomponent

Per qualsiasi domanda non esitare a contattarci rispondendo a questa email.

A presto,<br>
Il team di **Party Legacy**

@endcomponent