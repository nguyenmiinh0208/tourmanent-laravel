<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Court extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get all time slots for this court.
     */
    public function timeSlots(): HasMany
    {
        return $this->hasMany(TimeSlot::class);
    }

    /**
     * Get all matches for this court.
     */
    public function matches(): HasMany
    {
        return $this->hasMany(BadmintonMatch::class);
    }

    /**
     * Get time slots for this court in a specific phase.
     */
    public function timeSlotsInPhase(int $phaseId)
    {
        return $this->timeSlots()->where('phase_id', $phaseId);
    }

    /**
     * Get matches for this court in a specific phase.
     */
    public function matchesInPhase(int $phaseId)
    {
        return $this->matches()->where('phase_id', $phaseId);
    }

    /**
     * Check if the court is available at a specific time.
     */
    public function isAvailableAt(Carbon $startTime, Carbon $endTime, ?int $excludeTimeSlotId = null): bool
    {
        $query = $this->timeSlots()
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(function ($q2) use ($startTime, $endTime) {
                    // Check for overlapping time slots
                    $q2->where('start_at', '<', $endTime)
                       ->where('end_at', '>', $startTime);
                });
            });

        if ($excludeTimeSlotId) {
            $query->where('id', '!=', $excludeTimeSlotId);
        }

        return $query->count() === 0;
    }

    /**
     * Get available time slots for this court in a date range.
     */
    public function getAvailableTimeSlots(Carbon $startDate, Carbon $endDate, ?int $phaseId = null)
    {
        $query = $this->timeSlots()
            ->whereBetween('start_at', [$startDate, $endDate])
            ->whereDoesntHave('matches');

        if ($phaseId) {
            $query->where('phase_id', $phaseId);
        }

        return $query->orderBy('start_at')->get();
    }

    /**
     * Get occupied time slots for this court in a date range.
     */
    public function getOccupiedTimeSlots(Carbon $startDate, Carbon $endDate, ?int $phaseId = null)
    {
        $query = $this->timeSlots()
            ->whereBetween('start_at', [$startDate, $endDate])
            ->whereHas('matches');

        if ($phaseId) {
            $query->where('phase_id', $phaseId);
        }

        return $query->orderBy('start_at')->get();
    }

    /**
     * Get matches scheduled for today on this court.
     */
    public function getTodayMatches()
    {
        $today = now()->startOfDay();
        $tomorrow = now()->addDay()->startOfDay();

        return $this->matches()
            ->whereHas('timeSlot', function ($query) use ($today, $tomorrow) {
                $query->whereBetween('start_at', [$today, $tomorrow]);
            })
            ->with(['timeSlot', 'pairA', 'pairB'])
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get the court's utilization percentage for a date range.
     */
    public function getUtilizationPercentage(Carbon $startDate, Carbon $endDate): float
    {
        $totalSlots = $this->timeSlots()
            ->whereBetween('start_at', [$startDate, $endDate])
            ->count();

        if ($totalSlots === 0) {
            return 0.0;
        }

        $occupiedSlots = $this->timeSlots()
            ->whereBetween('start_at', [$startDate, $endDate])
            ->whereHas('matches')
            ->count();

        return ($occupiedSlots / $totalSlots) * 100;
    }

    /**
     * Scope to get courts with available time slots in a date range.
     */
    public function scopeWithAvailableSlots($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereHas('timeSlots', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_at', [$startDate, $endDate])
              ->whereDoesntHave('matches');
        });
    }
}
