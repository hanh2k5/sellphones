<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/run-migrations', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate:fresh', [
            '--force' => true,
            '--seed' => true,
        ]);
        return 'Database migrated and seeded successfully!';
    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});
