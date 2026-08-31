<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ThemeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class);

Route::post('/theme', ThemeController::class)->middleware('auth')->name('theme.update');
