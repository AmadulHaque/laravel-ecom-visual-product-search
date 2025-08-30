<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClipService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.clip.base_url', 'http://localhost:5000');
    }

    public function getImageEmbedding($imagePath)
    {
        try {
            $response = Http::timeout(30)
                ->attach('image', file_get_contents($imagePath), 'image.jpg')
                ->post($this->baseUrl . '/embed');

            if ($response->successful()) {
                return $response->json()['embedding'];
            } else {
                Log::error('CLIP service error: ' . $response->body());
                throw new \Exception('CLIP service returned error: ' . $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Failed to get embedding from CLIP service: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getImageEmbeddingFromUpload($uploadedFile)
    {
        try {
            $response = Http::timeout(30)
                ->attach('image', $uploadedFile->get(), 'image.jpg')
                ->post($this->baseUrl . '/embed');

            if ($response->successful()) {
                return $response->json()['embedding'];
            } else {
                Log::error('CLIP service error: ' . $response->body());
                throw new \Exception('CLIP service returned error: ' . $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Failed to get embedding from CLIP service: ' . $e->getMessage());
            throw $e;
        }
    }
}
