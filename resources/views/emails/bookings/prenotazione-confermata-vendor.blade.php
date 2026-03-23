@component('mail::message')

<div style="text-align:center;margin-bottom:28px;">
    <img src="{{ asset('images/party-legacy-logo.svg') }}"
         alt="Party Legacy"
         width="200"
         height="40"
         style="display:inline-block;">
</div>

# Prenotazione confermata

Ciao **{{ $booking->vendorAccount->company_name }}**,

Hai confermato con successo la prenotazione. Il cliente è stato notificato. Di seguito i dati di contatto del cliente per eventuali comunicazioni.

---

## Dettagli prenotazione

| | |
|---|---|
| **Servizio** | {{ $booking->offering->name ?? '—' }} |
| **Data evento** | {{ $booking->event_date->format('d/m/Y') }} |
| **Fascia oraria** | {{ optional($booking->vendorSlot)->label ?? '—' }} |
| **Importo** | {{ number_format($booking->total_amount, 2, ',', '.') }} {{ $booking->currency }} |

---

## Contatti del cliente

| | |
|---|---|
| **Nome** | {{ data_get($booking->customer_data, 'firstname') }} {{ data_get($booking->customer_data, 'lastname') }} |
| **Email** | {{ data_get($booking->customer_data, 'email') }} |

@if($booking->vendor_notes)

---

## Le tue note

> {{ $booking->vendor_notes }}

@endif

---

@component('mail::button', ['url' => route('login') . '?redirect=' . urlencode(route('vendor.bookings.show', $booking)), 'color' => 'green'])
Visualizza prenotazione
@endcomponent

A presto,<br>
Il team di **Party Legacy**

@endcomponent