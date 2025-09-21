<?php

namespace App\Services;

use App\Models\Phase;
use App\Models\Court;
use App\Models\TimeSlot;
use App\Models\BadmintonMatch;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Exception;

class SchedulingService
{
    /**
     * Distribute matches to courts and time slots.
     */
    public function distributeToCourts(Phase $phase, Collection $matches): array
    {
        $courts = Court::where('is_active', true)->get();
        
        if ($courts->isEmpty()) {
            throw new Exception('Không có sân nào khả dụng');
        }

        // Create time slots for the phase
        $timeSlots = $this->createTimeSlots($phase, $courts);
        
        // Distribute matches to time slots
        $scheduledMatches = $this->scheduleMatchesToTimeSlots($matches, $timeSlots);
        
        return [
            'scheduled_matches' => $scheduledMatches,
            'time_slots_created' => $timeSlots->count(),
            'courts_used' => $courts->count(),
            'total_matches' => $matches->count(),
            'scheduling_stats' => $this->getSchedulingStats($scheduledMatches, $timeSlots)
        ];
    }

    /**
     * Create time slots for courts in the phase.
     */
    protected function createTimeSlots(Phase $phase, Collection $courts): Collection
    {
        $timeSlots = collect();
        $startDate = $phase->start_at ?? now();
        
        // Create time slots for each court (8AM - 12PM, 1-hour slots)
        foreach ($courts as $court) {
            $currentTime = Carbon::parse($startDate)->setTime(8, 0, 0);
            $endTime = Carbon::parse($startDate)->setTime(12, 0, 0);
            
            while ($currentTime->lt($endTime)) {
                $slotEnd = $currentTime->copy()->addHour();
                
                // Check if time slot already exists
                $existingSlot = TimeSlot::where('phase_id', $phase->id)
                    ->where('court_id', $court->id)
                    ->where('start_at', $currentTime)
                    ->first();
                
                if (!$existingSlot) {
                    $timeSlot = TimeSlot::create([
                        'phase_id' => $phase->id,
                        'court_id' => $court->id,
                        'start_at' => $currentTime,
                        'end_at' => $slotEnd
                    ]);
                    
                    $timeSlots->push($timeSlot);
                }
                
                $currentTime->addHour();
            }
        }
        
        return $timeSlots;
    }

    /**
     * Schedule matches to available time slots.
     */
    protected function scheduleMatchesToTimeSlots(Collection $matches, Collection $timeSlots): Collection
    {
        $scheduledMatches = collect();
        $availableSlots = $timeSlots->shuffle(); // Randomize slot assignment
        $slotIndex = 0;
        
        foreach ($matches as $match) {
            if ($slotIndex >= $availableSlots->count()) {
                // If we run out of slots, create additional slots or skip
                break;
            }
            
            $timeSlot = $availableSlots[$slotIndex];
            
            // Check for player conflicts
            if (!$this->hasPlayerConflicts($match, $timeSlot, $scheduledMatches)) {
                // Assign match to time slot
                $match->update([
                    'time_slot_id' => $timeSlot->id,
                    'court_id' => $timeSlot->court_id
                ]);
                
                $scheduledMatches->push($match);
                $slotIndex++;
            } else {
                // Find next available slot without conflicts
                $foundSlot = $this->findAvailableSlot($match, $availableSlots, $slotIndex, $scheduledMatches);
                
                if ($foundSlot) {
                    $match->update([
                        'time_slot_id' => $foundSlot->id,
                        'court_id' => $foundSlot->court_id
                    ]);
                    
                    $scheduledMatches->push($match);
                }
            }
        }
        
        return $scheduledMatches;
    }

