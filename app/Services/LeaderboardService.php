<?php

namespace App\Services;

use App\Models\User;
use App\Models\BadmintonMatchParticipant;
use Illuminate\Support\Collection;

class LeaderboardService
{
    /**
     * Get leaderboard with optional gender and phase filters.
     */
    public function getLeaderboard(?string $gender = null, ?int $phaseId = null): array
    {
        $query = User::query();

        // Filter by gender if specified
        if ($gender && in_array($gender, ['M', 'F'])) {
            $query->where('gender', $gender);
        }

        $users = $query->get();
        
        // Get stats for all users with optional phase filter
        $leaderboard = $users->map(function ($user) use ($phaseId) {
            return $user->getLeaderboardStats($phaseId);
        });

        // Sort by total points (desc), then by match difference (desc)
        $leaderboard = $leaderboard->sortBy([
            ['total_points', 'desc'],
            ['match_difference', 'desc'],
            ['name', 'asc']
        ])->values();

        // Add rank
        $leaderboard = $leaderboard->map(function ($stats, $index) {
            $stats['rank'] = $index + 1;
            return $stats;
        });

        // Get phase info if filtering by phase
        $phaseInfo = null;
        if ($phaseId) {
            $phase = \App\Models\Phase::find($phaseId);
            $phaseInfo = $phase ? [
                'id' => $phase->id,
                'name' => $phase->name,
                'display_name' => $phase->getPhaseDisplayName(),
                'type' => $phase->type
            ] : null;
        }

        return [
            'leaderboard' => $leaderboard,
            'total_players' => $leaderboard->count(),
            'filter' => [
                'gender' => $gender,
                'gender_name' => $gender ? ($gender === 'M' ? 'Nam' : 'Nữ') : 'Tất cả',
                'phase_id' => $phaseId,
                'phase' => $phaseInfo
            ],
            'stats' => [
                'total_matches' => $leaderboard->sum('total_matches'),
                'total_points' => $leaderboard->sum('total_points'),
                'average_points_per_player' => $leaderboard->count() > 0 
                    ? round($leaderboard->sum('total_points') / $leaderboard->count(), 2) 
                    : 0
            ]
        ];
    }

    /**
     * Get detailed player statistics.
     */
    public function getPlayerStats(int $userId): array
    {
        $user = User::findOrFail($userId);
        $stats = $user->getLeaderboardStats();
        
        // Get match history
        $matchHistory = $user->getMatchHistory()->take(10);
        
        // Get performance by match type
        $performanceByType = $this->getPerformanceByType($userId);
        
        // Get recent performance (last 5 matches)
        $recentPerformance = $this->getRecentPerformance($userId);

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'gender' => $user->gender,
                'gender_name' => $user->getGenderNameAttribute(),
            ],
            'overall_stats' => $stats,
            'performance_by_type' => $performanceByType,
            'recent_performance' => $recentPerformance,
            'recent_matches' => $matchHistory->map(function ($participation) {
                return [
                    'match_id' => $participation->match->id,
                    'phase_name' => $participation->match->phase->getPhaseDisplayName(),
                    'type_name' => $participation->match->getTypeNameAttribute(),
                    'opponent_pair' => $participation->getOpponents()->map(function ($opponent) {
                        return $opponent->user->name;
                    })->join(' / '),
                    'result' => $participation->result,
                    'result_name' => $participation->getResultNameAttribute(),
                    'points' => $participation->points,
                    'match_date' => $participation->match->timeSlot?->start_at,
                ];
            })
        ];
    }

    /**
     * Get performance statistics by match type.
     */
    protected function getPerformanceByType(int $userId): array
    {
        $participations = BadmintonMatchParticipant::with('match')
            ->where('user_id', $userId)
            ->get();

        $typeStats = [];
        
        foreach (['MD', 'WD', 'XD'] as $type) {
            $typeParticipations = $participations->filter(function ($participation) use ($type) {
                return $participation->match->type === $type;
            });

            $wins = $typeParticipations->where('result', 'win')->count();
            $losses = $typeParticipations->where('result', 'lose')->count();
            $draws = $typeParticipations->where('result', 'draw')->count();
            $total = $typeParticipations->count();

            $typeStats[$type] = [
                'type' => $type,
                'type_name' => match($type) {
                    'MD' => "Đôi Nam",
                    'WD' => "Đôi Nữ", 
                    'XD' => "Đôi Nam Nữ",
                    default => "Không xác định"
                },
                'total_matches' => $total,
                'wins' => $wins,
                'losses' => $losses,
                'draws' => $draws,
                'total_points' => $typeParticipations->sum('points'),
                'win_percentage' => $total > 0 ? round(($wins / $total) * 100, 2) : 0
            ];
        }

        return $typeStats;
    }

    /**
     * Get recent performance trend.
     */
    protected function getRecentPerformance(int $userId): array
    {
        $recentParticipations = BadmintonMatchParticipant::with('match')
            ->where('user_id', $userId)
            ->whereNotNull('result')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $performance = [
            'recent_matches' => $recentParticipations->count(),
            'recent_wins' => $recentParticipations->where('result', 'win')->count(),
            'recent_losses' => $recentParticipations->where('result', 'lose')->count(),
            'recent_draws' => $recentParticipations->where('result', 'draw')->count(),
            'recent_points' => $recentParticipations->sum('points'),
            'recent_win_percentage' => 0,
            'trend' => 'stable'
        ];

        if ($performance['recent_matches'] > 0) {
            $performance['recent_win_percentage'] = round(
                ($performance['recent_wins'] / $performance['recent_matches']) * 100, 
                2
            );
        }

        // Determine trend based on recent performance
        if ($performance['recent_win_percentage'] > 60) {
            $performance['trend'] = 'improving';
        } elseif ($performance['recent_win_percentage'] < 40) {
            $performance['trend'] = 'declining';
        }

        return $performance;
    }

    /**
     * Get top performers.
     */
    public function getTopPerformers(int $limit = 10): array
    {
        $leaderboard = $this->getLeaderboard();
        
        return [
            'top_by_points' => $leaderboard['leaderboard']->take($limit),
            'top_by_win_percentage' => $leaderboard['leaderboard']
                ->filter(fn($player) => $player['total_matches'] >= 3) // At least 3 matches
                ->sortByDesc('win_percentage')
                ->take($limit)
                ->values(),
            'most_active' => $leaderboard['leaderboard']
                ->sortByDesc('total_matches')
                ->take($limit)
                ->values()
        ];
    }

    /**
     * Get tournament summary statistics.
     */
    public function getTournamentSummary(): array
    {
        $totalPlayers = User::count();
        $maleLeaderboard = $this->getLeaderboard('M');
        $femaleLeaderboard = $this->getLeaderboard('F');
        $overallLeaderboard = $this->getLeaderboard();

        return [
            'total_players' => $totalPlayers,
            'male_players' => User::where('gender', 'M')->count(),
            'female_players' => User::where('gender', 'F')->count(),
            'total_matches_played' => BadmintonMatchParticipant::whereNotNull('result')->count() / 4, // 4 participants per match
            'total_points_awarded' => BadmintonMatchParticipant::sum('points'),
            'top_male_player' => $maleLeaderboard['leaderboard']->first(),
            'top_female_player' => $femaleLeaderboard['leaderboard']->first(),
            'overall_champion' => $overallLeaderboard['leaderboard']->first(),
            'average_matches_per_player' => $totalPlayers > 0 
                ? round($overallLeaderboard['stats']['total_matches'] / $totalPlayers, 2)
                : 0
        ];
    }
}
