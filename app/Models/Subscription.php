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

    protected $casts = [
        'total_sessions' => 'int',
        'remaining_sessions' => 'int',
        'sessions_used' => 'int',
        'interval' => 'int',
        'expires_at' => 'datetime',
        'suspended_at' => 'datetime'
    ];

    protected $fillable = [
        'package_id',
        'user_id',
        'interval',
        'total_sessions',
        'remaining_sessions',
        'sessions_used',
        'expires_at',
        'suspended_at',
        'status'
    ];

    /**
     * Check if subscription has unlimited sessions.
     */
    public function isUnlimitedSessions(): bool
    {
        return is_null($this->total_sessions) || $this->total_sessions <= 0;
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
}
