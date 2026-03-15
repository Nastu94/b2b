<div class="min-h-screen bg-gray-50 flex items-center justify-center px-6">

    <div class="w-full max-w-6xl ">

        
        <div class="text-center mb-8">
            <?php echo e($logo); ?>


            <div class="mt-2 text-sm text-gray-600">
                <?php echo e($title); ?>

            </div>
        </div>

        
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 mt-5 ">
            <?php echo e($slot); ?>

        </div>

        
        <div class="mt-6 flex items-center justify-between text-xs text-gray-500">
            <a href="<?php echo e(route('home')); ?>"
               class="text-gray-600 hover:text-gray-900 transition">
                Torna alla home
            </a>
        </div>

    </div>

</div><?php /**PATH C:\laragon\www\b2b.partylegacy.it\resources\views/components/authentication-card.blade.php ENDPATH**/ ?>