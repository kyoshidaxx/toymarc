<?php

namespace App\Http\Controllers;

use App\Models\DmarcReport;
use App\Models\DmarcRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

/**
 * @property Request $request
 */
class DmarcReportController extends Controller
{
    /**
     * Display a listing of DMARC reports.
     */
    public function index(Request $request): JsonResponse
    {
        // Validate request parameters
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'org_name' => 'nullable|string|max:255',
            'policy_domain' => 'nullable|string|max:255',
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $query = DmarcReport::with('records');

            // Apply filters
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('begin_date', [$request->get('start_date'), $request->get('end_date')]);
            }

            if ($request->filled('org_name')) {
                $query->where('org_name', 'like', "%{$request->get('org_name')}%");
            }

            if ($request->filled('policy_domain')) {
                $query->where('policy_domain', 'like', "%{$request->get('policy_domain')}%");
            }

            // Apply search
            if ($request->filled('search')) {
                $search = $request->get('search');
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
                'success' => true,
                'data' => $reports->items(),
                'pagination' => [
                    'current_page' => $reports->currentPage(),
                    'last_page' => $reports->lastPage(),
                    'per_page' => $reports->perPage(),
                    'total' => $reports->total(),
                    'from' => $reports->firstItem(),
                    'to' => $reports->lastItem(),
                ],
                'meta' => [
                    'total_reports' => $reports->total(),
                    'filtered_reports' => $reports->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve DMARC reports',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Display the specified DMARC report.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $report = DmarcReport::with('records')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $report,
                'meta' => [
                    'records_count' => $report->records->count(),
                    'total_emails' => $report->records->sum('count'),
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'DMARC report not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve DMARC report',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get DMARC report statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        // Validate request parameters
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $cacheKey = 'dmarc_statistics_' . md5($request->fullUrl());
            
            $statistics = Cache::remember($cacheKey, 3600, function () use ($request) {
                $query = DmarcReport::with('records');

                // Apply date range filter
                if ($request->filled('start_date') && $request->filled('end_date')) {
                    $query->whereBetween('begin_date', [$request->get('start_date'), $request->get('end_date')]);
                }

                $reports = $query->get();

                $totalReports = $reports->count();
                $totalEmails = $reports->sum(function ($report) {
                    return $report->records->sum('count');
                });

                $authSuccessCount = $reports->sum(function ($report) {
                    return $report->records->filter(function ($record) {
                        return $record->dkim_aligned || $record->spf_aligned;
                    })->sum('count');
                });

                $authFailureCount = $totalEmails - $authSuccessCount;

                $policyBreakdown = [
                    'none' => $reports->where('policy_p', 'none')->count(),
                    'quarantine' => $reports->where('policy_p', 'quarantine')->count(),
                    'reject' => $reports->where('policy_p', 'reject')->count(),
                ];

                $topSourceIps = DmarcRecord::query()->select('source_ip')
                    ->selectRaw('SUM(count) as total_count')
                    ->selectRaw('SUM(CASE WHEN dkim_aligned = 1 OR spf_aligned = 1 THEN count ELSE 0 END) as success_count')
                    ->when($request->filled('start_date') && $request->filled('end_date'), function ($query) use ($request) {
                        return $query->whereHas('dmarcReport', function ($q) use ($request) {
                            $q->whereBetween('begin_date', [$request->get('start_date'), $request->get('end_date')]);
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
                'success' => true,
                'data' => $statistics,
                'meta' => [
                    'cache_ttl' => 3600,
                    'generated_at' => now()->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get DMARC records with filters.
     */
    public function records(Request $request): JsonResponse
    {
        // Validate request parameters
        $validator = Validator::make($request->all(), [
            'source_ip' => 'nullable|string|max:45',
            'auth_result' => 'nullable|in:success,failure',
            'disposition' => 'nullable|string|max:50',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $query = DmarcRecord::query()->with('dmarcReport');

            // Apply filters
            if ($request->filled('source_ip')) {
                $query->where('source_ip', 'like', "%{$request->get('source_ip')}%");
            }

            if ($request->filled('auth_result')) {
                switch ($request->get('auth_result')) {
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
                $query->where('disposition', $request->get('disposition'));
            }

            // Apply date range filter through relationship
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereHas('dmarcReport', function ($q) use ($request) {
                    $q->whereBetween('begin_date', [$request->get('start_date'), $request->get('end_date')]);
                });
            }

            $perPage = $request->get('per_page', config('dmarc.dashboard.max_records_per_page', 50));
            $records = $query->orderBy('count', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $records->items(),
                'pagination' => [
                    'current_page' => $records->currentPage(),
                    'last_page' => $records->lastPage(),
                    'per_page' => $records->perPage(),
                    'total' => $records->total(),
                    'from' => $records->firstItem(),
                    'to' => $records->lastItem(),
                ],
                'meta' => [
                    'total_records' => $records->total(),
                    'filtered_records' => $records->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve DMARC records',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get available filter options.
     */
    public function filterOptions(): JsonResponse
    {
        try {
            $options = Cache::remember('dmarc_filter_options', 3600, function () {
                return [
                    'organizations' => DmarcReport::query()->distinct()->pluck('org_name')->filter()->values(),
                    'policy_domains' => DmarcReport::query()->distinct()->pluck('policy_domain')->filter()->values(),
                    'dispositions' => DmarcRecord::query()->distinct()->pluck('disposition')->filter()->values(),
                    'auth_results' => ['success', 'failure'],
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $options,
                'meta' => [
                    'cache_ttl' => 3600,
                    'generated_at' => now()->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve filter options',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
} 