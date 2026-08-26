<?php

use App\Http\Controllers\WorkbookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('filament.admin.pages.dashboard');
});

Route::get('/workbook/download', [WorkbookController::class, 'download'])
    ->name('workbook.download')
    ->middleware('auth');
