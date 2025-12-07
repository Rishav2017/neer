@extends('layouts.admin')

@section('title', 'Categories')

@section('content')
<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-white">Categories</h1>
        <a href="{{ route('admin.categories.create') }}" class="flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add Category
        </a>
    </div>

    @if($categories->isEmpty())
        <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-12 text-center">
            <svg class="w-16 h-16 text-gray-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
            <h3 class="text-lg font-medium text-white mb-2">No categories yet</h3>
            <p class="text-gray-400 mb-4">Get started by creating your first category.</p>
            <a href="{{ route('admin.categories.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Category
            </a>
        </div>
    @else
        <div class="space-y-4">
            @foreach($categories as $category)
                <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg overflow-hidden">
                    <!-- Level 0: Top-Level Category -->
                    <div class="flex items-center justify-between p-4 border-b border-[#3E3E3A]">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-blue-600/20 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-white">{{ $category->name }}</h3>
                                @if($category->description)
                                    <p class="text-sm text-gray-400">{{ Str::limit($category->description, 100) }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-xs text-gray-500 mr-4">{{ $category->subcategories->count() }} subcategories</span>
                            <a href="{{ route('admin.categories.create', ['parent_id' => $category->id]) }}" class="p-2 text-green-400 hover:bg-green-600/20 rounded-lg transition-colors" title="Add Subcategory">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </a>
                            <a href="{{ route('admin.categories.edit', $category) }}" class="p-2 text-blue-400 hover:bg-blue-600/20 rounded-lg transition-colors" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </a>
                            <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-2 text-red-400 hover:bg-red-600/20 rounded-lg transition-colors" title="Delete">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Level 1: Subcategories -->
                    @if($category->subcategories->isNotEmpty())
                        <div class="bg-[#0a0a0a]/50">
                            @foreach($category->subcategories as $subcategory)
                                <div class="border-b border-[#3E3E3A] last:border-b-0">
                                    <!-- Subcategory Header -->
                                    <div class="flex items-center justify-between px-4 py-3 hover:bg-[#1f1f1e] transition-colors">
                                        <div class="flex items-center space-x-3 pl-8">
                                            <div class="w-8 h-8 bg-purple-600/20 rounded-lg flex items-center justify-center">
                                                <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <h4 class="text-sm font-medium text-white">{{ $subcategory->name }}</h4>
                                                @if($subcategory->description)
                                                    <p class="text-xs text-gray-500">{{ Str::limit($subcategory->description, 80) }}</p>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-xs text-gray-500 mr-4">{{ $subcategory->subcategories->count() }} sub-subcategories</span>
                                            <a href="{{ route('admin.categories.create', ['parent_id' => $subcategory->id]) }}" class="p-2 text-green-400 hover:bg-green-600/20 rounded-lg transition-colors" title="Add Sub-subcategory">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                </svg>
                                            </a>
                                            <a href="{{ route('admin.categories.edit', $subcategory) }}" class="p-2 text-blue-400 hover:bg-blue-600/20 rounded-lg transition-colors" title="Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>
                                            <form action="{{ route('admin.categories.destroy', $subcategory) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this subcategory?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-2 text-red-400 hover:bg-red-600/20 rounded-lg transition-colors" title="Delete">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- Level 2: Sub-subcategories -->
                                    @if($subcategory->subcategories->isNotEmpty())
                                        <div class="bg-[#0a0a0a]/80">
                                            @foreach($subcategory->subcategories as $subSubcategory)
                                                <div class="flex items-center justify-between px-4 py-2 border-t border-[#2a2a28] hover:bg-[#1a1a19] transition-colors">
                                                    <div class="flex items-center space-x-3 pl-16">
                                                        <div class="w-6 h-6 bg-teal-600/20 rounded flex items-center justify-center">
                                                            <svg class="w-3 h-3 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                                            </svg>
                                                        </div>
                                                        <div>
                                                            <h5 class="text-xs font-medium text-white">{{ $subSubcategory->name }}</h5>
                                                            @if($subSubcategory->description)
                                                                <p class="text-xs text-gray-600">{{ Str::limit($subSubcategory->description, 60) }}</p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center space-x-2">
                                                        <span class="text-xs text-gray-500 mr-4">{{ $subSubcategory->products->count() }} products</span>
                                                        <a href="{{ route('admin.categories.edit', $subSubcategory) }}" class="p-1.5 text-blue-400 hover:bg-blue-600/20 rounded transition-colors" title="Edit">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                            </svg>
                                                        </a>
                                                        <form action="{{ route('admin.categories.destroy', $subSubcategory) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this sub-subcategory?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="p-1.5 text-red-400 hover:bg-red-600/20 rounded transition-colors" title="Delete">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
