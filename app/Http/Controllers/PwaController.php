<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PwaController extends Controller
{
    /**
     * Offline fallback page (matches public/offline.html design via Blade).
     */
    public function offline(): View
    {
        return view('pwa.offline');
    }
}
