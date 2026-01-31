<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

Route::get('/', function () {
    return view('welcome');
});

// Fallback to serve index.html for SPA routing
Route::fallback(function () {
    if (File::exists(public_path('index.html'))) {
        return File::get(public_path('index.html'));
    }
    return view('welcome');
});

