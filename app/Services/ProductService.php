<?php

namespace App\Services;

use App\Models\Product;
use App\Services\WeaviateService;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    public function __construct(private WeaviateService $weaviateService)
    {}

    public function getAll()
    {
        return Product::paginate(10);
    }

    public function create(array $data): Product
    {
        if (isset($data['image'])) {
            $imagePath = $data['image']->store('products', 'public');
        }

        $product = Product::create([
            'name' => $data['name'],
            'price' => $data['price'],
            'image_path' => $imagePath ?? null,
        ]);

        // Add to Weaviate if image exists
        if (isset($imagePath)) {
            $fullImagePath = storage_path('app/public/' . $imagePath);
            $weaviateResponse = $this->weaviateService->addProduct($data, $fullImagePath);

            if ($weaviateResponse) {
                $product->update(['weaviate_id' => $weaviateResponse['id']]);
            }
        }
        return $product;
    }


    public function search(array $data)
    {
        $searchImagePath = $data['image']->store('temp', 'public');
        $fullSearchImagePath = storage_path('app/public/' . $searchImagePath);

        // Perform similarity search
        $similarProducts = $this->weaviateService->searchSimilarImages($fullSearchImagePath);

        // Delete temporary image
        Storage::disk('public')->delete($searchImagePath);

// dd($similarProducts);
        $results = [];
        foreach ($similarProducts as $similar) {
            $product = Product::where('weaviate_id', $similar['_additional']['id'])->first();
            if ($product) {
                $results[] = [
                    'product' => $product,
                    'certainty' => $similar['_additional']['certainty'],
                ];
            }
        }

        return $results;
        // Save the search results image
        // $resultsImagePath = 'searches/' . uniqid() . '.jpg';
        // Storage::disk('public')->copy($searchImagePath, $resultsImagePath);



    }

    public function initWeaviate(): mixed
    {
        return $this->weaviateService->createSchema();
    }

}
