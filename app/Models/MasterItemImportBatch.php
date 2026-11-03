<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterItemImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_code',
        'file_name',
        'user_id',
        'total_rows',
        'created_count',
        'updated_count',
        'critical_changes_count',
        'impact_summary',
    ];

    protected $casts = [
        'total_rows' => 'integer',
        'created_count' => 'integer',
        'updated_count' => 'integer',
        'critical_changes_count' => 'integer',
        'impact_summary' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(MasterItemLog::class, 'upload_batch_id', 'batch_code');
    }

    /**
     * Determines whether this upload batch altered existing production master records.
     */
    public function isProblematic(): bool
    {
        return $this->updated_count > 0 || $this->critical_changes_count > 0;
    }
}
