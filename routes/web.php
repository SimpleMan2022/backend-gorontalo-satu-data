<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Welcome to the API 😊',
    ]);
});

Route::get('/tes', function () {
    return view('emails.reset_password');
});
