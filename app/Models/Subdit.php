<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subdit extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'order_index',
    ];

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('order_index')->orderBy('name');
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
