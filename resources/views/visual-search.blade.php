<!-- resources/views/visual-search.blade.php -->
@extends('layouts.app')

@section('content')
<div x-data="visualSearch()" class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-center mb-2">Visual Product Search</h1>

    <!-- Service Status -->
    <div class="text-center mb-6">
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
            :class="serviceStatus === 'connected' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
            <span x-show="serviceStatus === 'connected'" class="h-2 w-2 bg-green-500 rounded-full mr-2"></span>
            <span x-show="serviceStatus !== 'connected'" class="h-2 w-2 bg-red-500 rounded-full mr-2"></span>
            Service <span x-text="serviceStatus"></span>
        </span>
    </div>

    <!-- Upload Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="text-center">
            <div x-show="!uploadedImage"
                 class="border-2 border-dashed border-gray-300 rounded-lg p-8 mb-4">
                <input type="file" id="imageUpload" class="hidden" accept="image/*" @change="uploadImage">
                <label for="imageUpload" class="cursor-pointer">
                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <div class="mt-4 text-sm text-gray-600">
                        <span class="font-medium text-indigo-600">Upload an image</span>
                        or drag and drop
                    </div>
                    <p class="text-xs text-gray-500">PNG, JPG up to 10MB</p>
                </label>
            </div>

            <div x-show="uploadedImage" class="mb-4">
                <img :src="uploadedImage" alt="Uploaded image" class="max-h-64 mx-auto rounded-lg shadow-md">
                <button @click="removeImage" class="mt-2 text-sm text-red-600">Remove image</button>
            </div>

            <button x-show="uploadedImage && serviceStatus === 'connected'"
                    @click="searchProducts"
                    :disabled="isSearching"
                    class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700 disabled:opacity-50">
                <span x-show="!isSearching">Search Similar Products</span>
                <span x-show="isSearching">Searching...</span>
            </button>

            <div x-show="serviceStatus !== 'connected'" class="mt-4 p-3 bg-yellow-100 rounded-md text-yellow-800">
                <p>Visual search service is offline. Please start the Python service to enable visual search.</p>
                <p class="text-sm mt-1">Run: <code class="bg-gray-200 px-1 py-0.5 rounded">python services/clip_service.py</code></p>
            </div>
        </div>
    </div>

    <!-- Results Section -->
    <div x-show="results.length > 0" class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-semibold mb-6">Search Results</h2>

        <div x-show="isSearching" class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            <p class="mt-2 text-gray-600">Finding similar products...</p>
        </div>

        <div x-show="!isSearching && results.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <template x-for="product in results" :key="product.id">
                <div class="border rounded-lg overflow-hidden hover:shadow-lg transition-shadow">
                    <img :src="'/storage/' + product.image_path" :alt="product.name" class="w-full h-48 object-cover">
                    <div class="p-4">
                        <h3 class="font-semibold text-lg" x-text="product.name"></h3>
                        <p class="text-gray-600 text-sm mt-1" x-text="product.description.substring(0, 60) + '...'"></p>
                        <div class="flex justify-between items-center mt-3">
                            <span class="text-indigo-600 font-bold" x-text="'$' + product.price"></span>
                            <button class="bg-indigo-100 text-indigo-600 px-3 py-1 rounded text-sm hover:bg-indigo-200">View Details</button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- No Results Message -->
    <div x-show="showNoResults" class="bg-white rounded-lg shadow-md p-6 text-center">
        <p class="text-gray-600">No similar products found. Try a different image.</p>
    </div>

    <!-- Admin Controls (for development) -->
    @if(Auth::check())
    <div class="mt-8 bg-gray-100 p-4 rounded-lg">
        <h3 class="font-semibold mb-2">Admin Controls</h3>
        <div class="flex space-x-2">
            <button @click="processProducts" class="bg-blue-500 text-white px-3 py-1 rounded text-sm">
                Process All Products
            </button>
            <button @click="clearVectors" class="bg-red-500 text-white px-3 py-1 rounded text-sm">
                Clear Vectors
            </button>
        </div>
    </div>
    @endif
</div>

<script>
function visualSearch() {
    return {
        uploadedImage: null,
        isSearching: false,
        results: [],
        showNoResults: false,
        serviceStatus: '{{ $serviceStatus }}',

        uploadImage(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.uploadedImage = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },

        removeImage() {
            this.uploadedImage = null;
            this.results = [];
            this.showNoResults = false;
            document.getElementById('imageUpload').value = '';
        },

        async searchProducts() {
            this.isSearching = true;
            this.results = [];
            this.showNoResults = false;

            try {
                const fileInput = document.getElementById('imageUpload');
                const formData = new FormData();
                formData.append('image', fileInput.files[0]);

                const response = await fetch('/visual-search', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.results = data.products;
                    this.showNoResults = data.products.length === 0;
                } else {
                    alert('Search failed: ' + (data.message || 'Please try again.'));
                    this.results = data.products || [];
                    this.showNoResults = data.products.length === 0;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            } finally {
                this.isSearching = false;
            }
        },

        async processProducts() {
            if (!confirm('Process all products to generate embeddings? This may take a while.')) return;

            try {
                const response = await fetch('/process-products', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();
                alert(data.message);
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to process products');
            }
        },

        async clearVectors() {
            if (!confirm('Clear all vectors from Qdrant? This will remove all visual search data.')) return;

            try {
                const response = await fetch('/clear-vectors', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();
                alert(data.message);
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to clear vectors');
            }
        }
    };
}
</script>

<style>
    [x-cloak] { display: none !important; }
</style>
@endsection
