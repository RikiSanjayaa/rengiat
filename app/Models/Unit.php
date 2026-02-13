<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'subdit_id',
        'name',
        'order_index',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

    public function scopeOrdered(Builder $query): void
    {
        $query
            ->orderBy(
                Subdit::query()
                    ->select('order_index')
                    ->whereColumn('subdits.id', 'units.subdit_id'),
            )
            ->orderBy('order_index')
            ->orderBy('name');
    }

    public function subdit(): BelongsTo
    {
        return $this->belongsTo(Subdit::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function rengiatEntries(): HasMany
    {
        return $this->hasMany(RengiatEntry::class);
    }
}
