
<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <title><?php echo e($title ?? auth()->user()?->name); ?> - Party Legacy</title>

    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>

</head>

<body class="h-screen overflow-hidden bg-slate-100 text-slate-800 antialiased">
    <div class="h-screen w-full flex overflow-hidden">

        
        <?php
            $is = fn($name) => request()->routeIs($name);
        ?>

        <aside class="w-64 shrink-0 pl-sidebar flex flex-col">

            
            <div class="p-6 border-b border-slate-200">
                <div class="text-lg pl-sidebar-brand">
                    Party Legacy
                </div>
                <div class="text-sm text-slate-500">
                    Vendor Panel
                </div>
            </div>

            
            <nav class="p-4 space-y-1 flex-1">
                <a href="<?php echo e(route('vendor.dashboard')); ?>"
                    class="pl-sidebar-link <?php echo e($is('vendor.dashboard') ? 'pl-sidebar-link-active' : ''); ?>">
                    Dashboard
                </a>

                <a href="<?php echo e(route('vendor.profile')); ?>"
                    class="pl-sidebar-link <?php echo e($is('vendor.profile') ? 'pl-sidebar-link-active' : ''); ?>">
                    Profilo
                </a>

                <a href="<?php echo e(route('vendor.offerings')); ?>"
                    class="pl-sidebar-link <?php echo e($is('vendor.offerings') ? 'pl-sidebar-link-active' : ''); ?>">
                    Servizi
                </a>

                <a href="<?php echo e(route('vendor.pricings')); ?>"
                    class="pl-sidebar-link <?php echo e($is('vendor.pricings') ? 'pl-sidebar-link-active' : ''); ?>">
                    Listini
                </a>

                <a href="<?php echo e(route('vendor.bookings')); ?>"
                    class="pl-sidebar-link <?php echo e($is('vendor.bookings') ? 'pl-sidebar-link-active' : ''); ?>">
                    Prenotazioni
                </a>
            </nav>

            
            <div class="p-4 border-t border-slate-800">
                <div class="text-sm text-slate-300">
                    <?php echo e(auth()->user()?->name); ?>

                </div>

                <form method="POST" action="<?php echo e(route('logout')); ?>" class="mt-3">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="pl-btn-logout">
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        
        <div class="flex-1 flex flex-col min-w-0">

            
            <header class="h-16 shrink-0 pl-topbar flex items-center justify-between px-6">
                <div>
                    <div class="text-xs pl-topbar-muted">Vendor</div>
                    <div class="text-lg pl-topbar-title">
                        <?php echo e($title ?? 'Dashboard'); ?>

                    </div>
                </div>

                <div class="text-sm pl-topbar-muted">
                    Party Legacy Management Engine
                </div>
            </header>

            
            <main class="flex-1 p-6 overflow-y-auto scrollbar-hide">
                <div class="max-w-7xl mx-auto">
                    <?php echo e($slot); ?>

                </div>
            </main>

        </div>
    </div>

    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>

</body>

</html>
<?php /**PATH C:\laragon\www\b2b.partylegacy.it\resources\views/layouts/vendor.blade.php ENDPATH**/ ?>