<?php

namespace App\Services;

use App\Models\User;
use App\Models\Phase;
use App\Models\Pair;
use App\Models\BadmintonMatch;
use App\Models\BadmintonMatchParticipant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Exception;

class TournamentService
{
    protected PairingAlgorithmService $pairingService;
    protected SchedulingService $schedulingService;

    public function __construct(
        PairingAlgorithmService $pairingService,
        SchedulingService $schedulingService
    ) {
        $this->pairingService = $pairingService;
        $this->schedulingService = $schedulingService;
    }

    /**
     * Import players into a phase.
     */
    public function importPlayers(array $playersData, int $phaseId): array
    {
        $phase = Phase::findOrFail($phaseId);
        
        if (!in_array($phase->status, ['draft'])) {
            throw new Exception('Chỉ có thể import người chơi khi vòng đấu ở trạng thái draft');
        }

        $imported = [];
        $errors = [];

        DB::transaction(function () use ($playersData, $phase, &$imported, &$errors) {
            foreach ($playersData as $index => $playerData) {
                try {
                    // Validate required fields
                    if (empty($playerData['name']) || empty($playerData['gender'])) {
                        $errors[] = "Dòng {$index}: Thiếu thông tin họ tên hoặc giới tính";
                        continue;
                    }

                    // Validate gender
                    if (!in_array($playerData['gender'], ['M', 'F'])) {
                        $errors[] = "Dòng {$index}: Giới tính phải là M hoặc F";
                        continue;
                    }

                    // Create or find user
                    $user = User::firstOrCreate(
                        ['name' => trim($playerData['name'])],
                        ['gender' => $playerData['gender']]
                    );

                    // Attach user to phase if not already attached
                    if (!$phase->users()->where('user_id', $user->id)->exists()) {
                        $phase->users()->attach($user->id);
                    }

                    $imported[] = $user;

                } catch (Exception $e) {
                    $errors[] = "Dòng {$index}: {$e->getMessage()}";
                }
            }
        });

        return [
            'imported' => $imported,
            'errors' => $errors,
            'total_imported' => count($imported),
            'total_errors' => count($errors)
        ];
    }

    /**
     * Generate random pairings for a phase.
     */
    public function generatePairings(int $phaseId, int $matchesPerPlayer): array
    {
        $phase = Phase::findOrFail($phaseId);
        
        if (!$phase->canGeneratePairs()) {
            throw new Exception('Không thể tạo cặp thi đấu cho vòng này');
        }

        // Clear existing algorithm-generated pairs
        $phase->pairs()->where('created_by_algorithm', true)->delete();

        $players = $phase->getPlayers();
        if ($players->count() < 4) {
            throw new Exception('Cần ít nhất 4 người chơi để tạo cặp thi đấu');
        }

        $result = DB::transaction(function () use ($phase, $players, $matchesPerPlayer) {
            // Update phase with matches per player
            $phase->update(['matches_per_player' => $matchesPerPlayer]);

            // Generate pairs using algorithm service
            return $this->pairingService->generateOptimalPairs($phase, $players, $matchesPerPlayer);
        });

        return $result;
    }

    /**
     * Schedule matches for a phase.
     */
    public function scheduleMatches(int $phaseId): array
    {
        $phase = Phase::findOrFail($phaseId);
        
        if (!$phase->canScheduleMatches()) {
            throw new Exception('Không thể lên lịch thi đấu cho vòng này');
        }

        $pairs = $phase->pairs;
        if ($pairs->count() < 2) {
            throw new Exception('Cần ít nhất 2 cặp để tạo lịch thi đấu');
        }

        return DB::transaction(function () use ($phase, $pairs) {
            // Create matches from pairs
            $matches = $this->createMatchesFromPairs($phase, $pairs);
            
            // Schedule matches to courts and time slots
            return $this->schedulingService->distributeToCourts($phase, $matches);
        });
    }

    /**
     * Create matches from pairs.
     */
    protected function createMatchesFromPairs(Phase $phase, Collection $pairs): Collection
    {
        $matches = collect();
        $pairsList = $pairs->toArray();
        
        // Create matches by pairing every pair with every other pair
        for ($i = 0; $i < count($pairsList); $i++) {
            for ($j = $i + 1; $j < count($pairsList); $j++) {
                $pairA = $pairsList[$i];
                $pairB = $pairsList[$j];
                
                // Ensure same match type
                if ($pairA['type'] === $pairB['type']) {
                    $match = BadmintonMatch::create([
                        'phase_id' => $phase->id,
                        'type' => $pairA['type'],
                        'pair_a_id' => $pairA['id'],
                        'pair_b_id' => $pairB['id'],
                        'status' => 'scheduled'
                    ]);
                    
                    // Create match participants
                    BadmintonMatchParticipant::createForMatch($match);
                    
                    $matches->push($match);
                }
            }
        }
        
        return $matches;
    }

    /**
     * Update match result from external system.
     */
    public function updateMatchResult(int $matchId, array $resultData): bool
    {
        $match = BadmintonMatch::findOrFail($matchId);
        
        if (!$match->isScheduled() && !$match->isPlaying()) {
            throw new Exception('Chỉ có thể cập nhật kết quả cho trận đấu đang diễn ra hoặc đã lên lịch');
        }

        return DB::transaction(function () use ($match, $resultData) {
            // Validate participants
            $this->validateMatchParticipants($match, $resultData['participants'] ?? []);
            
            // Update match with scores
            $scoreA = $resultData['score_team_a'] ?? 0;
            $scoreB = $resultData['score_team_b'] ?? 0;
            
            return $match->finish($scoreA, $scoreB);
        });
    }

    /**
     * Validate match participants.
     */
    protected function validateMatchParticipants(BadmintonMatch $match, array $participants): void
    {
        $matchParticipants = $match->participants->pluck('user_id')->toArray();
        $providedParticipants = collect($participants)->pluck('user_id')->toArray();
        
        if (array_diff($matchParticipants, $providedParticipants)) {
            throw new Exception('Danh sách người tham gia không khớp với trận đấu');
        }
    }

    /**
     * Get phase statistics.
     */
    public function getPhaseStats(int $phaseId): array
    {
        $phase = Phase::with(['pairs', 'matches', 'timeSlots'])->findOrFail($phaseId);
        
        $matches = $phase->matches;
        $players = $phase->getPlayers();
        
        return [
            'phase' => $phase,
            'total_players' => $players->count(),
            'players_by_gender' => $phase->getPlayersCountByGender(),
            'total_pairs' => $phase->pairs->count(),
            'pairs_by_type' => [
                'XD' => $phase->pairs->where('type', 'XD')->count(),
                'MD' => $phase->pairs->where('type', 'MD')->count(),
                'WD' => $phase->pairs->where('type', 'WD')->count(),
            ],
            'total_matches' => $matches->count(),
            'matches_by_status' => [
                'scheduled' => $matches->where('status', 'scheduled')->count(),
                'playing' => $matches->where('status', 'playing')->count(),
                'finished' => $matches->where('status', 'finished')->count(),
                'canceled' => $matches->where('status', 'canceled')->count(),
            ],
            'completion_percentage' => $matches->count() > 0 
                ? round(($matches->where('status', 'finished')->count() / $matches->count()) * 100, 2)
                : 0
        ];
    }
}
