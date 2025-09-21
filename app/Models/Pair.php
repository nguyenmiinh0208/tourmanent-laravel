<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Pair extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'phase_id',
        'user_lo_id',
        'user_hi_id',
        'type',
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
     * Get the phase that owns this pair.
     */
    public function phase(): BelongsTo
    {
        return $this->belongsTo(Phase::class);
    }

    /**
     * Get the "lo" user (lower ID) of this pair.
     */
    public function userLo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_lo_id');
    }

    /**
     * Get the "hi" user (higher ID) of this pair.
     */
    public function userHi(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_hi_id');
    }

    /**
     * Get matches where this pair is team A.
     */
    public function matchesAsTeamA(): HasMany
    {
        return $this->hasMany(BadmintonMatch::class, 'pair_a_id');
    }

    /**
     * Get matches where this pair is team B.
     */
    public function matchesAsTeamB(): HasMany
    {
        return $this->hasMany(BadmintonMatch::class, 'pair_b_id');
    }

    /**
     * Get all matches for this pair (both as team A and team B).
     */
    public function getAllMatches()
    {
        return BadmintonMatch::where('pair_a_id', $this->id)
                   ->orWhere('pair_b_id', $this->id);
    }

    /**
     * Scope to filter pairs by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get men's doubles pairs.
     */
    public function scopeMensDoubles($query)
    {
        return $query->where('type', 'MD');
    }

    /**
     * Scope to get women's doubles pairs.
     */
    public function scopeWomensDoubles($query)
    {
        return $query->where('type', 'WD');
    }

    /**
     * Scope to get mixed doubles pairs.
     */
    public function scopeMixedDoubles($query)
    {
        return $query->where('type', 'XD');
    }

    /**
     * Scope to get pairs for a specific phase.
     */
    public function scopeForPhase($query, int $phaseId)
    {
        return $query->where('phase_id', $phaseId);
    }

    /**
     * Scope to get pairs containing a specific user.
     */
    public function scopeContainingUser($query, int $userId)
    {
        return $query->where('user_lo_id', $userId)
                    ->orWhere('user_hi_id', $userId);
    }

    /**
     * Get both users as a collection.
     */
    public function getUsers()
    {
        return collect([$this->userLo, $this->userHi]);
    }

    /**
     * Get the pair name (combination of both user names).
     */
    public function getPairNameAttribute(): string
    {
        return $this->userLo->name . ' / ' . $this->userHi->name;
    }

    /**
     * Get the pair type display name.
     */
    public function getTypeNameAttribute(): string
    {
        return match ($this->type) {
            'MD' => "Men's Doubles",
            'WD' => "Women's Doubles",
            'XD' => 'Mixed Doubles',
            default => 'Unknown'
        };
    }

    /**
     * Check if this pair contains a specific user.
     */
    public function containsUser(int $userId): bool
    {
        return $this->user_lo_id === $userId || $this->user_hi_id === $userId;
    }

    /**
     * Get the partner of a specific user in this pair.
     */
    public function getPartnerOf(int $userId): ?User
    {
        if ($this->user_lo_id === $userId) {
            return $this->userHi;
        }
        
        if ($this->user_hi_id === $userId) {
            return $this->userLo;
        }
        
        return null;
    }

    /**
     * Check if the pair composition is valid for the pair type.
     */
    public function isValidComposition(): bool
    {
        $userLo = $this->userLo;
        $userHi = $this->userHi;

        if (!$userLo || !$userHi) {
            return false;
        }

        return match ($this->type) {
            'MD' => $userLo->isMale() && $userHi->isMale(),
            'WD' => $userLo->isFemale() && $userHi->isFemale(),
            'XD' => ($userLo->isMale() && $userHi->isFemale()) || 
                    ($userLo->isFemale() && $userHi->isMale()),
            default => false
        };
    }

    /**
     * Get match statistics for this pair.
     */
    public function getMatchStats(): array
    {
        $matches = $this->getAllMatches()->get();
        
        $stats = [
            'total_matches' => $matches->count(),
            'wins' => 0,
            'losses' => 0,
            'draws' => 0,
            'win_percentage' => 0,
        ];

        foreach ($matches as $match) {
            if ($match->winner === null) {
                continue;
            }

            $isTeamA = $match->pair_a_id === $this->id;
            
            if ($match->winner === 'draw') {
                $stats['draws']++;
            } elseif (($isTeamA && $match->winner === 'A') || (!$isTeamA && $match->winner === 'B')) {
                $stats['wins']++;
            } else {
                $stats['losses']++;
            }
        }

        $completedMatches = $stats['wins'] + $stats['losses'] + $stats['draws'];
        if ($completedMatches > 0) {
            $stats['win_percentage'] = round(($stats['wins'] / $completedMatches) * 100, 2);
        }

        return $stats;
    }

    /**
     * Create a pair with proper user ordering (lo/hi by ID).
     */
    public static function createPair(int $phaseId, int $userId1, int $userId2, string $type): self
    {
        [$userLoId, $userHiId] = $userId1 < $userId2 ? [$userId1, $userId2] : [$userId2, $userId1];

        return self::create([
            'phase_id' => $phaseId,
            'user_lo_id' => $userLoId,
            'user_hi_id' => $userHiId,
            'type' => $type,
        ]);
    }

    /**
     * Boot method to add model events.
     */
    protected static function boot()
    {
        parent::boot();

        // Ensure user_lo_id is always less than user_hi_id
        static::creating(function ($pair) {
            if ($pair->user_lo_id > $pair->user_hi_id) {
                [$pair->user_lo_id, $pair->user_hi_id] = [$pair->user_hi_id, $pair->user_lo_id];
            }
        });

        static::updating(function ($pair) {
            if ($pair->user_lo_id > $pair->user_hi_id) {
                [$pair->user_lo_id, $pair->user_hi_id] = [$pair->user_hi_id, $pair->user_lo_id];
            }
        });
    }
}
