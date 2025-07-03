<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    /**
     * Display the settings page.
     */
    public function index(): Response
    {
        $settings = [
            'system' => [
                'app_name' => config('app.name', 'DMARC Reports'),
                'app_env' => config('app.env'),
                'app_debug' => config('app.debug'),
                'timezone' => config('app.timezone'),
                'locale' => config('app.locale'),
            ],
            'dmarc' => [
                'reports_directory' => config('dmarc.reports_directory'),
                'max_records_per_page' => config('dmarc.dashboard.max_records_per_page'),
                'import_batch_size' => config('dmarc.import.batch_size'),
            ],
            'storage' => [
                'disk' => config('filesystems.default'),
                'dmarc_disk' => config('filesystems.disks.dmarc.driver'),
                'dmarc_path' => config('filesystems.disks.dmarc.root'),
            ],
            'cache' => [
                'default' => config('cache.default'),
                'ttl' => config('cache.ttl', 3600),
            ],
        ];

        // ストレージ情報
        $storageInfo = [
            'total_files' => count(Storage::disk('dmarc')->allFiles()),
            'total_size' => $this->formatBytes($this->getDirectorySize(Storage::disk('dmarc')->path(''))),
            'last_import' => $this->getLastImportTime(),
        ];

        return Inertia::render('Settings/Index', [
            'settings' => $settings,
            'storageInfo' => $storageInfo,
        ]);
    }

    /**
     * Get directory size in bytes.
     */
    private function getDirectorySize(string $path): int
    {
        $size = 0;
        foreach (glob(rtrim($path, '/') . '/*', GLOB_NOSORT) as $each) {
            $size += is_file($each) ? filesize($each) : $this->getDirectorySize($each);
        }
        return $size;
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Get last import time.
     */
    private function getLastImportTime(): ?string
    {
        $latestFile = collect(Storage::disk('dmarc')->allFiles())
            ->filter(function ($file) {
                return pathinfo($file, PATHINFO_EXTENSION) === 'xml';
            })
            ->sortByDesc(function ($file) {
                return Storage::disk('dmarc')->lastModified($file);
            })
            ->first();

        if ($latestFile) {
            return date('Y-m-d H:i:s', Storage::disk('dmarc')->lastModified($latestFile));
        }

        return null;
    }
} 