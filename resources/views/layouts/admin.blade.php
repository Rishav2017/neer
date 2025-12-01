<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Admin Dashboard') - {{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-[#0a0a0a] text-white">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-64 bg-[#161615] border-r border-[#3E3E3A] flex flex-col">
            <div class="flex-1 overflow-y-auto py-4">
                <nav class="space-y-1 px-3">
                    @php
                        $currentRoute = request()->route()->getName();
                        $navItems = [
                            ['route' => 'admin.dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'label' => 'Dashboard'],
                            ['route' => 'admin.users', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'label' => 'Users'],
                            ['route' => 'admin.orders', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'label' => 'Orders'],
                            ['route' => 'admin.products', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'label' => 'Products'],
                            ['route' => 'admin.categories', 'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z', 'label' => 'Categories'],
                        ];
                    @endphp

                    @foreach($navItems as $item)
                        @php
                            $isActive = $currentRoute === $item['route'];
                        @endphp
                        <a href="{{ route($item['route']) }}" 
                           class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ $isActive ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-[#1f1f1e] hover:text-white' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}" />
                            </svg>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-[#161615] border-b border-[#3E3E3A] px-6 py-4">
                <div class="flex items-center justify-between">
                    <!-- Logo -->
                    <div class="flex items-center">
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-8 rounded flex items-center justify-center bg-gradient-to-br from-red-500 via-green-500 to-blue-500">
                                <span class="text-white font-bold text-sm">RN</span>
                            </div>
                            <span class="text-xl font-bold text-white">{{ config('app.name', 'Admin') }}</span>
                        </div>
                    </div>

                    <!-- User Info & Actions -->
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-full bg-[#3E3E3A] flex items-center justify-center">
                                <span class="text-white font-semibold text-sm">
                                    {{ strtoupper(substr(auth()->user()->name ?? auth()->user()->phone, 0, 2)) }}
                                </span>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-medium text-white">
                                    {{ auth()->user()->name ?? 'Admin User' }}
                                </div>
                                <div class="text-xs text-gray-400">
                                    {{ auth()->user()->email ?? auth()->user()->phone }}
                                </div>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('admin.logout') }}" class="ml-4">
                            @csrf
                            <button type="submit" class="flex items-center px-4 py-2 bg-[#3E3E3A] hover:bg-[#4a4a47] text-white text-sm font-medium rounded-lg transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Exit Admin
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto bg-[#0a0a0a] p-6">
                @if(session('error'))
                    <div class="mb-4 bg-red-900/20 border border-red-800 text-red-200 px-4 py-3 rounded-lg">
                        {{ session('error') }}
                    </div>
                @endif

                @if(session('success'))
                    <div class="mb-4 bg-green-900/20 border border-green-800 text-green-200 px-4 py-3 rounded-lg">
                        {{ session('success') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
