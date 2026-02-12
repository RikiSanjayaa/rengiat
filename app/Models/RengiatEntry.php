<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RengiatEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id',
        'entry_date',
        'time_start',
        'description',
        'case_number',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
        ];
    }

    public function scopeChronological(Builder $query): void
    {
        $query
            ->orderByRaw('time_start IS NULL')
            ->orderBy('time_start')
            ->orderBy('created_at');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(RengiatAttachment::class, 'entry_id');
    }

    public function auditPayload(): array
    {
        return [
            'id' => $this->id,
            'unit_id' => $this->unit_id,
            'entry_date' => optional($this->entry_date)->toDateString(),
            'time_start' => $this->time_start,
            'description' => $this->description,
            'case_number' => $this->case_number,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
        ];
    }
}
