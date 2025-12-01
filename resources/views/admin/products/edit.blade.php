@extends('layouts.admin')

@section('title', 'Edit Product')

@section('content')
<div>
    <div class="mb-6">
        <a href="{{ route('admin.products.index') }}" class="inline-flex items-center text-gray-400 hover:text-white transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Products
        </a>
    </div>

    <h1 class="text-3xl font-bold text-white mb-6">Edit Product</h1>

    <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-6 max-w-2xl">
        <form action="{{ route('admin.products.update', $product) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-6">
                <label for="sub_category_id" class="block text-sm font-medium text-gray-400 mb-2">Subcategory *</label>
                <select name="sub_category_id" id="sub_category_id" required
                    class="w-full px-4 py-3 bg-[#0a0a0a] border border-[#3E3E3A] rounded-lg text-white focus:outline-none focus:border-blue-500 transition-colors">
                    <option value="">Select a subcategory</option>
                    @foreach($categories as $category)
                        @if($category->subcategories->isNotEmpty())
                            <optgroup label="{{ $category->name }}">
                                @foreach($category->subcategories as $subcategory)
                                    <option value="{{ $subcategory->id }}" {{ old('sub_category_id', $product->sub_category_id) == $subcategory->id ? 'selected' : '' }}>
                                        {{ $subcategory->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                    @endforeach
                </select>
                @error('sub_category_id')
                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="name" class="block text-sm font-medium text-gray-400 mb-2">Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name', $product->name) }}" required
                    class="w-full px-4 py-3 bg-[#0a0a0a] border border-[#3E3E3A] rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 transition-colors"
                    placeholder="Enter product name">
                @error('name')
                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="description" class="block text-sm font-medium text-gray-400 mb-2">Description</label>
                <textarea name="description" id="description" rows="4"
                    class="w-full px-4 py-3 bg-[#0a0a0a] border border-[#3E3E3A] rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 transition-colors resize-none"
                    placeholder="Enter product description (optional)">{{ old('description', $product->description) }}</textarea>
                @error('description')
                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="price" class="block text-sm font-medium text-gray-400 mb-2">Price *</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">$</span>
                        <input type="number" name="price" id="price" value="{{ old('price', $product->price) }}" required min="0" step="0.01"
                            class="w-full pl-8 pr-4 py-3 bg-[#0a0a0a] border border-[#3E3E3A] rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 transition-colors"
                            placeholder="0.00">
                    </div>
                    @error('price')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="stock_quantity" class="block text-sm font-medium text-gray-400 mb-2">Stock Quantity *</label>
                    <input type="number" name="stock_quantity" id="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity) }}" required min="0"
                        class="w-full px-4 py-3 bg-[#0a0a0a] border border-[#3E3E3A] rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 transition-colors"
                        placeholder="0">
                    @error('stock_quantity')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mb-6">
                <label for="image_url" class="block text-sm font-medium text-gray-400 mb-2">Image URL</label>
                <input type="url" name="image_url" id="image_url" value="{{ old('image_url', $product->image_url) }}"
                    class="w-full px-4 py-3 bg-[#0a0a0a] border border-[#3E3E3A] rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 transition-colors"
                    placeholder="https://example.com/image.jpg">
                @error('image_url')
                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                @enderror
                @if($product->image_url)
                    <div class="mt-3">
                        <p class="text-sm text-gray-400 mb-2">Current Image:</p>
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-24 h-24 rounded-lg object-cover">
                    </div>
                @endif
            </div>

            <div class="flex items-center space-x-4">
                <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                    Update Product
                </button>
                <a href="{{ route('admin.products.index') }}" class="px-6 py-3 bg-[#3E3E3A] hover:bg-[#4a4a47] text-white font-medium rounded-lg transition-colors">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
