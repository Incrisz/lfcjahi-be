<?php

use App\Http\Controllers\Api\PublicMediaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/messages/{id}', [PublicMediaController::class, 'share']);
