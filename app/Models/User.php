<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'gender',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get all match participants for this user.
     */
    public function matchParticipants(): HasMany
    {
        return $this->hasMany(BadmintonMatchParticipant::class);
    }

    /**
     * Get all matches this user participated in.
     */
    public function matches(): BelongsToMany
    {
        return $this->belongsToMany(BadmintonMatch::class, 'match_participants')
            ->withPivot(['team_side', 'result', 'points'])
            ->withTimestamps();
    }

    /**
     * Get all pairs where this user is the "lo" user.
     */
    public function pairsAsLo(): HasMany
    {
        return $this->hasMany(Pair::class, 'user_lo_id');
    }

    /**
     * Get all pairs where this user is the "hi" user.
     */
    public function pairsAsHi(): HasMany
    {
        return $this->hasMany(Pair::class, 'user_hi_id');
    }

    /**
     * Get all pairs this user belongs to.
     */
    public function pairs()
    {
        return Pair::where('user_lo_id', $this->id)
            ->orWhere('user_hi_id', $this->id);
    }

    /**
     * Get available time slots for this user (no conflicts).
     */
    public function getAvailableTimeSlots(int $phaseId)
    {
        $occupiedTimeSlots = $this->matches()
            ->where('phase_id', $phaseId)
            ->with('timeSlot')
            ->get()
            ->pluck('timeSlot.id')
            ->filter();

        return TimeSlot::where('phase_id', $phaseId)
            ->whereNotIn('id', $occupiedTimeSlots)
            ->orderBy('start_at')
            ->get();
    }

    /**
     * Get total statistics for this user across all phases.
     */
    public function getTotalStats(?int $phaseId = null): array
    {
        return BadmintonMatchParticipant::getUserStats($this->id, $phaseId);
    }

    /**
     * Get match history for this user.
     */
    public function getMatchHistory()
    {
        return $this->matchParticipants()
            ->with(['match.timeSlot', 'match.court', 'match.pairA', 'match.pairB'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Check if user has any conflicts in the given phase.
     */
    public function hasScheduleConflicts(int $phaseId): bool
    {
        $userMatches = $this->matches()
            ->where('phase_id', $phaseId)
            ->with('timeSlot')
            ->get();

        $timeSlots = $userMatches->pluck('timeSlot')->filter();
        
        for ($i = 0; $i < $timeSlots->count() - 1; $i++) {
            for ($j = $i + 1; $j < $timeSlots->count(); $j++) {
                if ($timeSlots[$i]->conflictsWith($timeSlots[$j])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get leaderboard stats for this user.
     */
    public function getLeaderboardStats(?int $phaseId = null): array
    {
        $stats = $this->getTotalStats($phaseId);
        $stats['name'] = $this->name;
        $stats['gender'] = $this->gender;
        $stats['gender_name'] = $this->getGenderNameAttribute();
        $stats['match_difference'] = $stats['wins'] - $stats['losses'];
        
        return $stats;
    }

    /**
     * Scope to filter users by gender.
     */
    public function scopeByGender($query, string $gender)
    {
        return $query->where('gender', $gender);
    }

    /**
     * Scope to get male users.
     */
    public function scopeMale($query)
    {
        return $query->where('gender', 'M');
    }

    /**
     * Scope to get female users.
     */
    public function scopeFemale($query)
    {
        return $query->where('gender', 'F');
    }

    /**
     * Get the user's gender display name.
     */
    public function getGenderNameAttribute(): string
    {
        return match ($this->gender) {
            'M' => 'Male',
            'F' => 'Female',
            default => 'Unknown'
        };
    }

    /**
     * Check if user is male.
     */
    public function isMale(): bool
    {
        return $this->gender === 'M';
    }

    /**
     * Check if user is female.
     */
    public function isFemale(): bool
    {
        return $this->gender === 'F';
    }
}