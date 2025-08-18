<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PdfController;

Route::get('/', [PdfController::class, 'index']);
Route::post('/upload', [PdfController::class, 'upload'])->name('upload');