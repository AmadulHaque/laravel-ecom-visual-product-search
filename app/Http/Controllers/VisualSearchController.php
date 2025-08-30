<?php

namespace App\Http\Controllers;


use App\Models\Product;
use App\Services\ClipService;
use App\Services\QdrantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VisualSearchController extends Controller
{
    protected $clipService;
    protected $qdrantService;

    public function __construct(ClipService $clipService, QdrantService $qdrantService)
    {
        $this->clipService = $clipService;
        $this->qdrantService = $qdrantService;
    }

    public function index()
    {
        $isServiceHealthy = $this->qdrantService->healthCheck();

        return view('visual-search', [
            'serviceStatus' => $isServiceHealthy ? 'connected' : 'disconnected'
        ]);
    }

    public function search(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240', // 10MB max
        ]);

        try {
            // Check if service is available
            if (!$this->qdrantService->healthCheck()) {
                throw new \Exception('Visual search service is not available');
            }

            // Get embedding from CLIP service
            $embedding = $this->clipService->getImageEmbeddingFromUpload($request->file('image'));

            // Query Qdrant for similar products
            $qdrantResults = $this->qdrantService->searchVectors($embedding, 8);

            // Get product IDs from Qdrant results
            $productIds = collect($qdrantResults['results'] ?? [])
                ->pluck('id')
                ->filter()
                ->map(function ($id) {
                    return (int) $id; // Ensure IDs are integers
                })
                ->toArray();

            // Get products from database
            if (!empty($productIds)) {
                $similarProducts = Product::whereIn('id', $productIds)
                    ->get()
                    ->sortBy(function ($product) use ($productIds) {
                        return array_search($product->id, $productIds);
                    })
                    ->values();
            } else {
                $similarProducts = collect();
            }

            return response()->json([
                'success' => true,
                'products' => $similarProducts,
                'matches_count' => count($productIds)
            ]);

        } catch (\Exception $e) {
            Log::error('Visual search error: ' . $e->getMessage());

            // Fallback: return random products if search fails
            $similarProducts = Product::inRandomOrder()->limit(8)->get();

            return response()->json([
                'success' => false,
                'message' => 'Search failed, showing random products',
                'products' => $similarProducts,
                'error' => $e->getMessage()
            ]);
        }
    }

    // Process all existing products and add to Qdrant
    public function processExistingProducts()
    {
        try {
            $products = Product::all();
            $processed = 0;
            $errors = 0;

            foreach ($products as $product) {
                try {
                    // Get embedding if not already exists
                    if (empty($product->embedding)) {
                        $imagePath = storage_path('app/public/' . $product->image_path);

                        if (file_exists($imagePath)) {
                            $embedding = $this->clipService->getImageEmbedding($imagePath);
                            $product->embedding = $embedding;
                            $product->save();
                        }
                    }

                    // Add to Qdrant if embedding exists
                    if (!empty($product->embedding)) {
                        $this->qdrantService->upsertVector(
                            $product->id,
                            $product->embedding,
                            [
                                'name' => $product->name,
                                'price' => $product->price,
                                'image_path' => $product->image_path
                            ]
                        );
                        $processed++;
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to process product {$product->id}: " . $e->getMessage());
                    $errors++;
                }

                // Small delay to avoid overwhelming the service
                usleep(100000); // 0.1 second
            }

            return response()->json([
                'success' => true,
                'message' => "Processed $processed products with $errors errors"
            ]);

        } catch (\Exception $e) {
            Log::error('Process products error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error processing products: ' . $e->getMessage()
            ]);
        }
    }

    // Clear all vectors from Qdrant
    public function clearVectors()
    {
        try {
            // Note: This would require adding a delete all endpoint to the Python service
            // For now, we'll just recreate the collection
            // In a real implementation, you'd add a proper endpoint to the Python service

            return response()->json([
                'success' => false,
                'message' => 'Clear functionality not implemented. Restart the Python service to clear vectors.'
            ]);

        } catch (\Exception $e) {
            Log::error('Clear vectors error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error clearing vectors: ' . $e->getMessage()
            ]);
        }
    }
}
