<?php

use App\Http\Controllers\WorkbookController;
use Illuminate\Support\Facades\Route;
use Octopy\Filament\Palette\PaletteManager;

Route::get('/', function () {
    return redirect()->to(auth()->check() ? '/admin' : '/admin/login');
})->name('home');

Route::get('/werkblad/download', WorkbookController::class.'@download')
    ->middleware('auth')
    ->name('workbook.download');
