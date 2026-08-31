<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $theme = $request->validate([
            'theme' => 'required|in:dark,light,system',
        ])['theme'];

        $request->user()->update(['theme_mode' => $theme]);

        return response()->json(['ok' => true, 'theme' => $theme]);
    }
}
