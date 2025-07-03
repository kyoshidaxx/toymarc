<?php

namespace App\Console\Commands;

use App\Services\DmarcReportImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportDmarcReportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dmarc:import 
                            {directory? : DMARCレポートファイルのディレクトリパス}
                            {--dry-run : 実際の取り込みを行わずにチェックのみ実行}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'DMARCレポートファイルを指定ディレクトリから取り込みます';

    /**
     * Execute the console command.
     */
    public function handle(DmarcReportImportService $importService): int
    {
        $directory = $this->argument('directory') ?? config('dmarc.reports_directory', 'dmarc_reports');
        $isDryRun = $this->option('dry-run');

        $this->info('DMARCレポート取り込みを開始します...');
        $this->info("ディレクトリ: {$directory}");
        
        if ($isDryRun) {
            $this->warn('ドライランモード: 実際の取り込みは行われません');
        }

        try {
            if ($isDryRun) {
                $this->performDryRun($directory);
            } else {
                $results = $importService->importReportsFromDirectory($directory);
                $this->displayResults($results);
            }

            $this->info('DMARCレポート取り込みが完了しました。');
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('DMARCレポート取り込みでエラーが発生しました: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Perform dry run to check files.
     */
    private function performDryRun(string $directory): void
    {
        $this->info('ドライラン実行中...');

        if (!Storage::exists($directory)) {
            $this->error("ディレクトリが存在しません: {$directory}");
            return;
        }

        $files = Storage::files($directory);
        $xmlFiles = array_filter($files, function ($file) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'xml';
        });

        $this->info("対象ファイル数: " . count($xmlFiles));

        if (empty($xmlFiles)) {
            $this->warn('XMLファイルが見つかりませんでした。');
            return;
        }

        $this->table(
            ['ファイル名', 'サイズ', '最終更新日'],
            array_map(function ($file) {
                $stats = Storage::stat($file);
                return [
                    basename($file),
                    $this->formatBytes($stats['size']),
                    date('Y-m-d H:i:s', $stats['mtime']),
                ];
            }, $xmlFiles)
        );
    }

    /**
     * Display import results.
     */
    private function displayResults(array $results): void
    {
        $this->info('取り込み結果:');
        $this->table(
            ['項目', '数'],
            [
                ['処理済み', $results['processed']],
                ['スキップ', $results['skipped']],
                ['エラー', $results['errors']],
            ]
        );

        if (!empty($results['errors_list'])) {
            $this->error('エラー詳細:');
            foreach ($results['errors_list'] as $error) {
                $this->error("- {$error['file']}: {$error['error']}");
            }
        }

        // Display statistics
        $stats = $this->laravel->make(DmarcReportImportService::class)->getImportStatistics();
        $this->info('統計情報:');
        $this->table(
            ['項目', '数'],
            [
                ['総レポート数', $stats['total_reports']],
                ['総レコード数', $stats['total_records']],
                ['総メール数', $stats['total_emails']],
                ['認証成功数', $stats['auth_success_count']],
                ['認証失敗数', $stats['auth_failure_count']],
            ]
        );
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
} 