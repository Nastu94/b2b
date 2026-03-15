<?php $__env->startComponent('mail::message'); ?>

<div style="text-align:center;margin-bottom:28px;">
    <img src="<?php echo new \Illuminate\Support\EncodedHtmlString(asset('images/party-legacy-logo.svg')); ?>"
         alt="Party Legacy"
         width="200"
         height="40"
         style="display:inline-block;">
</div>

# Il tuo evento è confermato!

Ciao **<?php echo new \Illuminate\Support\EncodedHtmlString(data_get($booking->customer_data, 'firstname')); ?>**,

Ottima notizia! Il fornitore ha confermato la tua prenotazione tramite **Party Legacy**. Ecco tutti i dettagli per il tuo evento.

---

## Riepilogo prenotazione

| | |
|---|---|
| **Servizio** | <?php echo new \Illuminate\Support\EncodedHtmlString($booking->offering->name ?? '—'); ?> |
| **Data evento** | <?php echo new \Illuminate\Support\EncodedHtmlString($booking->event_date->format('d/m/Y')); ?> |
| **Fascia oraria** | <?php echo new \Illuminate\Support\EncodedHtmlString(optional($booking->vendorSlot)->label ?? '—'); ?> |
| **Importo pagato** | <?php echo new \Illuminate\Support\EncodedHtmlString(number_format($booking->total_amount, 2, ',', '.')); ?> <?php echo new \Illuminate\Support\EncodedHtmlString($booking->currency); ?> |
| **Rif. ordine** | <?php echo new \Illuminate\Support\EncodedHtmlString($booking->prestashop_order_id); ?> |

---

## Contatti del fornitore

Puoi contattare direttamente il tuo fornitore per qualsiasi dettaglio organizzativo.

| | |
|---|---|
| **Azienda** | <?php echo new \Illuminate\Support\EncodedHtmlString($booking->vendorAccount->company_name); ?> |
| **Telefono** | <?php echo new \Illuminate\Support\EncodedHtmlString($booking->vendorAccount->phone ?? '—'); ?> |
| **Email** | <?php echo new \Illuminate\Support\EncodedHtmlString($booking->vendorAccount->billing_email ?? $booking->vendorAccount->pec_email ?? '—'); ?> |
| **Città** | <?php echo new \Illuminate\Support\EncodedHtmlString($booking->vendorAccount->effectiveCity() ?? '—'); ?> |

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($booking->vendor_notes): ?>

---

## Note del fornitore

> <?php echo new \Illuminate\Support\EncodedHtmlString($booking->vendor_notes); ?>


<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

---

Ti auguriamo un evento indimenticabile!

A presto,<br>
Il team di **Party Legacy**

<?php echo $__env->renderComponent(); ?><?php /**PATH C:\laragon\www\b2b.partylegacy.it\resources\views/emails/bookings/prenotazione-confermata.blade.php ENDPATH**/ ?>