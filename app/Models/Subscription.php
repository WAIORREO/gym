<?php

namespace App\Models;

use App\Models\Traits\HasCycle;
use App\Models\Traits\HasPackage;
use App\Models\Traits\HasService;
use App\Models\Traits\HasUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, HasService, HasCycle, HasPackage, HasUser, SoftDeletes;

    protected $appends = ['effective_status'];

    protected $casts = [
        'total_sessions' => 'int',
        'remaining_sessions' => 'int',
        'sessions_used' => 'int',
        'interval' => 'int',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'frozen_at' => 'datetime',
        'suspended_at' => 'datetime'
    ];

    protected $fillable = [
        'renewed_from_id',
        'package_id',
        'user_id',
        'interval',
        'starts_at',
        'total_sessions',
        'remaining_sessions',
        'sessions_used',
        'expires_at',
        'frozen_at',
        'suspended_at',
        'status'
    ];

    /**
     * Check if subscription has unlimited sessions.
     */
    public function isUnlimitedSessions(): bool
    {
        return is_null($this->total_sessions);
    }

    /**
     * Check if subscription date has expired.
     */
    public function isExpiredByDate(): bool
    {
        return $this->expires_at && \Carbon\Carbon::parse($this->expires_at)->endOfDay()->isPast();
    }

    /**
     * Check if subscription sessions are depleted.
     */
    public function isDepletedBySessions(): bool
    {
        return !$this->isUnlimitedSessions() && ($this->remaining_sessions <= 0);
    }

    public function getEffectiveStatusAttribute(): string
    {
        if ($this->status !== 'active') return $this->status;
        if ($this->starts_at && $this->starts_at->copy()->startOfDay()->isFuture()) return 'scheduled';
        if ($this->isExpiredByDate()) return 'expired';
        if ($this->isDepletedBySessions()) return 'completed';
        return 'active';
    }

    public function scopeEligible($query)
    {
        return $query->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()->startOfDay()))
            ->where(fn ($q) => $q->whereNull('total_sessions')->orWhere(fn ($q) => $q->where('total_sessions','>',0)->where('remaining_sessions','>',0)));
    }
}
