<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class WeaviateService
{
    protected $client;
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('WEAVIATE_URL', 'http://localhost:8080/v1');
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
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
            $response = $this->client->post('/objects', ['json' => $data]);
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('Error adding product: ' . $e->getMessage());
            return false;
        }
    }

    public function searchSimilarImages($imagePath, $limit = 5)
    {
        $imageBase64 = $this->convertImageToBase64($imagePath);

        $query = [
            'query' => '
                {
                    Get {
                        Product(nearImage: {
                            image: "' . $imageBase64 . '"
                        }, limit: ' . $limit . ') {
                            name
                            price
                            _additional {
                                id
                                certainty
                            }
                        }
                    }
                }
            '
        ];

        try {
            $response = $this->client->post('/graphql', ['json' => $query]);
            $result = json_decode($response->getBody(), true);

            if (isset($result['data']['Get']['Product'])) {
                return $result['data']['Get']['Product'];
            }
            return [];
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
