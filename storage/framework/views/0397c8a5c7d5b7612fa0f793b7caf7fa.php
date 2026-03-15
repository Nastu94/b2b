<?php $__env->startComponent('mail::message'); ?>


<div style="text-align:center;margin-bottom:28px;">
    <img src="<?php echo new \Illuminate\Support\EncodedHtmlString(asset('images/party-legacy-logo.svg')); ?>"
         alt="Party Legacy"
         width="200"
         height="40"
         style="display:inline-block;">
</div>

# Hai ricevuto una nuova prenotazione

Ciao **<?php echo new \Illuminate\Support\EncodedHtmlString($booking->vendorAccount->company_name); ?>**,

Hai ricevuto una nuova richiesta di prenotazione tramite **Party Legacy**. Accedi al gestionale per confermarla o rifiutarla.

---

## Dettagli prenotazione

| | |
|---|---|
| **Servizio** | <?php echo new \Illuminate\Support\EncodedHtmlString($booking->offering->name ?? '—'); ?> |
| **Data evento** | <?php echo new \Illuminate\Support\EncodedHtmlString($booking->event_date->format('d/m/Y')); ?> |
| **Fascia oraria** | <?php echo new \Illuminate\Support\EncodedHtmlString(optional($booking->vendorSlot)->label ?? '—'); ?> |
| **Importo** | <?php echo new \Illuminate\Support\EncodedHtmlString(number_format($booking->total_amount, 2, ',', '.')); ?> <?php echo new \Illuminate\Support\EncodedHtmlString($booking->currency); ?> |
| **Rif. ordine** | <?php echo new \Illuminate\Support\EncodedHtmlString($booking->prestashop_order_id); ?> |

---

## Dati cliente

| | |
|---|---|
| **Nome** | <?php echo new \Illuminate\Support\EncodedHtmlString(data_get($booking->customer_data, 'firstname')); ?> <?php echo new \Illuminate\Support\EncodedHtmlString(data_get($booking->customer_data, 'lastname')); ?> |
| **Email** | <?php echo new \Illuminate\Support\EncodedHtmlString(data_get($booking->customer_data, 'email')); ?> |

---


<?php $__env->startComponent('mail::button', ['url' => route('login') . '?redirect=' . urlencode(route('vendor.bookings.show', $booking)), 'color' => 'green']); ?>
Gestisci la prenotazione
<?php echo $__env->renderComponent(); ?>

Hai **tempo limitato** per rispondere. Se non confermi entro i termini, la prenotazione potrebbe essere annullata automaticamente.

A presto,<br>
Il team di **Party Legacy**

<?php echo $__env->renderComponent(); ?><?php /**PATH C:\laragon\www\b2b.partylegacy.it\resources\views/emails/bookings/nuova-prenotazione-vendor.blade.php ENDPATH**/ ?>