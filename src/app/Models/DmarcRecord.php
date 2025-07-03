<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $dmarc_report_id
 * @property string $source_ip
 * @property int $count
 * @property string $disposition
 * @property bool $dkim_aligned
 * @property string $dkim_result
 * @property bool $spf_aligned
 * @property string $spf_result
 * @property-read \App\Models\DmarcReport $dmarcReport
 * @method static \Illuminate\Database\Eloquent\Builder|DmarcRecord query()
 * @method static \Illuminate\Database\Query\Builder selectRaw(string $expression)
 */
class DmarcRecord extends Model
{
    use HasFactory/*<DmarcRecord>*/;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'dmarc_report_id',
        'source_ip',
        'count',
        'disposition',
        'dkim_aligned',
        'dkim_result',
        'spf_aligned',
        'spf_result',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'count' => 'integer',
        'dkim_aligned' => 'boolean',
        'spf_aligned' => 'boolean',
    ];

    /**
     * @return BelongsTo<DmarcReport, DmarcRecord>
     */
    public function dmarcReport(): BelongsTo
    {
        return $this->belongsTo(DmarcReport::class);
    }

    /**
     * Check if the record has successful authentication.
     */
    public function isAuthenticated(): bool
    {
        return $this->dkim_aligned || $this->spf_aligned;
    }

    /**
     * Get the authentication result summary.
     */
    public function getAuthResultSummaryAttribute(): string
    {
        if ($this->dkim_aligned && $this->spf_aligned) {
            return 'DKIM+SPF';
        } elseif ($this->dkim_aligned) {
            return 'DKIM';
        } elseif ($this->spf_aligned) {
            return 'SPF';
        } else {
            return 'None';
        }
    }
} 