<?php

use Illuminate\Support\Facades\Route;
use Octopy\Filament\Palette\PaletteManager;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::post('/dashboard/palette/{theme}', function (string $theme, PaletteManager $manager) {
    $palette = config("filament-palette.palette.{$theme}");

    if ($palette) {
        $manager->set($theme);
    }

    return redirect()->back();
})->middleware(['web', 'auth'])->name('palette.apply');
