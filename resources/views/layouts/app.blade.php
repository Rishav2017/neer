<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Laravel'))</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC]">
    <div class="min-h-screen">
        @if(auth()->check())
            <nav class="bg-white dark:bg-[#161615] border-b border-[#e3e3e0] dark:border-[#3E3E3A]">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center">
                            <a href="{{ route('admin.dashboard') }}" class="text-xl font-semibold">
                                Admin Dashboard
                            </a>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                {{ auth()->user()->phone }}
                            </span>
                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button type="submit" class="text-sm text-[#f53003] dark:text-[#FF4433] hover:underline">
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </nav>
        @endif

        <main class="py-8">
            @if(session('error'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-4">
                    <div class="bg-[#fff2f2] dark:bg-[#1D0002] border border-[#f53003] dark:border-[#FF4433] text-[#f53003] dark:text-[#FF4433] px-4 py-3 rounded">
                        {{ session('error') }}
                    </div>
                </div>
            @endif

            @if(session('success'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-4">
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded">
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
