<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - Operator Panel</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,900&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="font-sans antialiased bg-gray-100 min-h-screen flex flex-col {{ $bodyClass ?? '' }}">
    @if (isset($header))
        <header class="bg-white shadow z-10 sticky top-0">
            <div class="{{ $headerContainerClass ?? 'px-4 sm:px-6 lg:px-8 py-4' }}">
                {{ $header }}
            </div>
        </header>
    @endif

    <main class="flex-1 w-full {{ $mainClass ?? 'max-w-screen-2xl mx-auto p-4 sm:p-6 lg:p-8' }}">
        {{ $slot }}
    </main>
    
    @livewireScripts
</body>

</html>
