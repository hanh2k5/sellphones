<?php
use Illuminate\Support\Facades\Route;
Route::get('/status', function () { return response()->json(['status' => 'Framework Ready', 'version' => '13.0']); });
