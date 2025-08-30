<?php

use App\Http\Controllers\VisualSearchController;
use Illuminate\Support\Facades\Route;



Route::get('/', [VisualSearchController::class, 'index']);
Route::post('/visual-search', [VisualSearchController::class, 'search']);
Route::post('/process-products', [VisualSearchController::class, 'processExistingProducts'])->name('visual-search.process-products');
