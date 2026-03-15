<div class="max-w-6xl mx-auto">
    <div class="bg-white shadow-sm rounded-lg p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold">
                    Prenotazioni
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Gestisci le richieste dei clienti: in attesa e confermate.
                </p>
            </div>
        </div>

        
        <div class="bg-white shadow rounded-lg mt-6">

            
            <div class="border-b border-slate-200 px-6">
                <nav class="-mb-px flex gap-8" aria-label="Tabs">

                    <button type="button" wire:click="setTab('pending')"
                        class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition <?php echo e($tab === 'pending' ? 'border-slate-800 text-slate-900' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'); ?>">
                        In attesa
                    </button>


                    <button type="button" wire:click="setTab('confirmed')"
                        class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition <?php echo e($tab === 'confirmed' ? 'border-slate-800 text-slate-900' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'); ?>">
                        Confermate
                    </button>

                </nav>
            </div>

            
            <div class="p-4">

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($tab === 'pending'): ?>
                    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('vendor.bookings.vendor-bookings-list', ['status' => 'PENDING_VENDOR_CONFIRMATION']);

$key = 'pending';

$key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-3972905480-0', 'pending');

$__html = app('livewire')->mount($__name, $__params, $key);

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
                <?php elseif($tab === 'confirmed'): ?>
                    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('vendor.bookings.vendor-bookings-list', ['status' => 'CONFIRMED']);

$key = 'confirmed';

$key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-3972905480-1', 'confirmed');

$__html = app('livewire')->mount($__name, $__params, $key);

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            </div>

        </div>
    </div>
</div>
<?php /**PATH C:\laragon\www\b2b.partylegacy.it\resources\views/livewire/vendor/bookings/vendor-bookings-tabs.blade.php ENDPATH**/ ?>