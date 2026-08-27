<?php

use App\Http\Controllers\WorkbookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('filament.admin.pages.dashboard');
});

Route::get('/workbook/download', [WorkbookController::class, 'download'])
    ->name('workbook.download')
    ->middleware('auth');

Route::post('/theme', function (Request $request) {
    $theme = $request->validate([
        'theme' => 'required|in:dark,light,system',
    ])['theme'];

    $request->user()->update(['theme_mode' => $theme]);

    return response()->json(['ok' => true, 'theme' => $theme]);
})->middleware('auth')->name('theme.update');
