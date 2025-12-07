@extends('layouts.admin')

@section('title', 'Products')

@section('content')
<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-white">Products</h1>
        <a href="{{ route('admin.products.create') }}" class="flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add Product
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-4 mb-6">
        <form action="{{ route('admin.products.index') }}" method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                    class="w-full px-4 py-2 bg-[#0a0a0a] border border-[#3E3E3A] rounded-lg text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 transition-colors"
                    placeholder="Search products...">
            </div>

            <!-- Level 0: Category Filter -->
            <div class="w-48">
                <select name="category_id" id="category_filter"
                    class="w-full px-4 py-2 bg-[#0a0a0a] border border-[#3E3E3A] rounded-lg text-white focus:outline-none focus:border-blue-500 transition-colors">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Level 1: Subcategory Filter -->
            <div class="w-48">
                <select name="sub_category_id" id="subcategory_filter"
                    class="w-full px-4 py-2 bg-[#0a0a0a] border border-[#3E3E3A] rounded-lg text-white focus:outline-none focus:border-blue-500 transition-colors">
                    <option value="">All Subcategories</option>
                    @foreach($categories as $category)
                        @foreach($category->subcategories as $subcategory)
                            <option value="{{ $subcategory->id }}"
                                data-parent="{{ $category->id }}"
                                {{ request('sub_category_id') == $subcategory->id ? 'selected' : '' }}>
                                {{ $category->name }} &gt; {{ $subcategory->name }}
                            </option>
                        @endforeach
                    @endforeach
                </select>
            </div>

            <!-- Level 2: Sub-subcategory Filter -->
            <div class="w-56">
                <select name="sub_sub_category_id" id="subsubcategory_filter"
                    class="w-full px-4 py-2 bg-[#0a0a0a] border border-[#3E3E3A] rounded-lg text-white focus:outline-none focus:border-blue-500 transition-colors">
                    <option value="">All Sub-subcategories</option>
                    @foreach($categories as $category)
                        @foreach($category->subcategories as $subcategory)
                            @foreach($subcategory->subcategories as $subSubcategory)
                                <option value="{{ $subSubcategory->id }}"
                                    data-parent="{{ $subcategory->id }}"
                                    data-grandparent="{{ $category->id }}"
                                    {{ request('sub_sub_category_id') == $subSubcategory->id ? 'selected' : '' }}>
                                    {{ $category->name }} &gt; {{ $subcategory->name }} &gt; {{ $subSubcategory->name }}
                                </option>
                            @endforeach
                        @endforeach
                    @endforeach
                </select>
            </div>

            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                Filter
            </button>
            @if(request()->hasAny(['search', 'category_id', 'sub_category_id', 'sub_sub_category_id']))
                <a href="{{ route('admin.products.index') }}" class="px-4 py-2 bg-[#3E3E3A] hover:bg-[#4a4a47] text-white font-medium rounded-lg transition-colors">
                    Clear
                </a>
            @endif
        </form>
    </div>

    @if($products->isEmpty())
        <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg p-12 text-center">
            <svg class="w-16 h-16 text-gray-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
            <h3 class="text-lg font-medium text-white mb-2">No products found</h3>
            <p class="text-gray-400 mb-4">
                @if(request()->hasAny(['search', 'category_id', 'sub_category_id', 'sub_sub_category_id']))
                    No products match your filters. Try adjusting your search criteria.
                @else
                    Get started by creating your first product.
                @endif
            </p>
            <a href="{{ route('admin.products.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Product
            </a>
        </div>
    @else
        <div class="bg-[#161615] border border-[#3E3E3A] rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[#3E3E3A]">
                    <thead class="bg-[#0a0a0a]">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Category Path</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Stock</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#3E3E3A]">
                        @foreach($products as $product)
                            <tr class="hover:bg-[#1f1f1e] transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center space-x-3">
                                        @if($product->image_url)
                                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-10 h-10 rounded-lg object-cover">
                                        @else
                                            <div class="w-10 h-10 bg-purple-600/20 rounded-lg flex items-center justify-center">
                                                <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                </svg>
                                            </div>
                                        @endif
                                        <div>
                                            <div class="text-sm font-medium text-white">{{ $product->name }}</div>
                                            @if($product->description)
                                                <div class="text-xs text-gray-500">{{ Str::limit($product->description, 50) }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-xs space-y-0.5">
                                        @if($product->subcategory)
                                            <div class="flex items-center flex-wrap gap-1">
                                                @if($product->subcategory->parent && $product->subcategory->parent->parent)
                                                    <span class="text-blue-400">{{ $product->subcategory->parent->parent->name }}</span>
                                                    <span class="text-gray-600">&gt;</span>
                                                @endif
                                                @if($product->subcategory->parent)
                                                    <span class="text-purple-400">{{ $product->subcategory->parent->name }}</span>
                                                    <span class="text-gray-600">&gt;</span>
                                                @endif
                                                <span class="text-teal-400">{{ $product->subcategory->name }}</span>
                                            </div>
                                        @else
                                            <span class="text-gray-500">N/A</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-green-400">${{ number_format($product->price, 2) }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($product->stock_quantity > 10)
                                        <span class="px-2 py-1 text-xs rounded-full bg-green-600/20 text-green-300 border border-green-600/30">
                                            {{ $product->stock_quantity }} in stock
                                        </span>
                                    @elseif($product->stock_quantity > 0)
                                        <span class="px-2 py-1 text-xs rounded-full bg-yellow-600/20 text-yellow-300 border border-yellow-600/30">
                                            {{ $product->stock_quantity }} left
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs rounded-full bg-red-600/20 text-red-300 border border-red-600/30">
                                            Out of stock
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="{{ route('admin.products.edit', $product) }}" class="p-2 text-blue-400 hover:bg-blue-600/20 rounded-lg transition-colors" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                        <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 text-red-400 hover:bg-red-600/20 rounded-lg transition-colors" title="Delete">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if($products->hasPages())
            <div class="mt-6">
                {{ $products->withQueryString()->links() }}
            </div>
        @endif
    @endif
</div>

@push('scripts')
<script>
    // Cascade filter dropdowns
    document.addEventListener('DOMContentLoaded', function() {
        const categoryFilter = document.getElementById('category_filter');
        const subcategoryFilter = document.getElementById('subcategory_filter');
        const subsubcategoryFilter = document.getElementById('subsubcategory_filter');

        function filterOptions(selectElement, parentValue, dataAttr) {
            const options = selectElement.querySelectorAll('option');
            let firstVisible = null;

            options.forEach(option => {
                if (option.value === '') {
                    option.style.display = '';
                    return;
                }

                const parent = option.getAttribute(dataAttr);
                if (!parentValue || parent === parentValue) {
                    option.style.display = '';
                    if (!firstVisible) firstVisible = option;
                } else {
                    option.style.display = 'none';
                    if (option.selected) {
                        option.selected = false;
                        selectElement.querySelector('option[value=""]').selected = true;
                    }
                }
            });
        }

        if (categoryFilter) {
            categoryFilter.addEventListener('change', function() {
                filterOptions(subcategoryFilter, this.value, 'data-parent');
                filterOptions(subsubcategoryFilter, '', 'data-grandparent');
                subsubcategoryFilter.value = '';
            });
        }

        if (subcategoryFilter) {
            subcategoryFilter.addEventListener('change', function() {
                filterOptions(subsubcategoryFilter, this.value, 'data-parent');
            });
        }
    });
</script>
@endpush
@endsection
