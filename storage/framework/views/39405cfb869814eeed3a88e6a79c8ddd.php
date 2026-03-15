<div class="space-y-6">

    
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">
                Vendor Dashboard
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                Panoramica del tuo profilo e dei servizi attivi.
            </p>
        </div>
    </div>

    
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <div class="text-xs uppercase text-slate-500 tracking-wide">
                Stato account
            </div>
            <div class="mt-2 text-lg font-semibold text-slate-900">
                <?php echo e($status === 'ACTIVE' ? 'Attivo' : 'Inattivo'); ?>

            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <div class="text-xs uppercase text-slate-500 tracking-wide">
                Categoria
            </div>
            <div class="mt-2 text-lg font-semibold text-slate-900">
                <?php echo e($categoryName); ?>

            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <div class="text-xs uppercase text-slate-500 tracking-wide">
                Servizi attivi
            </div>
            <div class="mt-2 text-lg font-semibold text-slate-900">
                <?php echo e(count($activeOfferings)); ?>

            </div>
        </div>

    </div>

    
    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">

        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">
                Servizi attivi
            </h2>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(empty($activeOfferings)): ?>
            <div class="mt-4 text-sm text-slate-500">
                Nessun servizio selezionato.
                <a href="<?php echo e(route('vendor.offerings')); ?>" class="underline text-slate-900">
                    Seleziona servizi
                </a>
            </div>
        <?php else: ?>
            <?php
                $publishedOfferings = array_values(
                    array_filter($activeOfferings, fn($o) => (bool) ($o['is_published'] ?? false)),
                );
                $draftOfferings = array_values(
                    array_filter($activeOfferings, fn($o) => !(bool) ($o['is_published'] ?? false)),
                );
            ?>

            <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4">

                
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-slate-900">Servizi pubblicati</h3>
                        <span class="text-xs text-slate-500"><?php echo e(count($publishedOfferings)); ?></span>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $publishedOfferings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $o): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <span class="px-3 py-1 rounded-full text-sm bg-emerald-50 border border-emerald-200 text-emerald-800">
                                <?php echo e($o['name']); ?>

                            </span>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <p class="text-sm text-slate-500">Nessun servizio pubblicato.</p>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>

                
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-slate-900">Servizi in bozza</h3>
                        <span class="text-xs text-slate-500"><?php echo e(count($draftOfferings)); ?></span>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $draftOfferings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $o): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <span class="px-3 py-1 rounded-full text-sm bg-amber-50 border border-amber-200 text-amber-800">
                                <?php echo e($o['name']); ?>

                            </span>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <p class="text-sm text-slate-500">Nessuna bozza.</p>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>

            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    </div>

    
    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">

        <h2 class="text-lg font-semibold text-slate-900 mb-4">
            Riepilogo disponibilità
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

            
            <div class="border border-slate-200 rounded-xl p-4">
                <div class="text-xs uppercase text-slate-500 tracking-wide">Slot configurati</div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($slotsCount > 0): ?>
                    <div class="mt-2 text-2xl font-semibold text-slate-900"><?php echo e($slotsCount); ?></div>
                    <div class="mt-1 text-xs text-slate-400">
                        <?php echo e($slotsCount === 1 ? 'fascia oraria' : 'fasce orarie'); ?>

                    </div>
                <?php else: ?>
                    <div class="mt-2 text-sm text-amber-600 font-medium">Non configurati</div>
                    <div class="mt-1 text-xs text-slate-400">
                        <a href="<?php echo e(route('vendor.offerings')); ?>" class="underline">Vai a Servizi &rarr; Slot</a>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            
            <div class="border border-slate-200 rounded-xl p-4">
                <div class="text-xs uppercase text-slate-500 tracking-wide">Giorni aperti</div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($openDaysCount > 0): ?>
                    <div class="mt-2 text-2xl font-semibold text-slate-900"><?php echo e($openDaysCount); ?></div>
                    <div class="mt-1 text-xs text-slate-400">
                        <?php echo e(implode(', ', $openDayNames)); ?>

                    </div>
                <?php else: ?>
                    <div class="mt-2 text-sm text-amber-600 font-medium">Non configurati</div>
                    <div class="mt-1 text-xs text-slate-400">
                        <a href="<?php echo e(route('vendor.offerings')); ?>" class="underline">Vai a Template settimanale</a>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            
            <div class="border border-slate-200 rounded-xl p-4">
                <div class="text-xs uppercase text-slate-500 tracking-wide">Preavviso</div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($leadTimeConfigured): ?>
                    <div class="mt-2 text-sm font-semibold text-emerald-600">Configurato</div>
                    <div class="mt-1 text-xs text-slate-400">
                        <a href="<?php echo e(route('vendor.offerings')); ?>" class="underline">Modifica</a>
                    </div>
                <?php else: ?>
                    <div class="mt-2 text-sm text-amber-600 font-medium">Non configurato</div>
                    <div class="mt-1 text-xs text-slate-400">
                        <a href="<?php echo e(route('vendor.offerings')); ?>" class="underline">Vai a Lead time</a>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            
            <div class="border border-slate-200 rounded-xl p-4">
                <div class="text-xs uppercase text-slate-500 tracking-wide">Prossimi blocchi</div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($upcomingBlackouts) > 0): ?>
                    <div class="mt-2 space-y-1">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $upcomingBlackouts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="text-xs text-slate-700">
                                <span class="font-medium"><?php echo e($b['range']); ?></span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$b['full_day']): ?>
                                    <span class="text-slate-400"> — slot specifico</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="mt-2 text-sm text-slate-400">Nessun blocco</div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

        </div>

    </div>

</div><?php /**PATH C:\laragon\www\b2b.partylegacy.it\resources\views/livewire/vendor/dashboard/vendor-dashboard-page.blade.php ENDPATH**/ ?>