<?php

namespace App\Services;

use App\Models\User;
use App\Models\Phase;
use App\Models\Pair;
use Illuminate\Support\Collection;
use Exception;

class PairingAlgorithmService
{
    /**
     * Generate optimal pairs for a phase.
     */
    public function generateOptimalPairs(Phase $phase, Collection $players, int $matchesPerPlayer): array
    {
        $males = $players->where('gender', 'M');
        $females = $players->where('gender', 'F');
        
        $result = [
            'pairs' => collect(),
            'stats' => [
                'total_players' => $players->count(),
                'male_players' => $males->count(),
                'female_players' => $females->count(),
                'matches_per_player' => $matchesPerPlayer,
            ]
        ];

        // Step 1: Create Mixed Doubles pairs (XD) - Priority
        $xdPairs = $this->createMixedDoublesPairs($phase, $males, $females);
        $result['pairs'] = $result['pairs']->merge($xdPairs);
        
        // Update remaining players after XD pairing
        $usedMales = $xdPairs->pluck('user_lo_id')->merge($xdPairs->pluck('user_hi_id'))
            ->intersect($males->pluck('id'));
        $usedFemales = $xdPairs->pluck('user_lo_id')->merge($xdPairs->pluck('user_hi_id'))
            ->intersect($females->pluck('id'));
            
        $remainingMales = $males->whereNotIn('id', $usedMales);
        $remainingFemales = $females->whereNotIn('id', $usedFemales);

        // Step 2: Create Men's Doubles pairs (MD) from remaining males
        if ($remainingMales->count() >= 2) {
            $mdPairs = $this->createMensDoublesPairs($phase, $remainingMales);
            $result['pairs'] = $result['pairs']->merge($mdPairs);
        }

        // Step 3: Create Women's Doubles pairs (WD) from remaining females
        if ($remainingFemales->count() >= 2) {
            $wdPairs = $this->createWomensDoublesPairs($phase, $remainingFemales);
            $result['pairs'] = $result['pairs']->merge($wdPairs);
        }

        // Step 4: Handle leftover players by creating same-gender pairs
        $this->handleLeftoverPlayers($phase, $remainingMales, $remainingFemales, $result);

        // Step 5: Validate feasibility and warn if not perfect
        $this->validateResultingPairs($players, $matchesPerPlayer, $result);

        $result['stats']['total_pairs'] = $result['pairs']->count();
        $result['stats']['pairs_by_type'] = [
            'XD' => $result['pairs']->where('type', 'XD')->count(),
            'MD' => $result['pairs']->where('type', 'MD')->count(),
            'WD' => $result['pairs']->where('type', 'WD')->count(),
        ];

        return $result;
    }

    /**
     * Create Mixed Doubles pairs.
     */
    protected function createMixedDoublesPairs(Phase $phase, Collection $males, Collection $females): Collection
    {
        $pairs = collect();
        $malesList = $males->shuffle()->values();
        $femalesList = $females->shuffle()->values();
        
        $maxXDPairs = min($malesList->count(), $femalesList->count());
        
        for ($i = 0; $i < $maxXDPairs; $i++) {
            $male = $malesList[$i];
            $female = $femalesList[$i];
            
            // Ensure user_lo_id < user_hi_id
            $userLoId = min($male->id, $female->id);
            $userHiId = max($male->id, $female->id);
            
            $pair = Pair::create([
                'phase_id' => $phase->id,
                'user_lo_id' => $userLoId,
                'user_hi_id' => $userHiId,
                'type' => 'XD',
                'created_by_algorithm' => true
            ]);
            
            $pairs->push($pair);
        }
        
        return $pairs;
    }

    /**
     * Create Men's Doubles pairs.
     */
    protected function createMensDoublesPairs(Phase $phase, Collection $males): Collection
    {
        return $this->createSameGenderPairs($phase, $males, 'MD');
    }

    /**
     * Create Women's Doubles pairs.
     */
    protected function createWomensDoublesPairs(Phase $phase, Collection $females): Collection
    {
        return $this->createSameGenderPairs($phase, $females, 'WD');
    }

    /**
     * Create same gender pairs.
     */
    protected function createSameGenderPairs(Phase $phase, Collection $players, string $type): Collection
    {
        $pairs = collect();
        $playersList = $players->shuffle()->values();
        
        // Create pairs from consecutive players
        for ($i = 0; $i < $playersList->count() - 1; $i += 2) {
            $player1 = $playersList[$i];
            $player2 = $playersList[$i + 1];
            
            // Ensure user_lo_id < user_hi_id
            $userLoId = min($player1->id, $player2->id);
            $userHiId = max($player1->id, $player2->id);
            
            $pair = Pair::create([
                'phase_id' => $phase->id,
                'user_lo_id' => $userLoId,
                'user_hi_id' => $userHiId,
                'type' => $type,
                'created_by_algorithm' => true
            ]);
            
            $pairs->push($pair);
        }
        
        return $pairs;
    }

    /**
     * Handle leftover players by creating additional same-gender pairs.
     */
    protected function handleLeftoverPlayers(Phase $phase, Collection $remainingMales, Collection $remainingFemales, array &$result): void
    {
        // Get players already used in XD pairs
        $usedInXD = $result['pairs']->where('type', 'XD')->flatMap(function ($pair) {
            return [$pair->user_lo_id, $pair->user_hi_id];
        });

        // Filter out players already used
        $availableMales = $remainingMales->whereNotIn('id', $usedInXD);
        $availableFemales = $remainingFemales->whereNotIn('id', $usedInXD);

        // Create MD pairs from remaining males
        if ($availableMales->count() >= 2) {
            $mdPairs = $this->createSameGenderPairs($phase, $availableMales, 'MD');
            $result['pairs'] = $result['pairs']->merge($mdPairs);
        }

        // Create WD pairs from remaining females  
        if ($availableFemales->count() >= 2) {
            $wdPairs = $this->createSameGenderPairs($phase, $availableFemales, 'WD');
            $result['pairs'] = $result['pairs']->merge($wdPairs);
        }
    }

