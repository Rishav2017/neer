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

    <h1 class="text-3xl font-bold text-white mb-6">
        {{ $parentId ? 'Create Subcategory' : 'Create Category' }}
    </h1>

    <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-6 max-w-2xl">
        <form action="{{ route('admin.categories.store') }}" method="POST">
            @csrf

            @if($parentId)
                <input type="hidden" name="parent_id" value="{{ $parentId }}">
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-400 mb-2">Parent Category</label>
                    <div class="px-4 py-3 bg-[#0a0a0a] border border-[#3E3E3A] rounded-lg text-white">
                        {{ $parentCategories->firstWhere('id', $parentId)?->name ?? 'Unknown' }}
                    </div>
                </div>
            @else
                <div class="mb-6">
                    <label for="parent_id" class="block text-sm font-medium text-gray-400 mb-2">Parent Category (optional)</label>
                    <select name="parent_id" id="parent_id" class="w-full px-4 py-3 bg-[#0a0a0a] border border-[#3E3E3A] rounded-lg text-white focus:outline-none focus:border-blue-500 transition-colors">
                        <option value="">None (Top-level category)</option>
                        @foreach($parentCategories as $parent)
                            <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                        @endforeach
                    </select>
                    @error('parent_id')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
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
                    Create {{ $parentId ? 'Subcategory' : 'Category' }}
                </button>
                <a href="{{ route('admin.categories.index') }}" class="px-6 py-3 bg-[#3E3E3A] hover:bg-[#4a4a47] text-white font-medium rounded-lg transition-colors">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
