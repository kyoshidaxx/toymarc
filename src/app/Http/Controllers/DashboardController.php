<?php

namespace App\Http\Controllers;

use App\Models\DmarcReport;
use App\Models\DmarcRecord;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index(): Response
    {
        $statistics = Cache::remember('dashboard_statistics', 300, function () {
            $totalReports = DmarcReport::count();
            $totalRecords = DmarcRecord::count();
            $totalEmails = DmarcRecord::sum('count');
            
            // 認証成功率
            $authSuccessCount = DmarcRecord::where(function ($query) {
                $query->where('dkim_aligned', true)
                      ->orWhere('spf_aligned', true);
            })->sum('count');
            
            $authSuccessRate = $totalEmails > 0 ? round(($authSuccessCount / $totalEmails) * 100, 2) : 0;
            
            // 最近30日間の統計
            $thirtyDaysAgo = now()->subDays(30);
            $recentReports = DmarcReport::where('begin_date', '>=', $thirtyDaysAgo)->count();
            $recentEmails = DmarcRecord::whereHas('dmarcReport', function ($query) use ($thirtyDaysAgo) {
                $query->where('begin_date', '>=', $thirtyDaysAgo);
            })->sum('count');
            
            // ポリシー別統計
            $policyBreakdown = DmarcReport::selectRaw('policy_p, COUNT(*) as count')
                ->groupBy('policy_p')
                ->pluck('count', 'policy_p')
                ->toArray();
            
            // 組織別統計（上位5件）
            $topOrganizations = DmarcReport::selectRaw('org_name, COUNT(*) as report_count')
                ->groupBy('org_name')
                ->orderByDesc('report_count')
                ->limit(5)
                ->get();
            
            return [
                'total_reports' => $totalReports,
                'total_records' => $totalRecords,
                'total_emails' => $totalEmails,
                'auth_success_rate' => $authSuccessRate,
                'recent_reports' => $recentReports,
                'recent_emails' => $recentEmails,
                'policy_breakdown' => $policyBreakdown,
                'top_organizations' => $topOrganizations,
            ];
        });

        $recentActivity = Cache::remember('dashboard_recent_activity', 60, function () {
            return DmarcReport::with('records')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($report) {
                    return [
                        'id' => $report->id,
                        'org_name' => $report->org_name,
                        'report_id' => $report->report_id,
                        'begin_date' => $report->begin_date,
                        'end_date' => $report->end_date,
                        'records_count' => $report->records->count(),
                        'total_emails' => $report->records->sum('count'),
                        'created_at' => $report->created_at,
                    ];
                });
        });

        return Inertia::render('Dashboard/Index', [
            'statistics' => $statistics,
            'recentActivity' => $recentActivity,
        ]);
    }
} 