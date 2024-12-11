<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Welcome to the API 😊',
        'introduction' => [
            'title' => 'Backend API Documentation',
            'welcome_message' => 'Selamat datang di Backend API kami! 🎉',
            'description' => 'API ini dirancang untuk mendukung berbagai fitur aplikasi, sekaligus memperkenalkan tim backend yang bertanggung jawab atas pengembangannya.',
            'team' => [
                [
                    'role' => 'Backend Lead',
                    'name' => 'Yasdil Lasoma',
                    'profile' => 'https://github.com/adilasomaa'
                ],
                [
                    'role' => 'API Developer',
                    'name' => 'Rosyad Aditya Nugroho',
                    'profile' => 'https://github.com/adilasomaa'
                ],
                [
                    'role' => 'Database Administrator',
                    'name' => 'Mohammad Farrel',
                    'profile' => 'https://github.com/adilasomaa'
                ],
                [
                    'role' => 'Documentation Contributor',
                    'name' => 'Acha Qilal',
                    'profile' => 'https://github.com/adilasomaa'
                ]
            ]
        ]
    ]);
});

Route::get('/tes', function () {
    return view('emails.reset_password');
});
