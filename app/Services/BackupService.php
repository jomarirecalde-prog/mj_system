<?php

namespace App\Services;

use App\Models\Backup;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

class BackupService
{
    public function createBackup(?User $user = null): Backup
    {
        $user = $user ?? Auth::user();

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        $backupDir = storage_path('app/backups');

        if (! File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $timestamp = now('Asia/Manila')->format('Y-m-d_His');
        $filename = "backup_{$timestamp}.sqlite";
        $relativePath = "backups/{$filename}";
        $absolutePath = storage_path('app/'.$relativePath);

        if ($driver === 'sqlite') {
            $databasePath = config("database.connections.{$connection}.database");

            if (! is_string($databasePath) || ! File::exists($databasePath)) {
                throw new RuntimeException('SQLite database file not found.');
            }

            File::copy($databasePath, $absolutePath);
        } else {
            throw new RuntimeException("Automated backup is only implemented for SQLite (current driver: {$driver}).");
        }

        $size = File::size($absolutePath);

        return Backup::query()->create([
            'filename' => $filename,
            'path' => $relativePath,
            'size' => $size,
            'created_by' => $user?->id,
        ]);
    }

    /**
     * @return Collection<int, Backup>
     */
    public function list(): Collection
    {
        return Backup::query()
            ->with('creator')
            ->orderByDesc('created_at')
            ->get();
    }

    public function downloadPath(Backup $backup): string
    {
        $path = storage_path('app/'.$backup->path);

        if (! File::exists($path)) {
            throw new RuntimeException('Backup file no longer exists on disk.');
        }

        return $path;
    }

    /**
     * Restore database from backup. Caller must enforce confirmation in the controller.
     */
    public function restore(Backup $backup): void
    {
        $source = $this->downloadPath($backup);

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver !== 'sqlite') {
            throw new RuntimeException('Restore is only implemented for SQLite.');
        }

        $databasePath = config("database.connections.{$connection}.database");

        if (! is_string($databasePath)) {
            throw new RuntimeException('Invalid database configuration.');
        }

        DB::disconnect($connection);

        File::copy($source, $databasePath);
    }
}
