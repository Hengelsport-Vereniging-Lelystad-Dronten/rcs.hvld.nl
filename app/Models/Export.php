<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Export extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'original_filename',
        'file_path',
        'export_type',
        'record_count',
        'filters',
        'selected_records',
        'created_by',
        'downloaded_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'selected_records' => 'array',
        'downloaded_at' => 'datetime',
    ];

    /**
     * Relatie: Export behoort tot een User (maker).
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope voor exports van een bepaald type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('export_type', $type);
    }

    /**
     * Scope voor recente exports.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Markeer als gedownload.
     */
    public function markAsDownloaded()
    {
        $this->update(['downloaded_at' => now()]);
    }

    /**
     * Check of het bestand bestaat.
     */
    public function fileExists()
    {
        return \Storage::exists($this->file_path);
    }

    /**
     * Get download URL.
     */
    public function getDownloadUrl()
    {
        return route('beheer.exports.download', $this->id);
    }
}
