@extends('layouts.admin')

@section('title', 'Create Category')

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
        $title = 'Create Category';
        $buttonText = 'Create Category';

        if ($parentCategory) {
            if ($parentCategory->level === 0) {
                $title = 'Create Subcategory';
                $buttonText = 'Create Subcategory';
            } else {
                $title = 'Create Sub-subcategory';
                $buttonText = 'Create Sub-subcategory';
            }
        }
    @endphp

    <h1 class="text-3xl font-bold text-white mb-6">{{ $title }}</h1>

    <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-6 max-w-2xl">
        <form action="{{ route('admin.categories.store') }}" method="POST">
            @csrf

            @if($parentCategory)
                {{-- Creating under a specific parent --}}
                <input type="hidden" name="parent_id" value="{{ $parentId }}">

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-400 mb-2">Parent Category</label>
                    <div class="px-4 py-3 bg-[#0a0a0a] border border-[#3E3E3A] rounded-lg text-white">
                        @if($parentCategory->level === 0)
                            <span class="text-blue-400">{{ $parentCategory->name }}</span>
                        @else
                            <span class="text-blue-400">{{ $parentCategory->parent->name }}</span>
                            <span class="text-gray-500 mx-2">&gt;</span>
                            <span class="text-purple-400">{{ $parentCategory->name }}</span>
                        @endif
                    </div>
                </div>
            @else
                {{-- Creating a new category - show parent selection --}}
                <div class="mb-6">
                    <label for="parent_id" class="block text-sm font-medium text-gray-400 mb-2">Parent Category (optional)</label>
                    <select name="parent_id" id="parent_id" class="w-full px-4 py-3 bg-[#0a0a0a] border border-[#3E3E3A] rounded-lg text-white focus:outline-none focus:border-blue-500 transition-colors">
                        <option value="">None (Top-level category)</option>

                        {{-- Level 0 categories (can create subcategories under them) --}}
                        @foreach($topLevelCategories as $topLevel)
                            <option value="{{ $topLevel->id }}" {{ old('parent_id') == $topLevel->id ? 'selected' : '' }}>
                                {{ $topLevel->name }}
                            </option>
                        @endforeach

                        {{-- Level 1 categories (can create sub-subcategories under them) --}}
                        @if($subCategories->isNotEmpty())
                            <optgroup label="Subcategories (creates sub-subcategory)">
                                @foreach($subCategories as $subCat)
                                    <option value="{{ $subCat->id }}" {{ old('parent_id') == $subCat->id ? 'selected' : '' }}>
                                        {{ $subCat->parent->name }} &gt; {{ $subCat->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>
                    @error('parent_id')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-xs text-gray-500">
                        Leave empty for a top-level category, or select a parent to create a subcategory or sub-subcategory.
                    </p>
                </div>
            @endif

            <div class="mb-6">
                <label for="name" class="block text-sm font-medium text-gray-400 mb-2">Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
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
                    placeholder="Enter category description (optional)">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center space-x-4">
                <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                    {{ $buttonText }}
                </button>
                <a href="{{ route('admin.categories.index') }}" class="px-6 py-3 bg-[#3E3E3A] hover:bg-[#4a4a47] text-white font-medium rounded-lg transition-colors">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
