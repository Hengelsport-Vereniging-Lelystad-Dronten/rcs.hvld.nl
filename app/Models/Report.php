<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * app/Models/Report.php
 *
 * Model voor periodieke rapportages (wekelijks, maandelijks, kwartaal, custom).
 * Slaat gegenereerde rapporten op met samengevatte statistieken en metadata.
 */
class Report extends Model
{
    protected $fillable = [
        'report_type',      // 'weekly', 'monthly', 'quarterly', 'custom'
        'period_start',     // Start datum/tijd van rapportageperiode
        'period_end',       // Eind datum/tijd van rapportageperiode
        'data_summary',     // JSON blob met statistieken
        'generated_at',     // Moment van generering
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'generated_at' => 'datetime',
        'data_summary' => 'array',
    ];

    /**
     * Haal alle overtredingen op die in deze rapportageperiode vallen.
     */
    public function overtredingen()
    {
        return Overtreding::whereBetween('created_at', [$this->period_start, $this->period_end])->get();
    }

    /**
     * Haal alle controlerondes op die in deze periode zijn afgerond.
     */
    public function controleRondes()
    {
        return ControleRonde::whereBetween('eind_tijd', [$this->period_start, $this->period_end])
            ->where('status', 'afgerond')
            ->get();
    }

    /**
     * Format rapport als leesbare string voor export/preview.
     */
    public function formattedSummary(): string
    {
        $data = $this->data_summary ?? [];
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
