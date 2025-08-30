<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QdrantService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.clip.base_url', 'http://localhost:5000');
    }

    public function upsertVector($id, $vector, $payload = [])
    {
        try {
            $response = Http::timeout(30)
                ->post($this->baseUrl . '/qdrant/upsert', [
                    'id' => $id,
                    'vector' => $vector,
                    'payload' => $payload
                ]);

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Qdrant upsert error: ' . $response->body());
                throw new \Exception('Qdrant upsert failed: ' . $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Failed to upsert vector to Qdrant: ' . $e->getMessage());
            throw $e;
        }
    }

    public function searchVectors($vector, $limit = 10)
    {
        try {
            $response = Http::timeout(30)
                ->post($this->baseUrl . '/qdrant/search', [
                    'vector' => $vector,
                    'limit' => $limit
                ]);

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Qdrant search error: ' . $response->body());
                throw new \Exception('Qdrant search failed: ' . $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Failed to search vectors in Qdrant: ' . $e->getMessage());
            throw $e;
        }
    }

    public function deleteVector($id)
    {
        try {
            $response = Http::timeout(30)
                ->post($this->baseUrl . '/qdrant/delete', [
                    'id' => $id
                ]);

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Qdrant delete error: ' . $response->body());
                throw new \Exception('Qdrant delete failed: ' . $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Failed to delete vector from Qdrant: ' . $e->getMessage());
            throw $e;
        }
    }

    public function healthCheck()
    {
        try {
            $response = Http::timeout(10)->get($this->baseUrl . '/health');
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