    /**
     * Check if a match has player conflicts with existing scheduled matches.
     */
    protected function hasPlayerConflicts(BadmintonMatch $match, TimeSlot $timeSlot, Collection $scheduledMatches): bool
    {
        $matchPlayerIds = $this->getMatchPlayerIds($match);
        
        // Check conflicts with matches in the same time slot
        $conflictingMatches = $scheduledMatches->filter(function ($scheduledMatch) use ($timeSlot) {
            return $scheduledMatch->time_slot_id === $timeSlot->id;
        });
        
        foreach ($conflictingMatches as $conflictingMatch) {
            $conflictingPlayerIds = $this->getMatchPlayerIds($conflictingMatch);
            
            // Check if any player appears in both matches
            if (!empty(array_intersect($matchPlayerIds, $conflictingPlayerIds))) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get player IDs from a match.
     */
    protected function getMatchPlayerIds(BadmintonMatch $match): array
    {
        $playerIds = [];
        
        if ($match->pairA) {
            $playerIds[] = $match->pairA->user_lo_id;
            $playerIds[] = $match->pairA->user_hi_id;
        }
        
        if ($match->pairB) {
            $playerIds[] = $match->pairB->user_lo_id;
            $playerIds[] = $match->pairB->user_hi_id;
        }
        
        return $playerIds;
    }

    /**
     * Find an available time slot without conflicts.
     */
    protected function findAvailableSlot(BadmintonMatch $match, Collection $timeSlots, int $startIndex, Collection $scheduledMatches): ?TimeSlot
    {
        for ($i = $startIndex; $i < $timeSlots->count(); $i++) {
            $timeSlot = $timeSlots[$i];
            
            if (!$this->hasPlayerConflicts($match, $timeSlot, $scheduledMatches)) {
                return $timeSlot;
            }
        }
        
        return null;
    }

    /**
     * Optimize court utilization by balancing matches across courts.
     */
    public function optimizeCourtUtilization(int $phaseId): array
    {
        $phase = Phase::findOrFail($phaseId);
        $matches = $phase->matches()->with(['timeSlot', 'court'])->get();
        
        $courtStats = [];
        $timeSlotStats = [];
        
        foreach ($matches as $match) {
            if ($match->court) {
                $courtId = $match->court->id;
                $courtStats[$courtId] = ($courtStats[$courtId] ?? 0) + 1;
            }
            
            if ($match->timeSlot) {
                $timeSlotId = $match->timeSlot->id;
                $timeSlotStats[$timeSlotId] = ($timeSlotStats[$timeSlotId] ?? 0) + 1;
            }
        }
        
        return [
            'court_utilization' => $courtStats,
            'time_slot_utilization' => $timeSlotStats,
            'total_matches' => $matches->count(),
            'average_matches_per_court' => !empty($courtStats) ? array_sum($courtStats) / count($courtStats) : 0,
            'utilization_balance' => $this->calculateUtilizationBalance($courtStats)
        ];
    }

    /**
     * Calculate utilization balance (lower is better).
     */
    protected function calculateUtilizationBalance(array $courtStats): float
    {
        if (empty($courtStats)) {
            return 0;
        }
        
        $average = array_sum($courtStats) / count($courtStats);
        $variance = 0;
        
        foreach ($courtStats as $count) {
            $variance += pow($count - $average, 2);
        }
        
        return sqrt($variance / count($courtStats));
    }

    /**
     * Get scheduling statistics.
     */
    protected function getSchedulingStats(Collection $scheduledMatches, Collection $timeSlots): array
    {
        $courtUsage = [];
        $timeSlotUsage = [];
        
        foreach ($scheduledMatches as $match) {
            if ($match->court_id) {
                $courtUsage[$match->court_id] = ($courtUsage[$match->court_id] ?? 0) + 1;
            }
            
            if ($match->time_slot_id) {
                $timeSlotUsage[$match->time_slot_id] = ($timeSlotUsage[$match->time_slot_id] ?? 0) + 1;
            }
        }
        
        return [
            'courts_used' => count($courtUsage),
            'time_slots_used' => count($timeSlotUsage),
            'matches_per_court' => $courtUsage,
            'matches_per_time_slot' => $timeSlotUsage,
            'scheduling_efficiency' => $scheduledMatches->count() / max($timeSlots->count(), 1) * 100
        ];
    }

    /**
     * Validate schedule for conflicts.
     */
    public function validateSchedule(int $phaseId): array
    {
        $phase = Phase::findOrFail($phaseId);
        $matches = $phase->matches()->with(['timeSlot', 'pairA', 'pairB'])->get();
        
        $conflicts = [];
        $timeSlotGroups = $matches->groupBy('time_slot_id');
        
        foreach ($timeSlotGroups as $timeSlotId => $timeSlotMatches) {
            if ($timeSlotMatches->count() > 1) {
                // Check for player conflicts within the same time slot
                $allPlayerIds = [];
                
                foreach ($timeSlotMatches as $match) {
                    $matchPlayerIds = $this->getMatchPlayerIds($match);
                    
                    foreach ($matchPlayerIds as $playerId) {
                        if (in_array($playerId, $allPlayerIds)) {
                            $conflicts[] = [
                                'type' => 'player_conflict',
                                'time_slot_id' => $timeSlotId,
                                'player_id' => $playerId,
                                'matches' => $timeSlotMatches->pluck('id')->toArray()
                            ];
                        } else {
                            $allPlayerIds[] = $playerId;
                        }
                    }
                }
            }
        }
        
        return [
            'has_conflicts' => !empty($conflicts),
            'conflicts' => $conflicts,
            'total_matches_checked' => $matches->count(),
            'time_slots_checked' => $timeSlotGroups->count()
        ];
    }
}
