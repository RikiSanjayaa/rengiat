<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportSetting extends Model
{
    protected $fillable = [
        'user_id',
        'atas_nama',
        'jabatan',
        'nama_penandatangan',
        'pangkat_nrp',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the TDD has any meaningful content filled in.
     */
    public function hasTdd(): bool
    {
        return trim($this->atas_nama) !== ''
            || trim($this->jabatan) !== ''
            || trim($this->nama_penandatangan) !== ''
            || trim($this->pangkat_nrp) !== '';
    }
}
