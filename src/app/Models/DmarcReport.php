<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $org_name
 * @property string $email
 * @property string $report_id
 * @property \Carbon\Carbon $begin_date
 * @property \Carbon\Carbon $end_date
 * @property string $policy_domain
 * @property string $policy_p
 * @property int $policy_pct
 * @property array $raw_data
 * @property string $file_hash
 * @property-read \Illuminate\Database\Eloquent\Collection|DmarcRecord[] $records
 * @method static \Illuminate\Database\Eloquent\Builder byDateRange(string $startDate, string $endDate)
 * @method static \Illuminate\Database\Eloquent\Builder byOrgName(string $orgName)
 * @method static \Illuminate\Database\Eloquent\Builder byPolicyDomain(string $domain)
 */
class DmarcReport extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'org_name',
        'email',
        'report_id',
        'begin_date',
        'end_date',
        'policy_domain',
        'policy_p',
        'policy_pct',
        'raw_data',
        'file_hash',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'begin_date' => 'datetime',
        'end_date' => 'datetime',
        'raw_data' => 'array',
        'policy_pct' => 'integer',
    ];

    /**
     * Get the records for the DMARC report.
     */
    public function records(): HasMany
    {
        return $this->hasMany(DmarcRecord::class);
    }

    /**
     * Get the authentication success rate attribute.
     */
    public function getAuthSuccessRateAttribute(): float
    {
        $totalRecords = $this->records()->count();
        if ($totalRecords === 0) {
            return 0.0;
        }

        $successRecords = $this->records()
            ->where('dkim_aligned', true)
            ->orWhere('spf_aligned', true)
            ->count();

        return round(($successRecords / $totalRecords) * 100, 2);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeByDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('begin_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to filter by organization name.
     */
    public function scopeByOrgName(Builder $query, string $orgName): Builder
    {
        return $query->where('org_name', 'like', "%{$orgName}%");
    }

    /**
     * Scope a query to filter by policy domain.
     */
    public function scopeByPolicyDomain(Builder $query, string $domain): Builder
    {
        return $query->where('policy_domain', 'like', "%{$domain}%");
    }

    /**
     * Get summary data for dashboard.
     * 
     * @return array<string, mixed>
     */
    public static function getSummaryData(): array
    {
        $reports = self::with('records')->get();

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

        return [
            'total_reports' => $totalReports,
            'total_emails' => $totalEmails,
            'auth_success_count' => $authSuccessCount,
            'auth_failure_count' => $authFailureCount,
            'policy_breakdown' => $policyBreakdown,
        ];
    }
} 