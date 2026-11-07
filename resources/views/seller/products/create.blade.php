@php($editing = $product->exists)
<x-seller.product-layout :title="$editing ? 'Edit Product' : 'Add Product'">
    <x-slot:actions><a class="btn-soft" href="{{ route('seller.products.index') }}">All Products</a></x-slot:actions>
    <x-seller.product-form :product="$product" :categories="$categories" />
</x-seller.product-layout>
