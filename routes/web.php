<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use App\Models\Service;
use App\Models\Category;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/sitemap.xml', function () {
    $baseUrl = env('FRONTEND_URL', config('app.url'));

    $staticUrls = [
        $baseUrl . '/',
        $baseUrl . '/services',
        $baseUrl . '/how-it-works',
    ];

    $categoryUrls = Category::select('id', 'updated_at')
        ->get()
        ->map(fn ($category) => [
            'loc' => $baseUrl . '/services?category=' . $category->id,
            'lastmod' => optional($category->updated_at)->toAtomString(),
        ])
        ->toArray();

    $serviceUrls = Service::select('id', 'updated_at')
        ->get()
        ->map(fn ($service) => [
            'loc' => $baseUrl . '/services/' . $service->id,
            'lastmod' => optional($service->updated_at)->toAtomString(),
        ])
        ->toArray();

    $urls = array_merge(
        array_map(fn ($loc) => ['loc' => $loc, 'lastmod' => now()->toAtomString()], $staticUrls),
        $categoryUrls,
        $serviceUrls
    );

    $xml = view('sitemap', ['urls' => $urls])->render();

    return Response::make($xml, 200, ['Content-Type' => 'application/xml']);
});

// Fallback to serve index.html for SPA routing
Route::fallback(function () {
    if (File::exists(public_path('index.html'))) {
        return File::get(public_path('index.html'));
    }
    return view('welcome');
});
