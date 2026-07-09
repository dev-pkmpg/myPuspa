<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'myPuspa') }} — {{ $title ?? 'Dashboard' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-50 min-h-screen">
    <x-sidebar />
    <div class="pl-64 min-h-screen flex flex-col">
        <header class="h-16 bg-white border-b border-gray-200 flex items-center px-6">
            <h1 class="text-lg font-semibold text-gray-800">{{ $title ?? 'Dashboard' }}</h1>
        </header>
        <main class="flex-1 p-6">
            {{ $slot }}
        </main>
    </div>
    @livewireScripts
</body>
</html>
