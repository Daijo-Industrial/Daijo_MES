<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased" x-data="{ sidebarOpen: false }">
    <div class="min-h-screen bg-gray-100">

        {{-- Overlay untuk mobile ketika sidebar terbuka --}}
        <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 bg-black/40 z-20 lg:hidden"
            @click="sidebarOpen = false">
        </div>

        {{-- Sidebar (fixed di kiri) --}}
        <livewire:layout.sidebar />

        {{-- Main content, DORONG ke kanan sebesar lebar sidebar saat lg+ --}}
        <div class="flex-1 lg:pl-64 transition-all duration-300">

            {{-- Tombol toggle sidebar di mobile --}}
            <div class="lg:hidden flex items-center p-4">
                <button @click="sidebarOpen = true" class="text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        class="h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>

            <livewire:alert />

            @if (isset($header))
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <main>
                {{ $slot }}
            </main>
        </div>
    </div>
</body>

</html>
