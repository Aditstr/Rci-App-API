<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/api-docs', function () {
    return view('api-docs');
});

Route::get('/swagger.yaml', function () {
    return response()->file(public_path('swagger.yaml'));
});
