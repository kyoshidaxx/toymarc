<?php

namespace App\Services;

use App\Models\DmarcReport;
use App\Models\DmarcRecord;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DmarcReportImportException extends Exception
{
}

class DmarcReportImportService
{
    public function __construct(
        private DmarcReportParserService $parserService
    ) {
    }

    /**
     * Import DMARC reports from directory.
     */
    public function importReportsFromDirectory(string $directory): array
    {
        $results = [
            'processed' => 0,
            'skipped' => 0,
            'errors' => 0,
            'errors_list' => [],
        ];

        Log::info('DMARCレポート取り込み開始', [
            'directory' => $directory,
        ]);

        if (!Storage::disk('dmarc')->exists($directory)) {
            throw new DmarcReportImportException("ディレクトリが存在しません: {$directory}");
        }

        $files = Storage::disk('dmarc')->files($directory);
        $xmlFiles = array_filter($files, function ($file) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'xml';
        });

        Log::info('DMARCレポート取り込み対象ファイル数', [
            'total_files' => count($xmlFiles),
        ]);

        foreach ($xmlFiles as $file) {
            try {
                $this->importSingleReport($file);
                $results['processed']++;
            } catch (DmarcReportImportException $e) {
                $results['errors']++;
                $results['errors_list'][] = [
                    'file' => $file,
                    'error' => $e->getMessage(),
                ];
                Log::error('DMARCレポート取り込みエラー', [
                    'file' => $file,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('DMARCレポート取り込み完了', [
            'processed' => $results['processed'],
            'skipped' => $results['skipped'],
            'errors' => $results['errors'],
        ]);

        return $results;
    }

    /**
     * Import a single DMARC report file.
     */
    private function importSingleReport(string $filePath): void
    {
        $content = Storage::disk('dmarc')->get($filePath);
        if ($content === false) {
            throw new DmarcReportImportException("ファイルの読み込みに失敗しました: {$filePath}");
        }

        $fileHash = hash('sha256', $content);

        // Check if report already exists
        if (DmarcReport::query()->where('file_hash', $fileHash)->exists()) {
            Log::info('DMARCレポートは既に取り込み済みです', [
                'file' => $filePath,
                'hash' => $fileHash,
            ]);
            return;
        }

        // Parse XML content
        $report = $this->parserService->parseXmlReport($content);

        // Check for duplicate report by metadata
        if ($this->isDuplicateReport($report)) {
            Log::info('DMARCレポートは重複しています', [
                'file' => $filePath,
                'report_id' => $report->report_id,
                'org_name' => $report->org_name,
            ]);
            return;
        }

        // Save report and records
        DB::transaction(function () use ($report, $fileHash) {
            $report->file_hash = $fileHash;
            $report->save();

            foreach ($report->records as $record) {
                $record->dmarc_report_id = $report->id;
                $record->save();
            }
        });

        Log::info('DMARCレポート取り込み成功', [
            'file' => $filePath,
            'report_id' => $report->report_id,
            'org_name' => $report->org_name,
            'records_count' => $report->records->count(),
        ]);
    }

    /**
     * Check if report is duplicate based on metadata.
     */
    private function isDuplicateReport(DmarcReport $report): bool
    {
        return DmarcReport::query()->where('report_id', $report->report_id)
            ->where('org_name', $report->org_name)
            ->where('begin_date', $report->begin_date)
            ->where('end_date', $report->end_date)
            ->exists();
    }

    /**
     * Validate report metadata.
     */
    public function validateReportMetadata(array $metadata): bool
    {
        $requiredFields = ['org_name', 'email', 'report_id', 'begin_date', 'end_date'];
        
        foreach ($requiredFields as $field) {
            if (empty($metadata[$field])) {
                return false;
            }
        }

        // Validate date range
        $beginDate = strtotime($metadata['begin_date']);
        $endDate = strtotime($metadata['end_date']);
        
        if ($beginDate === false || $endDate === false || $beginDate >= $endDate) {
            return false;
        }

        return true;
    }

    /**
     * Get import statistics.
     */
    public function getImportStatistics(): array
    {
        $totalReports = DmarcReport::query()->count();
        $totalRecords = DmarcRecord::query()->count();
        $totalEmails = DmarcRecord::query()->sum('count');
        
        $authSuccessCount = DmarcRecord::query()->where('dkim_aligned', true)
            ->orWhere('spf_aligned', true)
            ->sum('count');

        return [
            'total_reports' => $totalReports,
            'total_records' => $totalRecords,
            'total_emails' => $totalEmails,
            'auth_success_count' => $authSuccessCount,
            'auth_failure_count' => $totalEmails - $authSuccessCount,
        ];
    }
} 