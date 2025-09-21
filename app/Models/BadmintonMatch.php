<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class BadmintonMatch extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'matches';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'phase_id',
        'time_slot_id',
        'court_id',
        'type',
        'pair_a_id',
        'pair_b_id',
        'status',
        'score_team_a',
        'score_team_b',
        'winner',
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
     * Get the phase that owns this match.
     */
    public function phase(): BelongsTo
    {
        return $this->belongsTo(Phase::class);
    }

    /**
     * Get the time slot for this match.
     */
    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class);
    }

    /**
     * Get the court for this match.
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /**
     * Get pair A for this match.
     */
    public function pairA(): BelongsTo
    {
        return $this->belongsTo(Pair::class, 'pair_a_id');
    }

    /**
     * Get pair B for this match.
     */
    public function pairB(): BelongsTo
    {
        return $this->belongsTo(Pair::class, 'pair_b_id');
    }

    /**
     * Get all participants for this match.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(BadmintonMatchParticipant::class);
    }

    /**
     * Get participants for team A.
     */
    public function teamAParticipants(): HasMany
    {
        return $this->hasMany(BadmintonMatchParticipant::class)->where('team_side', 'A');
    }

    /**
     * Get participants for team B.
     */
    public function teamBParticipants(): HasMany
    {
        return $this->hasMany(BadmintonMatchParticipant::class)->where('team_side', 'B');
    }

    /**
     * Scope to filter matches by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get scheduled matches.
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope to get playing matches.
     */
    public function scopePlaying($query)
    {
        return $query->where('status', 'playing');
    }

    /**
     * Scope to get finished matches.
     */
    public function scopeFinished($query)
    {
        return $query->where('status', 'finished');
    }

    /**
     * Scope to get canceled matches.
     */
    public function scopeCanceled($query)
    {
        return $query->where('status', 'canceled');
    }

    /**
     * Scope to filter matches by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get men's doubles matches.
     */
    public function scopeMensDoubles($query)
    {
        return $query->where('type', 'MD');
    }

    /**
     * Scope to get women's doubles matches.
     */
    public function scopeWomensDoubles($query)
    {
        return $query->where('type', 'WD');
    }

    /**
     * Scope to get mixed doubles matches.
     */
    public function scopeMixedDoubles($query)
    {
        return $query->where('type', 'XD');
    }

    /**
     * Scope to get matches for a specific phase.
     */
    public function scopeForPhase($query, int $phaseId)
    {
        return $query->where('phase_id', $phaseId);
    }

    /**
     * Scope to get matches for a specific court.
     */
    public function scopeForCourt($query, int $courtId)
    {
        return $query->where('court_id', $courtId);
    }

    /**
     * Scope to get matches for today.
     */
    public function scopeToday($query)
    {
        return $query->whereHas('timeSlot', function ($q) {
            $today = now()->startOfDay();
            $tomorrow = now()->addDay()->startOfDay();
            $q->whereBetween('start_at', [$today, $tomorrow]);
        });
    }

    /**
     * Scope to get upcoming matches.
     */
    public function scopeUpcoming($query)
    {
        return $query->whereHas('timeSlot', function ($q) {
            $q->where('start_at', '>', now());
        });
    }

    /**
     * Scope to get matches involving a specific pair.
     */
    public function scopeInvolvingPair($query, int $pairId)
    {
        return $query->where('pair_a_id', $pairId)
                    ->orWhere('pair_b_id', $pairId);
    }

    /**
     * Get the match status display name.
     */
    public function getStatusNameAttribute(): string
    {
        return match ($this->status) {
            'scheduled' => 'Scheduled',
            'playing' => 'Playing',
            'finished' => 'Finished',
            'canceled' => 'Canceled',
            default => 'Unknown'
        };
    }

    /**
     * Get the match type display name.
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
     * Get the winner display name.
     */
    public function getWinnerNameAttribute(): ?string
    {
        return match ($this->winner) {
            'A' => $this->pairA?->pair_name ?? 'Team A',
            'B' => $this->pairB?->pair_name ?? 'Team B',
            'draw' => 'Draw',
            default => null
        };
    }

    /**
     * Get the match score display.
     */
    public function getScoreDisplayAttribute(): string
    {
        if ($this->score_team_a === null || $this->score_team_b === null) {
            return 'No score';
        }

        return $this->score_team_a . ' - ' . $this->score_team_b;
    }

    /**
     * Check if the match is scheduled.
     */
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    /**
     * Check if the match is currently playing.
     */
    public function isPlaying(): bool
    {
        return $this->status === 'playing';
    }

    /**
     * Check if the match is finished.
     */
    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }

    /**
     * Check if the match is canceled.
     */
    public function isCanceled(): bool
    {
        return $this->status === 'canceled';
    }

    /**
     * Check if the match has a score.
     */
    public function hasScore(): bool
    {
        return $this->score_team_a !== null && $this->score_team_b !== null;
    }

    /**
     * Check if the match has a winner.
     */
    public function hasWinner(): bool
    {
        return $this->winner !== null;
    }

    /**
     * Start the match (change status to playing).
     */
    public function start(): bool
    {
        if (!$this->isScheduled()) {
            return false;
        }

        $this->status = 'playing';
        return $this->save();
    }

    /**
     * Finish the match with scores.
     */
    public function finish(int $scoreTeamA, int $scoreTeamB): bool
    {
        if (!$this->isPlaying() && !$this->isScheduled()) {
            return false;
        }

        $this->score_team_a = $scoreTeamA;
        $this->score_team_b = $scoreTeamB;
        $this->status = 'finished';

        // Determine winner
        if ($scoreTeamA > $scoreTeamB) {
            $this->winner = 'A';
        } elseif ($scoreTeamB > $scoreTeamA) {
            $this->winner = 'B';
        } else {
            $this->winner = 'draw';
        }

        $saved = $this->save();

        if ($saved) {
            $this->updateParticipantResults();
        }

        return $saved;
    }

    /**
     * Cancel the match.
     */
    public function cancel(): bool
    {
        if ($this->isFinished()) {
            return false;
        }

        $this->status = 'canceled';
        return $this->save();
    }

    /**
     * Update participant results based on match outcome.
     */
    protected function updateParticipantResults(): void
    {
        if (!$this->hasWinner()) {
            return;
        }

        $teamAResult = match ($this->winner) {
            'A' => 'win',
            'B' => 'lose',
            'draw' => 'draw',
            default => null
        };

        $teamBResult = match ($this->winner) {
            'A' => 'lose',
            'B' => 'win',
            'draw' => 'draw',
            default => null
        };

        $teamAPoints = match ($teamAResult) {
            'win' => 1.0,
            'lose' => 0.0,
            'draw' => 0.5,
            default => 0.0
        };

        $teamBPoints = match ($teamBResult) {
            'win' => 1.0,
            'lose' => 0.0,
            'draw' => 0.5,
            default => 0.0
        };

        // Update team A participants
        $this->teamAParticipants()->update([
            'result' => $teamAResult,
            'points' => $teamAPoints,
        ]);

        // Update team B participants
        $this->teamBParticipants()->update([
            'result' => $teamBResult,
            'points' => $teamBPoints,
        ]);
    }

    /**
     * Get the match time (from time slot).
     */
    public function getMatchTimeAttribute(): ?Carbon
    {
        return $this->timeSlot?->start_at;
    }

    /**
     * Get the match duration (from time slot).
     */
    public function getMatchDurationAttribute(): ?int
    {
        return $this->timeSlot?->getDurationInMinutes();
    }
}
