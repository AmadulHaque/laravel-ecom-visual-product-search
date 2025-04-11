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
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

       try {
        $this->productService->search($validated);
       } catch (\Throwable $th) {
            return redirect()->route('products.create')
            ->with('error', 'Product not added successfully! Error: ' . $th->getMessage());
       }
    }

    public function initWeaviate()
    {
        $result = $this->productService->initWeaviate();
        return redirect()->back()->with('weaviate_status', $result ? 'Schema created!' : 'Error creating schema');
    }


}
