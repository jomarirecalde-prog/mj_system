<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function __construct(protected BackupService $backupService)
    {
        $this->middleware(['auth', 'active', 'role:admin']);
    }

    public function index(): View
    {
        $backups = $this->backupService->list();

        return view('backups.index', compact('backups'));
    }

    public function create(Request $request): RedirectResponse
    {
        try {
            $this->backupService->createBackup($request->user());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Backup created successfully.');
    }

    public function download(Backup $backup): BinaryFileResponse
    {
        try {
            $path = $this->backupService->downloadPath($backup);
        } catch (\Throwable $e) {
            abort(404, $e->getMessage());
        }

        return response()->download($path, $backup->filename);
    }

    public function restore(Request $request, Backup $backup): RedirectResponse
    {
        $request->validate([
            'confirm' => ['required', 'accepted'],
        ]);

        try {
            $this->backupService->restore($backup);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('backups.index')
            ->with('success', 'Database restored from backup. Please sign in again if needed.');
    }
}
