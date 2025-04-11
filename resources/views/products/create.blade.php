@extends('layouts.app')

@section('content')
  <!-- Form -->
  <div class="container mx-auto p-6">

    <div class="bg-white p-8 rounded-lg shadow-md max-w-lg mx-auto">
        <h5>Weaviate Setup</h5>
        <p>Initialize Weaviate schema before adding products</p>
        <form method="POST" action="{{ route('weaviate.init') }}">
            @csrf
            <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Initialize Weaviate
            </button>
        </form>
        @if (session('weaviate_status'))
            <div class="alert alert-info mt-3">
                {{ session('weaviate_status') }}
            </div>
        @endif
    </div>
  </div>

    <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" class="bg-white p-8 rounded-lg shadow-md max-w-lg mx-auto">
      @csrf

      @if ($errors->any())
        <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">
          <ul class="list-disc list-inside text-sm">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

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


      <!-- Product Name -->
      <div class="mb-4">
        <label for="name" class="block text-sm font-medium text-gray-700">Product Name</label>
        <input type="text" id="name" name="name" value="{{ old('name') }}"
          class="mt-1 block w-full px-3 py-2 border @error('name') border-red-500 @else border-gray-300 @enderror rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
      </div>

      <!-- Product Price -->
      <div class="mb-4">
        <label for="price" class="block text-sm font-medium text-gray-700">Price</label>
        <input type="number" id="price" name="price" value="{{ old('price') }}" step="0.01"
          class="mt-1 block w-full px-3 py-2 border @error('price') border-red-500 @else border-gray-300 @enderror rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
      </div>

      <!-- Product Image -->
      <div class="mb-4">
        <label for="image" class="block text-sm font-medium text-gray-700">Product Image</label>
        <input type="file" id="image" name="image" accept="image/*"
          class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
      </div>

      <!-- Submit Button -->

      <button type="submit"
        class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
        Create Product
      </button>
    </form>



  @endsection
