@extends('layouts.app')

@section('content')
    <!-- Header Section -->
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-800">Product List</h2>
        <a href="{{ route('products.create') }}" class="bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
          Add New Product
        </a>
      </div>

       @if (session('success'))
        <div class="mb-4 p-4 bg-green-100 text-green-700 rounded">
            {{ session('success') }}
        </div>
        @endif

        @if (session('error'))
        <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">
            {{ session('error') }}
        </div>
        @endif


      <!-- Search Bar -->
      <div class="mb-6">
        <form action="{{ route('products.search') }}" method="post" enctype="multipart/form-data">
            @csrf
            <input type="file" name="image" accept="image/*" onchange="this.form.submit()" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            @error('image')
                <div class="text-red-500" >{{ $message }}</div>
            @enderror
        </form>
      </div>

      <!-- Smart Table -->
      <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200 rounded-lg shadow-md">
          <thead class="bg-gray-100">
            <tr>
              <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Image</th>
              <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Name</th>
              <th class="py-3 px-4 text-left text-sm font-medium text-gray-700">Price</th>
            </tr>
          </thead>
          <tbody id="productTableBody" class="divide-y divide-gray-200">
            @forelse ($products as $product)
                <tr>
                    <td class="py-4 px-4">
                        <img src="{{ '/storage/' . $product->image_path }}" alt="Laptop" class="w-10 h-10 object-cover rounded">
                    </td>
                    <td class="py-4 px-4 text-sm text-gray-700">{{ $product->name }}</td>
                    <td class="py-4 px-4 text-sm text-gray-700">{{ $product->price }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="py-4 px-4 text-sm text-gray-700 text-center">No products found.</td>
                </tr>
            @endforelse
          </tbody>
        </table>
      </div>
@endsection
