<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BadmintonMatchParticipant extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'match_participants';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'match_id',
        'user_id',
        'team_side',
        'result',
        'points',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'points' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the match that owns this participant.
     */
    public function match(): BelongsTo
    {
        return $this->belongsTo(BadmintonMatch::class);
    }

    /**
     * Get the user that owns this participant.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter participants by team side.
     */
    public function scopeByTeamSide($query, string $teamSide)
    {
        return $query->where('team_side', $teamSide);
    }

    /**
     * Scope to get team A participants.
     */
    public function scopeTeamA($query)
    {
        return $query->where('team_side', 'A');
    }

    /**
     * Scope to get team B participants.
     */
    public function scopeTeamB($query)
    {
        return $query->where('team_side', 'B');
    }

    /**
     * Scope to filter participants by result.
     */
    public function scopeByResult($query, string $result)
    {
        return $query->where('result', $result);
    }

    /**
     * Scope to get winning participants.
     */
    public function scopeWinners($query)
    {
        return $query->where('result', 'win');
    }

    /**
     * Scope to get losing participants.
     */
    public function scopeLosers($query)
    {
        return $query->where('result', 'lose');
    }

    /**
     * Scope to get participants with draw results.
     */
    public function scopeDraws($query)
    {
        return $query->where('result', 'draw');
    }

    /**
     * Scope to get participants for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get participants for a specific match.
     */
    public function scopeForMatch($query, int $matchId)
    {
        return $query->where('match_id', $matchId);
    }

    /**
     * Get the team side display name.
     */
    public function getTeamSideNameAttribute(): string
    {
        return match ($this->team_side) {
            'A' => 'Team A',
            'B' => 'Team B',
            default => 'Unknown'
        };
    }

    /**
     * Get the result display name.
     */
    public function getResultNameAttribute(): ?string
    {
        return match ($this->result) {
            'win' => 'Win',
            'lose' => 'Loss',
            'draw' => 'Draw',
            default => null
        };
    }

    /**
     * Check if this participant won.
     */
    public function isWinner(): bool
    {
        return $this->result === 'win';
    }

    /**
     * Check if this participant lost.
     */
    public function isLoser(): bool
    {
        return $this->result === 'lose';
    }

    /**
     * Check if this participant had a draw.
     */
    public function isDraw(): bool
    {
        return $this->result === 'draw';
    }

    /**
     * Check if this participant is on team A.
     */
    public function isTeamA(): bool
    {
        return $this->team_side === 'A';
    }

    /**
     * Check if this participant is on team B.
     */
    public function isTeamB(): bool
    {
        return $this->team_side === 'B';
    }

    /**
     * Get the opponent team participants.
     */
    public function getOpponents()
    {
        $opponentSide = $this->team_side === 'A' ? 'B' : 'A';
        
        return self::where('match_id', $this->match_id)
                   ->where('team_side', $opponentSide)
                   ->with('user')
                   ->get();
    }

    /**
     * Get the teammate participant (if any).
     */
    public function getTeammate(): ?self
    {
        return self::where('match_id', $this->match_id)
                   ->where('team_side', $this->team_side)
                   ->where('user_id', '!=', $this->user_id)
                   ->first();
    }

    /**
     * Update the participant's result and points.
     */
    public function updateResult(string $result): bool
    {
        $points = match ($result) {
            'win' => 1.0,
            'lose' => 0.0,
            'draw' => 0.5,
            default => 0.0
        };

        $this->result = $result;
        $this->points = $points;

        return $this->save();
    }

    /**
     * Get statistics for a specific user across all their participations.
     */
    public static function getUserStats(int $userId, ?int $phaseId = null): array
    {
        $query = self::forUser($userId);
        
        // Filter by phase if specified
        if ($phaseId) {
            $query->whereHas('match', function ($q) use ($phaseId) {
                $q->where('phase_id', $phaseId);
            });
        }
        
        $participations = $query->get();

        $stats = [
            'total_matches' => $participations->count(),
            'wins' => $participations->where('result', 'win')->count(),
            'losses' => $participations->where('result', 'lose')->count(),
            'draws' => $participations->where('result', 'draw')->count(),
            'total_points' => $participations->sum('points'),
            'win_percentage' => 0,
        ];

        $completedMatches = $stats['wins'] + $stats['losses'] + $stats['draws'];
        if ($completedMatches > 0) {
            $stats['win_percentage'] = round(($stats['wins'] / $completedMatches) * 100, 2);
        }

        return $stats;
    }

    /**
     * Get statistics for a specific match.
     */
    public static function getMatchStats(int $matchId): array
    {
        $participants = self::forMatch($matchId)->with('user')->get();

        return [
            'team_a_participants' => $participants->where('team_side', 'A'),
            'team_b_participants' => $participants->where('team_side', 'B'),
            'total_participants' => $participants->count(),
        ];
    }

    /**
     * Create match participants for a match.
     */
    public static function createForMatch(BadmintonMatch $match): void
    {
        // Get users from pair A
        $pairA = $match->pairA;
        self::create([
            'match_id' => $match->id,
            'user_id' => $pairA->user_lo_id,
            'team_side' => 'A',
            'result' => null,
            'points' => 0,
        ]);
        
        self::create([
            'match_id' => $match->id,
            'user_id' => $pairA->user_hi_id,
            'team_side' => 'A',
            'result' => null,
            'points' => 0,
        ]);

        // Get users from pair B
        $pairB = $match->pairB;
        self::create([
            'match_id' => $match->id,
            'user_id' => $pairB->user_lo_id,
            'team_side' => 'B',
            'result' => null,
            'points' => 0,
        ]);
        
        self::create([
            'match_id' => $match->id,
            'user_id' => $pairB->user_hi_id,
            'team_side' => 'B',
            'result' => null,
            'points' => 0,
        ]);
    }
}
