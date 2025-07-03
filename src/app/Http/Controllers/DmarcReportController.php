<?php

namespace App\Http\Controllers;

use App\Models\DmarcReport;
use App\Models\DmarcRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DmarcReportController extends Controller
{
    /**
     * Display a listing of DMARC reports.
     */
    public function index(Request $request): JsonResponse
    {
        $query = DmarcReport::with('records');

        // Apply filters
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->byDateRange($request->start_date, $request->end_date);
        }

        if ($request->filled('org_name')) {
            $query->byOrgName($request->org_name);
        }

        if ($request->filled('policy_domain')) {
            $query->byPolicyDomain($request->policy_domain);
        }

        // Apply search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('org_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('report_id', 'like', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $request->get('per_page', config('dmarc.dashboard.max_records_per_page', 50));
        $reports = $query->orderBy('begin_date', 'desc')->paginate($perPage);

        return response()->json([
            'data' => $reports->items(),
            'pagination' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
            ],
        ]);
    }

    /**
     * Display the specified DMARC report.
     */
    public function show(int $id): JsonResponse
    {
        $report = DmarcReport::with('records')->findOrFail($id);

        return response()->json([
            'data' => $report,
        ]);
    }

    /**
     * Get DMARC report statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $cacheKey = 'dmarc_statistics_' . md5($request->fullUrl());
        
        $statistics = Cache::remember($cacheKey, 3600, function () use ($request) {
            $query = DmarcReport::with('records');

            // Apply date range filter
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->byDateRange($request->start_date, $request->end_date);
            }

            $reports = $query->get();

            $totalReports = $reports->count();
            $totalEmails = $reports->sum(function ($report) {
                return $report->records->sum('count');
            });

            $authSuccessCount = $reports->sum(function ($report) {
                return $report->records->where('dkim_aligned', true)
                    ->orWhere('spf_aligned', true)
                    ->sum('count');
            });

            $authFailureCount = $totalEmails - $authSuccessCount;

            $policyBreakdown = [
                'none' => $reports->where('policy_p', 'none')->count(),
                'quarantine' => $reports->where('policy_p', 'quarantine')->count(),
                'reject' => $reports->where('policy_p', 'reject')->count(),
            ];

            $topSourceIps = DmarcRecord::select('source_ip')
                ->selectRaw('SUM(count) as total_count')
                ->selectRaw('SUM(CASE WHEN dkim_aligned = 1 OR spf_aligned = 1 THEN count ELSE 0 END) as success_count')
                ->when($request->filled('start_date') && $request->filled('end_date'), function ($query) use ($request) {
                    return $query->whereHas('dmarcReport', function ($q) use ($request) {
                        $q->byDateRange($request->start_date, $request->end_date);
                    });
                })
                ->groupBy('source_ip')
                ->orderByDesc('total_count')
                ->limit(10)
                ->get()
                ->map(function ($record) {
                    $record->success_rate = $record->total_count > 0 
                        ? round(($record->success_count / $record->total_count) * 100, 2)
                        : 0;
                    return $record;
                });

            return [
                'total_reports' => $totalReports,
                'total_emails' => $totalEmails,
                'auth_success_count' => $authSuccessCount,
                'auth_failure_count' => $authFailureCount,
                'auth_success_rate' => $totalEmails > 0 ? round(($authSuccessCount / $totalEmails) * 100, 2) : 0,
                'policy_breakdown' => $policyBreakdown,
                'top_source_ips' => $topSourceIps,
            ];
        });

        return response()->json([
            'data' => $statistics,
        ]);
    }

    /**
     * Get DMARC records with filters.
     */
    public function records(Request $request): JsonResponse
    {
        $query = DmarcRecord::with('dmarcReport');

        // Apply filters
        if ($request->filled('source_ip')) {
            $query->where('source_ip', 'like', "%{$request->source_ip}%");
        }

        if ($request->filled('auth_result')) {
            switch ($request->auth_result) {
                case 'success':
                    $query->where(function ($q) {
                        $q->where('dkim_aligned', true)->orWhere('spf_aligned', true);
                    });
                    break;
                case 'failure':
                    $query->where('dkim_aligned', false)->where('spf_aligned', false);
                    break;
            }
        }

        if ($request->filled('disposition')) {
            $query->where('disposition', $request->disposition);
        }

        // Apply date range filter through relationship
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereHas('dmarcReport', function ($q) use ($request) {
                $q->byDateRange($request->start_date, $request->end_date);
            });
        }

        $perPage = $request->get('per_page', config('dmarc.dashboard.max_records_per_page', 50));
        $records = $query->orderBy('count', 'desc')->paginate($perPage);

        return response()->json([
            'data' => $records->items(),
            'pagination' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
            ],
        ]);
    }

    /**
     * Get available filter options.
     */
    public function filterOptions(): JsonResponse
    {
        $options = Cache::remember('dmarc_filter_options', 3600, function () {
            return [
                'organizations' => DmarcReport::distinct()->pluck('org_name')->filter()->values(),
                'policy_domains' => DmarcReport::distinct()->pluck('policy_domain')->filter()->values(),
                'dispositions' => DmarcRecord::distinct()->pluck('disposition')->filter()->values(),
                'auth_results' => ['success', 'failure'],
            ];
        });

        return response()->json([
            'data' => $options,
        ]);
    }
} 