<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

        <title><?php echo e(config('app.name', 'Laravel')); ?></title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex w-full pt-6 sm:pt-0 bg-gradient-to-r from-gray-50 from-10% via-gray-100 via-50% to-gray-200 to-100% dark:bg-gray-900">
           <?php echo e($slot); ?>

        </div>
    </body>
</html>
<?php /**PATH /Users/togruljalalli/Desktop/projects/HRM/resources/views/layouts/guest.blade.php ENDPATH**/ ?>