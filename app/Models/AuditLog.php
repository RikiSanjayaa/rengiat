<?php

namespace App\Models;

use App\Enums\AuditLogAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'actor_user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'action' => AuditLogAction::class,
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
