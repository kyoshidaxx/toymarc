<?php

namespace App\Http\Controllers;

use App\Models\DmarcReport;
use App\Models\DmarcRecord;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Cache;

class AnalyticsController extends Controller
{
    /**
     * Display the analytics page.
     */
    public function index(Request $request): Response
    {
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $analytics = Cache::remember("analytics_{$startDate}_{$endDate}", 300, function () use ($startDate, $endDate) {
            // 日別統計
            $dailyStats = DmarcReport::query()->selectRaw('
                DATE(begin_date) as date,
                COUNT(*) as reports_count,
                SUM((SELECT SUM(count) FROM dmarc_records WHERE dmarc_records.dmarc_report_id = dmarc_reports.id)) as emails_count
            ')
            ->whereBetween('begin_date', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

            // 認証結果別統計
            $authStats = DmarcRecord::query()->selectRaw('
                SUM(CASE WHEN dkim_aligned = 1 AND spf_aligned = 1 THEN count ELSE 0 END) as both_success,
                SUM(CASE WHEN dkim_aligned = 1 AND spf_aligned = 0 THEN count ELSE 0 END) as dkim_only,
                SUM(CASE WHEN dkim_aligned = 0 AND spf_aligned = 1 THEN count ELSE 0 END) as spf_only,
                SUM(CASE WHEN dkim_aligned = 0 AND spf_aligned = 0 THEN count ELSE 0 END) as both_failed
            ')
            ->whereHas('dmarcReport', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('begin_date', [$startDate, $endDate]);
            })
            ->first();

            // 送信元IP別統計（上位10件）
            $topSourceIps = DmarcRecord::query()->selectRaw('
                source_ip,
                SUM(count) as total_emails,
                SUM(CASE WHEN dkim_aligned = 1 OR spf_aligned = 1 THEN count ELSE 0 END) as success_emails
            ')
            ->whereHas('dmarcReport', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('begin_date', [$startDate, $endDate]);
            })
            ->groupBy('source_ip')
            ->orderByDesc('total_emails')
            ->limit(10)
            ->get()
            ->map(function ($ip) {
                $ip->success_rate = $ip->total_emails > 0 
                    ? round(($ip->success_emails / $ip->total_emails) * 100, 2)
                    : 0;
                return $ip;
            });

            // 組織別統計
            $orgStats = DmarcReport::query()->selectRaw('
                org_name,
                COUNT(*) as reports_count,
                SUM((SELECT SUM(count) FROM dmarc_records WHERE dmarc_records.dmarc_report_id = dmarc_reports.id)) as emails_count
            ')
            ->whereBetween('begin_date', [$startDate, $endDate])
            ->groupBy('org_name')
            ->orderByDesc('emails_count')
            ->limit(10)
            ->get();

            // ポリシー別統計
            $policyStats = DmarcReport::query()->selectRaw('
                policy_p,
                COUNT(*) as reports_count,
                SUM((SELECT SUM(count) FROM dmarc_records WHERE dmarc_records.dmarc_report_id = dmarc_reports.id)) as emails_count
            ')
            ->whereBetween('begin_date', [$startDate, $endDate])
            ->groupBy('policy_p')
            ->get();

            return [
                'dailyStats' => $dailyStats,
                'authStats' => $authStats,
                'topSourceIps' => $topSourceIps,
                'orgStats' => $orgStats,
                'policyStats' => $policyStats,
                'dateRange' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
            ];
        });

        return Inertia::render('Analytics/Index', [
            'analytics' => $analytics,
        ]);
    }
} 