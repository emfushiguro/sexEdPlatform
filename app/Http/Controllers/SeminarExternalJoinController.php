<?php

namespace App\Http\Controllers;

use App\Models\Seminar;
use App\Services\Seminars\SeminarExternalAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SeminarExternalJoinController extends Controller
{
    public function __invoke(Request $request, Seminar $seminar, SeminarExternalAccessService $access): RedirectResponse
    {
        $url = $access->redirectUrl($request->user(), $seminar);

        return redirect()->away($url)->withHeaders([
            'Cache-Control' => 'no-store',
            'Referrer-Policy' => 'no-referrer',
        ]);
    }
}
