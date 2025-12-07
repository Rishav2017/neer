@extends('layouts.admin')

@section('title', 'Edit Category')

@section('content')
<div>
    <div class="mb-6">
        <a href="{{ route('admin.categories.index') }}" class="inline-flex items-center text-gray-400 hover:text-white transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Categories
        </a>
    </div>

    @php
        $typeNames = ['Category', 'Subcategory', 'Sub-subcategory'];
        $typeName = $typeNames[$category->level] ?? 'Category';
    @endphp

    <h1 class="text-3xl font-bold text-white mb-6">Edit {{ $typeName }}</h1>

    {{-- Show current hierarchy path --}}
    @if($category->level > 0)
        <div class="mb-6 flex items-center text-sm">
            <span class="text-gray-500">Location:</span>
            <span class="ml-2">
                @if($category->level === 1)
                    <span class="text-blue-400">{{ $category->parent->name }}</span>
                    <span class="text-gray-500 mx-1">&gt;</span>
                    <span class="text-purple-400 font-medium">{{ $category->name }}</span>
                @elseif($category->level === 2)
                    <span class="text-blue-400">{{ $category->parent->parent->name }}</span>
                    <span class="text-gray-500 mx-1">&gt;</span>
                    <span class="text-purple-400">{{ $category->parent->name }}</span>
                    <span class="text-gray-500 mx-1">&gt;</span>
                    <span class="text-teal-400 font-medium">{{ $category->name }}</span>
                @endif
            </span>
        </div>
    @endif

    <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-6 max-w-2xl">
        <form action="{{ route('admin.categories.update', $category) }}" method="POST">
            @csrf
            @method('PUT')

            {{-- Parent selection based on category level --}}
            @if($category->level === 0)
                {{-- Top-level category cannot have a parent --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-400 mb-2">Category Level</label>
                    <div class="px-4 py-3 bg-[#0a0a0a] border border-[#3E3E3A] rounded-lg text-blue-400">
                        Top-level Category
                    </div>
                    <p class="mt-2 text-xs text-gray-500">
                        Top-level categories cannot be moved under other categories.
                    </p>
                </div>
            @elseif($category->level === 1)
                {{-- Subcategory - can change parent top-level category --}}
                <div class="mb-6">
                    <label for="parent_id" class="block text-sm font-medium text-gray-400 mb-2">Parent Category</label>
                    <select name="parent_id" id="parent_id" class="w-full px-4 py-3 bg-[#0a0a0a] border border-[#3E3E3A] rounded-lg text-white focus:outline-none focus:border-blue-500 transition-colors">
                        @foreach($topLevelCategories as $topLevel)
                            <option value="{{ $topLevel->id }}" {{ old('parent_id', $category->parent_id) == $topLevel->id ? 'selected' : '' }}>
                                {{ $topLevel->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('parent_id')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            @else
                {{-- Sub-subcategory - can change parent subcategory --}}
                <div class="mb-6">
                    <label for="parent_id" class="block text-sm font-medium text-gray-400 mb-2">Parent Subcategory</label>
                    <select name="parent_id" id="parent_id" class="w-full px-4 py-3 bg-[#0a0a0a] border border-[#3E3E3A] rounded-lg text-white focus:outline-none focus:border-blue-500 transition-colors">
                        @foreach($subCategories as $subCat)
                            <option value="{{ $subCat->id }}" {{ old('parent_id', $category->parent_id) == $subCat->id ? 'selected' : '' }}>
                                {{ $subCat->parent->name }} &gt; {{ $subCat->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('parent_id')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            {{-- Warning if category has children --}}
            @if($category->subcategories->count() > 0)
                <div class="mb-6 p-4 bg-yellow-600/10 border border-yellow-600/30 rounded-lg">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-yellow-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                            <h4 class="text-sm font-medium text-yellow-400">This category has children</h4>
                            <p class="text-xs text-yellow-400/80 mt-1">
                                This {{ strtolower($typeName) }} has {{ $category->subcategories->count() }}
                                {{ $category->level === 0 ? 'subcategories' : 'sub-subcategories' }}.
                                Moving it may affect the category hierarchy.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Warning if sub-subcategory has products --}}
            @if($category->level === 2 && $category->products->count() > 0)
                <div class="mb-6 p-4 bg-blue-600/10 border border-blue-600/30 rounded-lg">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-blue-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <h4 class="text-sm font-medium text-blue-400">Products in this category</h4>
                            <p class="text-xs text-blue-400/80 mt-1">
                                This sub-subcategory has {{ $category->products->count() }} products.
                                They will remain associated with this category if you move it.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="mb-6">
                <label for="name" class="block text-sm font-medium text-gray-400 mb-2">Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}" required
                    class="w-full px-4 py-3 bg-[#0a0a0a] border border-[#3E3E3A] rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 transition-colors"
                    placeholder="Enter category name">
                @error('name')
                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="description" class="block text-sm font-medium text-gray-400 mb-2">Description</label>
                <textarea name="description" id="description" rows="4"
                    class="w-full px-4 py-3 bg-[#0a0a0a] border border-[#3E3E3A] rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 transition-colors resize-none"
                    placeholder="Enter category description (optional)">{{ old('description', $category->description) }}</textarea>
                @error('description')
                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center space-x-4">
                <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                    Update {{ $typeName }}
                </button>
                <a href="{{ route('admin.categories.index') }}" class="px-6 py-3 bg-[#3E3E3A] hover:bg-[#4a4a47] text-white font-medium rounded-lg transition-colors">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
