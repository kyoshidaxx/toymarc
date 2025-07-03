<?php

namespace App\Http\Controllers;

use App\Models\DmarcReport;
use App\Models\DmarcRecord;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index(): Response
    {
        $statistics = Cache::remember('dashboard_statistics', 300, function () {
            $totalReports = DmarcReport::query()->count();
            $totalRecords = DmarcRecord::query()->count();
            $totalEmails = DmarcRecord::query()->sum('count');
            
            // 認証成功率
            $authSuccessCount = DmarcRecord::query()->where(function ($query) {
                $query->where('dkim_aligned', true)
                      ->orWhere('spf_aligned', true);
            })->sum('count');
            
            $authSuccessRate = $totalEmails > 0 ? round(($authSuccessCount / $totalEmails) * 100, 2) : 0;
            
            // 最近30日間の統計
            $thirtyDaysAgo = now()->subDays(30);
            $recentReports = DmarcReport::query()->where('begin_date', '>=', $thirtyDaysAgo)->count();
            $recentEmails = DmarcRecord::query()->whereHas('dmarcReport', function ($query) use ($thirtyDaysAgo) {
                $query->where('begin_date', '>=', $thirtyDaysAgo);
            })->sum('count');
            
            // ポリシー別統計
            $policyBreakdown = DmarcReport::query()->selectRaw('policy_p, COUNT(*) as count')
                ->groupBy('policy_p')
                ->pluck('count', 'policy_p')
                ->toArray();
            
            // 組織別統計（上位5件）
            $topOrganizations = DmarcReport::query()->selectRaw('org_name, COUNT(*) as report_count')
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
            return DmarcReport::query()->with('records')
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

        // 認証データを明示的に取得
        $user = Auth::user();
        $authData = [
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ] : null,
        ];

        return Inertia::render('Dashboard/Index', [
            'statistics' => $statistics,
            'recentActivity' => $recentActivity,
            'auth' => $authData, // 明示的に認証データを渡す
        ]);
    }
} 