    /**
     * Ensure each player has exactly N matches by creating additional pairs if needed.
     */
    protected function ensureEqualMatches(Phase $phase, Collection $players, int $targetMatches, array &$result): void
    {
        $pairs = $result['pairs'];
        
        // Count current matches per player
        $playerMatchCount = [];
        foreach ($players as $player) {
            $playerMatchCount[$player->id] = 0;
        }

        // Count matches from existing pairs
        foreach ($pairs as $pair) {
            $playerMatchCount[$pair->user_lo_id]++;
            $playerMatchCount[$pair->user_hi_id]++;
        }

        // Find players who need more matches
        $playersNeedingMatches = collect($playerMatchCount)
            ->filter(fn($count) => $count < $targetMatches)
            ->keys();

        // Create additional pairs for players needing more matches
        while ($playersNeedingMatches->count() >= 2) {
            $player1Id = $playersNeedingMatches->shift();
            $player2Id = $playersNeedingMatches->shift();
            
            $player1 = $players->firstWhere('id', $player1Id);
            $player2 = $players->firstWhere('id', $player2Id);
            
            if (!$player1 || !$player2) continue;
            
            // Determine pair type
            $type = $this->determinePairType($player1, $player2);
            
            // Ensure user_lo_id < user_hi_id
            $userLoId = min($player1Id, $player2Id);
            $userHiId = max($player1Id, $player2Id);
            
            $pair = Pair::create([
                'phase_id' => $phase->id,
                'user_lo_id' => $userLoId,
                'user_hi_id' => $userHiId,
                'type' => $type,
                'created_by_algorithm' => true
            ]);
            
            $result['pairs']->push($pair);
            
            // Update match counts
            $playerMatchCount[$player1Id]++;
            $playerMatchCount[$player2Id]++;
            
            // Remove players who have reached target matches
            if ($playerMatchCount[$player1Id] >= $targetMatches) {
                $playersNeedingMatches = $playersNeedingMatches->reject(fn($id) => $id == $player1Id);
            }
            if ($playerMatchCount[$player2Id] >= $targetMatches) {
                $playersNeedingMatches = $playersNeedingMatches->reject(fn($id) => $id == $player2Id);
            }
        }
    }

    /**
     * Determine pair type based on player genders.
     */
    protected function determinePairType(User $player1, User $player2): string
    {
        if ($player1->gender !== $player2->gender) {
            return 'XD'; // Mixed Doubles
        }
        
        return $player1->gender === 'M' ? 'MD' : 'WD';
    }

    /**
     * Validate resulting pairs for feasibility issues.
     */
    protected function validateResultingPairs(Collection $players, int $matchesPerPlayer, array &$result): void
    {
        $pairs = $result['pairs'];
        
        // Count matches per player
        $playerMatchCount = [];
        foreach ($players as $player) {
            $playerMatchCount[$player->id] = 0;
        }
        
        foreach ($pairs as $pair) {
            $playerMatchCount[$pair->user_lo_id]++;
            $playerMatchCount[$pair->user_hi_id]++;
        }
        
        // Calculate statistics
        $totalPlayerMatches = array_sum($playerMatchCount);
        $expectedTotalMatches = $players->count() * $matchesPerPlayer;
        $actualMatches = $totalPlayerMatches / 4; // Each match has 4 players
        $expectedMatches = $expectedTotalMatches / 4;
        
        $result['stats']['actual_matches_per_player'] = $players->count() > 0 
            ? round($totalPlayerMatches / $players->count(), 2) 
            : 0;
        $result['stats']['expected_matches_per_player'] = $matchesPerPlayer;
        $result['stats']['total_matches'] = $actualMatches;
        $result['stats']['feasible'] = ($expectedTotalMatches % 4 === 0);
        
        // Add warnings if not perfectly balanced
        $warnings = [];
        if (!$result['stats']['feasible']) {
            $warnings[] = "Với {$players->count()} người chơi và {$matchesPerPlayer} trận/người, không thể tạo lịch hoàn hảo";
        }
        
        $result['warnings'] = $warnings;
    }

    /**
     * Validate pairing constraints.
     */
    public function validatePairings(Collection $pairs, Collection $players, int $matchesPerPlayer): array
    {
        $errors = [];
        
        // Check if all pairs have valid gender combinations
        foreach ($pairs as $pair) {
            if (!$pair->isValidComposition()) {
                $errors[] = "Cặp {$pair->id} có thành phần giới tính không hợp lệ cho loại {$pair->type}";
            }
        }
        
        // Check matches per player
        $playerMatchCount = [];
        foreach ($players as $player) {
            $playerMatchCount[$player->id] = 0;
        }
        
        foreach ($pairs as $pair) {
            $playerMatchCount[$pair->user_lo_id]++;
            $playerMatchCount[$pair->user_hi_id]++;
        }
        
        foreach ($playerMatchCount as $playerId => $count) {
            if ($count !== $matchesPerPlayer) {
                $player = $players->firstWhere('id', $playerId);
                $errors[] = "Người chơi {$player->name} có {$count} trận thay vì {$matchesPerPlayer} trận";
            }
        }
        
        return [
            'is_valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
