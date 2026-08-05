@component('mail::message')

# Prenotazione rifiutata: verifica rimborso

Il vendor ha rifiutato una prenotazione. Questa email segnala un'azione amministrativa manuale: **nessun rimborso è stato eseguito automaticamente**.

## Riferimenti operativi

| | |
|---|---|
| **Booking Laravel** | #{{ $booking->id }} |
| **Ordine PrestaShop** | {{ $booking->prestashop_order_id ?: 'Non disponibile' }} |
| **Riga ordine PrestaShop** | {{ $booking->prestashop_order_line_id ?: 'Non disponibile' }} |
| **Vendor** | {{ $booking->vendorAccount->company_name ?: trim(($booking->vendorAccount->first_name ?? '').' '.($booking->vendorAccount->last_name ?? '')) ?: 'Non disponibile' }} |
| **Servizio** | {{ $booking->offering->name ?? 'Non disponibile' }} |
| **Data evento** | {{ $booking->event_date->format('d/m/Y') }} |
| **Fascia oraria** | {{ optional($booking->vendorSlot)->label ?? 'Non disponibile' }} |
| **Importo** | {{ $booking->total_amount !== null ? number_format((float) $booking->total_amount, 2, ',', '.').' '.($booking->currency ?: 'EUR') : 'Non disponibile' }} |
| **Pagamento registrato in Laravel** | {{ $booking->paid_at ? $booking->paid_at->format('d/m/Y H:i') : 'No' }} |

@php
    $customerName = trim(
        (string) (data_get($booking->customer_data, 'firstname') ?: '') . ' ' .
        (string) (data_get($booking->customer_data, 'lastname') ?: '')
    );
    $customerName = $customerName ?: data_get($booking->customer_data, 'name') ?: 'Non disponibile';
    $customerPhone = data_get($booking->customer_data, 'phone')
        ?: data_get($booking->customer_data, 'delivery_address.phone_mobile')
        ?: data_get($booking->customer_data, 'delivery_address.phone')
        ?: 'Non disponibile';
@endphp

## Cliente

| | |
|---|---|
| **Nome** | {{ $customerName }} |
| **Email** | {{ data_get($booking->customer_data, 'email') ?: 'Non disponibile' }} |
| **Telefono** | {{ $customerPhone }} |

@if($booking->decline_reason)

## Motivo del rifiuto

> {{ $booking->decline_reason }}

@endif

@if($booking->vendor_notes)

## Note del vendor

> {{ $booking->vendor_notes }}

@endif

## Azione richiesta

1. Aprire l'ordine indicato nel Back Office PrestaShop.
2. Verificare metodo e stato effettivo del pagamento.
3. Se l'importo è stato incassato, procedere manualmente con il rimborso secondo la procedura amministrativa.

@component('mail::button', ['url' => route('admin.bookings.show', $booking), 'color' => 'primary'])
Apri la prenotazione
@endcomponent

Questa è una notifica automatica generata da Party Legacy.

@endcomponent
