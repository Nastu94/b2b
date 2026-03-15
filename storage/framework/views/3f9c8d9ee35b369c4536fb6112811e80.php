<?php $__env->startComponent('mail::message'); ?>

<div style="text-align:center;margin-bottom:28px;">
    <img src="<?php echo new \Illuminate\Support\EncodedHtmlString(asset('images/party-legacy-logo.svg')); ?>"
         alt="Party Legacy"
         width="200"
         height="40"
         style="display:inline-block;">
</div>

# Prenotazione confermata

Ciao **<?php echo new \Illuminate\Support\EncodedHtmlString($booking->vendorAccount->company_name); ?>**,

Hai confermato con successo la prenotazione. Il cliente è stato notificato. Di seguito i dati di contatto del cliente per eventuali comunicazioni.

---

## Dettagli prenotazione

| | |
|---|---|
| **Servizio** | <?php echo new \Illuminate\Support\EncodedHtmlString($booking->offering->name ?? '—'); ?> |
| **Data evento** | <?php echo new \Illuminate\Support\EncodedHtmlString($booking->event_date->format('d/m/Y')); ?> |
| **Fascia oraria** | <?php echo new \Illuminate\Support\EncodedHtmlString(optional($booking->vendorSlot)->label ?? '—'); ?> |
| **Importo** | <?php echo new \Illuminate\Support\EncodedHtmlString(number_format($booking->total_amount, 2, ',', '.')); ?> <?php echo new \Illuminate\Support\EncodedHtmlString($booking->currency); ?> |

---

## Contatti del cliente

| | |
|---|---|
| **Nome** | <?php echo new \Illuminate\Support\EncodedHtmlString(data_get($booking->customer_data, 'firstname')); ?> <?php echo new \Illuminate\Support\EncodedHtmlString(data_get($booking->customer_data, 'lastname')); ?> |
| **Email** | <?php echo new \Illuminate\Support\EncodedHtmlString(data_get($booking->customer_data, 'email')); ?> |

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($booking->vendor_notes): ?>

---

## Le tue note

> <?php echo new \Illuminate\Support\EncodedHtmlString($booking->vendor_notes); ?>


<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

---

<?php $__env->startComponent('mail::button', ['url' => route('login') . '?redirect=' . urlencode(route('vendor.bookings.show', $booking)), 'color' => 'green']); ?>
Visualizza prenotazione
<?php echo $__env->renderComponent(); ?>

A presto,<br>
Il team di **Party Legacy**

<?php echo $__env->renderComponent(); ?><?php /**PATH C:\laragon\www\b2b.partylegacy.it\resources\views/emails/bookings/prenotazione-confermata-vendor.blade.php ENDPATH**/ ?>