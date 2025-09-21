<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class TimeSlot extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'phase_id',
        'court_id',
        'start_at',
        'end_at',
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
     * Get the phase that owns this time slot.
     */
    public function phase(): BelongsTo
    {
        return $this->belongsTo(Phase::class);
    }

    /**
     * Get the court that owns this time slot.
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /**
     * Get all matches scheduled for this time slot.
     */
    public function matches(): HasMany
    {
        return $this->hasMany(BadmintonMatch::class);
    }

    /**
     * Scope to get time slots for a specific phase.
     */
    public function scopeForPhase($query, int $phaseId)
    {
        return $query->where('phase_id', $phaseId);
    }

    /**
     * Scope to get time slots for a specific court.
     */
    public function scopeForCourt($query, int $courtId)
    {
        return $query->where('court_id', $courtId);
    }

    /**
     * Scope to get available time slots (without matches).
     */
    public function scopeAvailable($query)
    {
        return $query->whereDoesntHave('matches');
    }

    /**
     * Scope to get occupied time slots (with matches).
     */
    public function scopeOccupied($query)
    {
        return $query->whereHas('matches');
    }

    /**
     * Scope to get time slots within a date range.
     */
    public function scopeInDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('start_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get time slots for today.
     */
    public function scopeToday($query)
    {
        $today = now()->startOfDay();
        $tomorrow = now()->addDay()->startOfDay();
        
        return $query->whereBetween('start_at', [$today, $tomorrow]);
    }

    /**
     * Scope to get upcoming time slots.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_at', '>', now());
    }

    /**
     * Scope to get past time slots.
     */
    public function scopePast($query)
    {
        return $query->where('end_at', '<', now());
    }

    /**
     * Scope to get currently active time slots.
     */
    public function scopeActive($query)
    {
        $now = now();
        return $query->where('start_at', '<=', $now)
                    ->where('end_at', '>=', $now);
    }

    /**
     * Check if this time slot is available (no matches assigned).
     */
    public function isAvailable(): bool
    {
        return $this->matches()->count() === 0;
    }

    /**
     * Check if this time slot is occupied (has matches assigned).
     */
    public function isOccupied(): bool
    {
        return $this->matches()->count() > 0;
    }

    /**
     * Check if this time slot is currently active.
     */
    public function isActive(): bool
    {
        $now = now();
        return $this->start_at <= $now && $this->end_at >= $now;
    }

    /**
     * Check if this time slot is in the past.
     */
    public function isPast(): bool
    {
        return $this->end_at < now();
    }

    /**
     * Check if this time slot is upcoming.
     */
    public function isUpcoming(): bool
    {
        return $this->start_at > now();
    }

    /**
     * Get the duration of this time slot in minutes.
     */
    public function getDurationInMinutes(): int
    {
        return $this->start_at->diffInMinutes($this->end_at);
    }

    /**
     * Get the duration of this time slot in hours.
     */
    public function getDurationInHours(): float
    {
        return $this->start_at->diffInHours($this->end_at, true);
    }

    /**
     * Check if this time slot conflicts with another time slot.
     */
    public function conflictsWith(TimeSlot $other): bool
    {
        if ($this->court_id !== $other->court_id) {
            return false;
        }

        return $this->start_at < $other->end_at && $this->end_at > $other->start_at;
    }

    /**
     * Get a formatted time range string.
     */
    public function getTimeRangeAttribute(): string
    {
        return $this->start_at->format('H:i') . ' - ' . $this->end_at->format('H:i');
    }

    /**
     * Get a formatted date and time range string.
     */
    public function getDateTimeRangeAttribute(): string
    {
        $startFormat = $this->start_at->format('Y-m-d H:i');
        $endFormat = $this->end_at->format('H:i');
        
        return $startFormat . ' - ' . $endFormat;
    }
}
