<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductStoreRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{

    public function __construct(private ProductService $productService)
    {}

    public function index()
    {
        $products = $this->productService->getAll();
        return view('products.index', compact('products'));
    }

    public function create()
    {
        return view('products.create');
    }


    public function store(ProductStoreRequest $request)
    {
        try {
            $validated = $request->validated();
            $this->productService->create($validated);
            return redirect()->route('products.create')
                ->with('success', 'Product added successfully!');
        } catch (\Throwable $th) {
            return redirect()->route('products.create')
                ->with('error', 'Product not added successfully! Error: ' . $th->getMessage());
        }
    }


    public function search(Request $request)
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

       try {
            $result = $this->productService->search($validated);
            return  $result;
            return redirect()->route(route: 'products.index')
                ->with('success', 'Product found successfully!');
       } catch (\Throwable $th) {
            return redirect()->route('products.index')
            ->with('error', 'Error: ' . $th->getMessage());
       }
    }


    public function initWeaviate()
    {
        try {
            $this->productService->initWeaviate();
            return redirect()->route('products.create')
                ->with('success', 'Weaviate initialized successfully!');
        } catch (\Throwable $th) {
            return redirect()->route('products.create')
                ->with('error', 'Weaviate not initialized successfully! Error: ' . $th->getMessage());
        }
    }


}
