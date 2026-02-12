<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RengiatAttachment extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'entry_id',
        'path',
        'mime_type',
        'size_bytes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(RengiatEntry::class, 'entry_id');
    }
}
