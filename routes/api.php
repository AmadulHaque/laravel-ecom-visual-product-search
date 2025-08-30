<?php

use App\Http\Controllers\VisualSearchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::post('/visual-search', [VisualSearchController::class, 'search']);
Route::post('/process-products', [VisualSearchController::class, 'processExistingProducts']);
