<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class WeaviateService
{
    protected $client;
    protected $baseUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->baseUrl = env('WEAVIATE_URL', 'http://localhost:8080/v1');
        $this->apiKey = env('WEAVIATE_API_KEY');

        $headers = [
            'Content-Type' => 'application/json',
        ];

        // Add Authorization header if API key is present
        if ($this->apiKey) {
            $headers['Authorization'] = 'Bearer ' . $this->apiKey;
        }

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => $headers,
        ]);
    }

    public function createSchema()
    {
        $schema = [
            'classes' => [
                [
                    'class' => 'Product',
                    'description' => 'A product with image and metadata',
                    'properties' => [
                        [
                            'name' => 'name',
                            'dataType' => ['string'],
                        ],
                        [
                            'name' => 'price',
                            'dataType' => ['number'],
                        ],
                    ],
                    'vectorizer' => 'img2vec-neural',
                    'moduleConfig' => [
                        'img2vec-neural' => [
                            'imageFields' => ['image'],
                        ],
                    ],
                ],
            ],
        ];

        try {
            $response = $this->client->post('/schema', ['json' => $schema]);
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('Error creating schema: ' . $e->getMessage());
            return false;
        }
    }

    public function addProduct($productData, $imagePath)
    {
        $imageBase64 = $this->convertImageToBase64($imagePath);


        $data = [
            'class' => 'Product',
            'properties' => [
                'name' => $productData['name'],
                'price' => (float)$productData['price'],
            ],
            'image' => $imageBase64,
        ];

        try {
            $response = $this->client->post('/v1/objects', ['json' => $data]);
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('Error adding product: ' . $e->getMessage());
            return false;
        }
    }

    public function searchSimilarImages($imagePath, $limit = 5)
    {
        $imageBase64 = $this->convertImageToBase64($imagePath);

        $data = [
            'image' => $imageBase64,
            'limit' => $limit,
            'className' => 'Product'
        ];

        try {
            $response = $this->client->post('/v1/objects', ['json' => $data]);
            $result = json_decode($response->getBody(), true);
            dd($result);
            return $result['objects'] ?? [];
        } catch (\Exception $e) {
            Log::error('Error searching similar images: ' . $e->getMessage());
            return [];
        }
    }
    protected function convertImageToBase64($imagePath)
    {
        $imageData = file_get_contents($imagePath);
        return base64_encode($imageData);
    }
}
