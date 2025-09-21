<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;

class Phase extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'name',
        'start_at',
        'end_at',
        'status',
        'matches_per_player',
        'seed',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get all time slots for this phase.
     */
    public function timeSlots(): HasMany
    {
        return $this->hasMany(TimeSlot::class);
    }

    /**
     * Get all pairs for this phase.
     */
    public function pairs(): HasMany
    {
        return $this->hasMany(Pair::class);
    }

    /**
     * Get all matches for this phase.
     */
    public function matches(): HasMany
    {
        return $this->hasMany(BadmintonMatch::class);
    }

    /**
     * Get all users/players for this phase through pivot table.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'phase_players')
                    ->withTimestamps();
    }

    /**
     * Scope to get phases by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get draft phases.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope to get scheduled phases.
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope to get completed phases.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get archived phases.
     */
    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    /**
     * Scope to get active phases (scheduled or playing).
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['scheduled', 'playing']);
    }

    /**
     * Scope to get phases within date range.
     */
    public function scopeInDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_at', [$startDate, $endDate])
              ->orWhereBetween('end_at', [$startDate, $endDate])
              ->orWhere(function ($q2) use ($startDate, $endDate) {
                  $q2->where('start_at', '<=', $startDate)
                     ->where('end_at', '>=', $endDate);
              });
        });
    }

    /**
     * Check if the phase is currently active.
     */
    public function isActive(): bool
    {
        $now = now();
        return $this->start_at <= $now && 
               ($this->end_at === null || $this->end_at >= $now) &&
               in_array($this->status, ['scheduled', 'playing']);
    }

    /**
     * Check if the phase is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the phase is in draft status.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Get the phase status display name.
     */
    public function getStatusNameAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'scheduled' => 'Scheduled',
            'completed' => 'Completed',
            'archived' => 'Archived',
            default => 'Unknown'
        };
    }

    /**
     * Get the phase duration in hours.
     */
    public function getDurationInHours(): ?float
    {
        if (!$this->start_at || !$this->end_at) {
            return null;
        }

        return $this->start_at->diffInHours($this->end_at);
    }

    /**
     * Get pairs by match type for this phase.
     */
    public function getPairsByType(string $type)
    {
        return $this->pairs()->where('type', $type)->get();
    }

    /**
     * Get matches by type for this phase.
     */
    public function getMatchesByType(string $type)
    {
        return $this->matches()->where('type', $type)->get();
    }

    /**
     * Get phase display name in Vietnamese.
     */
    public function getPhaseDisplayName(): string
    {
        return match ($this->type) {
            'vong_loai' => 'Vòng Loại',
            'ban_ket' => 'Bán Kết',
            'chung_ket' => 'Chung Kết',
            default => $this->name
        };
    }

    /**
     * Check if phase can transition to new status.
     */
    public function canTransitionTo(string $newStatus): bool
    {
        $validTransitions = [
            'draft' => ['scheduled'],
            'scheduled' => ['playing', 'draft'],
            'playing' => ['completed'],
            'completed' => ['archived'],
            'archived' => []
        ];

        return in_array($newStatus, $validTransitions[$this->status] ?? []);
    }

    /**
     * Get players in this phase.
     */
    public function getPlayers()
    {
        // First try to get players from the phase_players pivot table
        $playersFromPivot = $this->users;
        
        if ($playersFromPivot->count() > 0) {
            return $playersFromPivot;
        }
        
        // Fallback: get players from pairs if pivot table is empty
        $userIds = $this->pairs()
            ->select(['user_lo_id', 'user_hi_id'])
            ->get()
            ->flatMap(function ($pair) {
                return [$pair->user_lo_id, $pair->user_hi_id];
            })
            ->unique();

        return User::whereIn('id', $userIds)->get();
    }

    /**
     * Get players count by gender.
     */
    public function getPlayersCountByGender(): array
    {
        $players = $this->getPlayers();
        
        return [
            'male' => $players->where('gender', 'M')->count(),
            'female' => $players->where('gender', 'F')->count(),
            'total' => $players->count()
        ];
    }

    /**
     * Check if pairs generation is possible.
     */
    public function canGeneratePairs(): bool
    {
        return $this->status === 'draft' && $this->getPlayers()->count() >= 4;
    }

    /**
     * Check if matches scheduling is possible.
     */
    public function canScheduleMatches(): bool
    {
        return $this->pairs()->count() > 0 && 
               in_array($this->status, ['draft', 'scheduled']);
    }
}
