<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PwaController extends Controller
{
    /**
     * Offline fallback page (matches public/offline.html design via Blade).
     */
    public function offline(): View
    {
        return view('pwa.offline');
    }

    public function manifest(): BinaryFileResponse|Response
    {
        return $this->publicFile('site.webmanifest', 'application/manifest+json');
    }

    public function serviceWorker(): BinaryFileResponse|Response
    {
        $response = $this->publicFile('service-worker.js', 'application/javascript; charset=utf-8');

        if ($response instanceof BinaryFileResponse) {
            $response->headers->set('Service-Worker-Allowed', '/');
        }

        return $response;
    }

    protected function publicFile(string $filename, string $contentType): BinaryFileResponse|Response
    {
        $path = public_path($filename);

        if (! File::isFile($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
