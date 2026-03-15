@component('mail::message')

<div style="text-align:center;margin-bottom:28px;">
    <img src="{{ asset('images/party-legacy-logo.svg') }}"
         alt="Party Legacy"
         width="200"
         height="40"
         style="display:inline-block;">
</div>

# Il tuo evento è confermato!

Ciao **{{ data_get($booking->customer_data, 'firstname') }}**,

Ottima notizia! Il fornitore ha confermato la tua prenotazione tramite **Party Legacy**. Ecco tutti i dettagli per il tuo evento.

---

## Riepilogo prenotazione

| | |
|---|---|
| **Servizio** | {{ $booking->offering->name ?? '—' }} |
| **Data evento** | {{ $booking->event_date->format('d/m/Y') }} |
| **Fascia oraria** | {{ optional($booking->vendorSlot)->label ?? '—' }} |
| **Importo pagato** | {{ number_format($booking->total_amount, 2, ',', '.') }} {{ $booking->currency }} |
| **Rif. ordine** | {{ $booking->prestashop_order_id }} |

---

## Contatti del fornitore

Puoi contattare direttamente il tuo fornitore per qualsiasi dettaglio organizzativo.

| | |
|---|---|
| **Azienda** | {{ $booking->vendorAccount->company_name }} |
| **Telefono** | {{ $booking->vendorAccount->phone ?? '—' }} |
| **Email** | {{ $booking->vendorAccount->billing_email ?? $booking->vendorAccount->pec_email ?? '—' }} |
| **Città** | {{ $booking->vendorAccount->effectiveCity() ?? '—' }} |

@if($booking->vendor_notes)

---

## Note del fornitore

> {{ $booking->vendor_notes }}

@endif

---

Ti auguriamo un evento indimenticabile!

A presto,<br>
Il team di **Party Legacy**

@endcomponent