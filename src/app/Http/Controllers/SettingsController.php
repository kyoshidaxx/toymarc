<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Storage;
use App\Services\DmarcReportImportService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

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
     * Import DMARC reports.
     */
    public function importReports(Request $request)
    {
        try {
            // Artisanコマンドを実行
            $exitCode = Artisan::call('dmarc:import');
            
            if ($exitCode === 0) {
                $output = Artisan::output();
                return back()->with('success', 'レポートの取り込みが完了しました');
            } else {
                return back()->withErrors(['error' => 'レポートの取り込みに失敗しました: ' . Artisan::output()]);
            }
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'エラーが発生しました: ' . $e->getMessage()]);
        }
    }

    /**
     * Upload and import DMARC report files.
     */
    public function uploadReports(Request $request)
    {
        try {
            $request->validate([
                'files.*' => 'required|file|mimes:xml|max:10240', // 10MB max per file
            ]);

            $uploadedFiles = $request->file('files');
            $uploadedCount = 0;
            $importedCount = 0;
            $errors = [];

            foreach ($uploadedFiles as $file) {
                try {
                    // ファイルをdmarcディスクに保存
                    $filename = time() . '_' . $file->getClientOriginalName();
                    $path = Storage::disk('dmarc')->putFileAs('', $file, $filename);
                    
                    if ($path) {
                        $uploadedCount++;
                        
                        // 個別にファイルをインポート
                        try {
                            $importService = app(DmarcReportImportService::class);
                            $importService->importSingleReport($path);
                            $importedCount++;
                        } catch (\Exception $e) {
                            $errors[] = $file->getClientOriginalName() . ' (インポートエラー): ' . $e->getMessage();
                        }
                    } else {
                        $errors[] = $file->getClientOriginalName() . ': ファイルの保存に失敗しました';
                    }
                } catch (\Exception $e) {
                    $errors[] = $file->getClientOriginalName() . ': ' . $e->getMessage();
                }
            }

            // 結果メッセージを作成
            if ($uploadedCount > 0) {
                $message = "{$uploadedCount}個のファイルをアップロードし、{$importedCount}個のレポートをインポートしました";
                if (count($errors) > 0) {
                    $message .= "（" . count($errors) . "個のエラー）";
                }
                
                if (count($errors) > 0) {
                    return back()->with('success', $message)->withErrors(['details' => $errors]);
                } else {
                    return back()->with('success', $message);
                }
            } else {
                return back()->withErrors(['error' => 'ファイルのアップロードに失敗しました']);
            }
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'エラーが発生しました: ' . $e->getMessage()]);
        }
    }

    /**
     * Clear application cache.
     */
    public function clearCache()
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            
            return back()->with('success', 'キャッシュがクリアされました');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'エラーが発生しました: ' . $e->getMessage()]);
        }
    }

    /**
     * Clean up storage by removing old files.
     */
    public function cleanupStorage()
    {
        try {
            $importService = app(DmarcReportImportService::class);
            $results = $importService->cleanupStorage();
            
            $message = "ストレージクリーンアップが完了しました。";
            if ($results['deleted_files'] > 0) {
                $deletedSizeMB = round($results['deleted_size'] / (1024 * 1024), 2);
                $message .= " {$results['deleted_files']} 個のファイル（{$deletedSizeMB} MB）を削除しました。";
            } else {
                $message .= " 削除対象のファイルはありませんでした。";
            }
            
            if (count($results['errors']) > 0) {
                return back()->with('success', $message)->withErrors(['details' => $results['errors']]);
            } else {
                return back()->with('success', $message);
            }
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'エラーが発生しました: ' . $e->getMessage()]);
        }
    }

    /**
     * Get application logs.
     */
    public function getLogs(Request $request)
    {
        try {
            $logFile = $request->get('file', 'laravel.log');
            $lines = $request->get('lines', 100);
            
            $logPath = storage_path('logs/' . $logFile);
            
            if (!File::exists($logPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ログファイルが見つかりません: ' . $logFile
                ], 404);
            }
            
            // ログファイルの最後のN行を取得
            $content = File::get($logPath);
            $lines_array = explode("\n", $content);
            $last_lines = array_slice($lines_array, -$lines);
            $log_content = implode("\n", $last_lines);
            
            // ログファイルの基本情報
            $fileInfo = [
                'name' => $logFile,
                'size' => $this->formatBytes(File::size($logPath)),
                'modified' => date('Y-m-d H:i:s', File::lastModified($logPath)),
                'total_lines' => count($lines_array),
                'displayed_lines' => count($last_lines),
            ];
            
            return response()->json([
                'success' => true,
                'data' => [
                    'content' => $log_content,
                    'file_info' => $fileInfo,
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ログの読み込みに失敗しました: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available log files.
     */
    public function getLogFiles()
    {
        try {
            $logPath = storage_path('logs');
            $files = File::files($logPath);
            
            $logFiles = [];
            foreach ($files as $file) {
                $logFiles[] = [
                    'name' => $file->getFilename(),
                    'size' => $this->formatBytes($file->getSize()),
                    'modified' => date('Y-m-d H:i:s', $file->getMTime()),
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $logFiles
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ログファイル一覧の取得に失敗しました: ' . $e->getMessage()
            ], 500);
        }
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