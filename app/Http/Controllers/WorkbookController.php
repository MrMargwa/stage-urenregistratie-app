<?php

namespace App\Http\Controllers;

use App\Services\WorkbookService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WorkbookController extends Controller
{
    public function download(Request $request): Response
    {
        $user = $request->user();

        abort_unless($user !== null && $user->hasLinkedWorkbook(), 404);

        $workbooks = app(WorkbookService::class);

        $workbooks->refresh($user);

        return response()->download(
            $workbooks->absolutePath($user),
            $workbooks->downloadName($user),
        );
    }
}
