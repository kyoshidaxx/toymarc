<?php

namespace App\Http\Controllers;

use App\Models\DmarcReport;
use App\Services\DmarcReportImportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DmarcDashboardController extends Controller
{
    public function __construct(
        private DmarcReportImportService $importService
    ) {
    }

    /**
     * Display the DMARC dashboard.
     */
    public function index(): Response
    {
        /** @var \Illuminate\Support\Collection<int, \App\Models\DmarcReport> $reports */
        $reports = DmarcReport::with('records')
            ->orderBy('begin_date', 'desc')
            ->get()
            ->map(function ($report) {
                return [
                    'id' => $report->id,
                    'org_name' => $report->org_name,
                    'report_id' => $report->report_id,
                    'begin_date' => $report->begin_date,
                    'end_date' => $report->end_date,
                    'records_count' => $report->records->count(),
                    'records' => $report->records->map(function ($record) {
                        return [
                            'id' => $record->id,
                            'source_ip' => $record->source_ip,
                            'count' => $record->count,
                            'dkim_aligned' => $record->dkim_aligned,
                            'spf_aligned' => $record->spf_aligned,
                        ];
                    }),
                ];
            });

        $statistics = $this->importService->getImportStatistics();

        return Inertia::render('DmarcReports/Index', [
            'reports' => $reports,
            'statistics' => $statistics,
        ]);
    }

    /**
     * Show a specific DMARC report.
     */
    public function show(DmarcReport $report): Response
    {
        $report->load('records');

        return Inertia::render('DmarcReports/Show', [
            'report' => [
                'id' => $report->id,
                'org_name' => $report->org_name,
                'report_id' => $report->report_id,
                'begin_date' => $report->begin_date,
                'end_date' => $report->end_date,
                'raw_data' => $report->raw_data,
                'records' => $report->records->map(function ($record) {
                    return [
                        'id' => $record->id,
                        'source_ip' => $record->source_ip,
                        'count' => $record->count,
                        'dkim_aligned' => $record->dkim_aligned,
                        'spf_aligned' => $record->spf_aligned,
                        'disposition' => $record->disposition,
                        'reason' => $record->reason,
                    ];
                }),
            ],
        ]);
    }
} 