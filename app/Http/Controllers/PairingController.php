<?php

namespace App\Http\Controllers;

use App\Models\Phase;
use App\Services\PairingAlgorithmService;
use App\Services\SchedulingService;
use App\Services\TournamentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class PairingController extends Controller
{
    protected PairingAlgorithmService $pairingService;
    protected SchedulingService $schedulingService;
    protected TournamentService $tournamentService;

    public function __construct(
        PairingAlgorithmService $pairingService,
        SchedulingService $schedulingService,
        TournamentService $tournamentService
    ) {
        $this->pairingService = $pairingService;
        $this->schedulingService = $schedulingService;
        $this->tournamentService = $tournamentService;
    }

    // ==================== WEB METHODS ====================

    /**
     * Display pairing index page.
     */
    public function index()
    {
        return view('admin.pairing.index');
    }

    /**
     * Display pairing generation page.
     */
    public function generate()
    {
        return view('admin.pairing.generate');
    }

    /**
     * Display scheduling page.
     */
    public function schedule()
    {
        return view('admin.pairing.schedule');
    }

    // ==================== API METHODS ====================

    /**
     * Get phases available for pairing.
     */
    public function getPhases(): JsonResponse
    {
        try {
            $phases = Phase::with(['users'])
                ->where('status', 'draft')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($phase) {
                    return [
                        'id' => $phase->id,
                        'name' => $phase->name,
                        'display_name' => $phase->getPhaseDisplayName(),
                        'type' => $phase->type,
                        'players_count' => $phase->getPlayers()->count(),
                        'can_generate_pairs' => $phase->canGeneratePairs(),
                        'can_schedule' => $phase->canScheduleMatches(),
                        'status' => $phase->status,
                        'matches_per_player' => $phase->matches_per_player,
                        'created_at' => $phase->created_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $phases
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi khi tải danh sách vòng đấu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate pairs for a phase.
     */
    public function generatePairs(Request $request): JsonResponse
    {
        $request->validate([
            'phase_id' => 'required|integer|exists:phases,id',
            'matches_per_player' => 'required|integer|min:1|max:10'
        ]);

        try {
            $phase = Phase::findOrFail($request->phase_id);
            $players = $phase->getPlayers();

            if ($players->count() < 4) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cần ít nhất 4 người chơi để tạo cặp đôi'
                ], 400);
            }

            // Clear existing matches and pairs for this phase before generating new ones
            $phase->matches()->delete(); // Delete matches first due to foreign key constraints
            $phase->pairs()->delete();

            $result = $this->pairingService->generateOptimalPairs(
                $phase, 
                $players, 
                $request->matches_per_player
            );

            // Load user details for pairs
            $result['pairs'] = $result['pairs']->map(function ($pair) {
                $pair->load(['userLo', 'userHi']);
                return [
                    'id' => $pair->id,
                    'phase_id' => $pair->phase_id,
                    'user_lo_id' => $pair->user_lo_id,
                    'user_hi_id' => $pair->user_hi_id,
                    'type' => $pair->type,
                    'user_lo' => [
                        'id' => $pair->userLo->id,
                        'name' => $pair->userLo->name,
                        'gender' => $pair->userLo->gender
                    ],
                    'user_hi' => [
                        'id' => $pair->userHi->id,
                        'name' => $pair->userHi->name,
                        'gender' => $pair->userHi->gender
                    ],
                    'created_by_algorithm' => $pair->created_by_algorithm,
                    'created_at' => $pair->created_at,
                    'updated_at' => $pair->updated_at
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Tạo cặp đôi thành công',
                'data' => $result
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi khi tạo cặp đôi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Schedule matches for a phase.
     */
    public function scheduleMatches(Request $request): JsonResponse
    {
        $request->validate([
            'phase_id' => 'required|integer|exists:phases,id'
        ]);

        try {
            $phase = Phase::findOrFail($request->phase_id);
            
            if (!$phase->canScheduleMatches()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vòng đấu chưa sẵn sàng để lên lịch. Vui lòng tạo cặp đôi trước.'
                ], 400);
            }

            $result = $this->schedulingService->schedulePhaseMatches($phase);

            return response()->json([
                'success' => true,
                'message' => 'Lên lịch thi đấu thành công',
                'data' => $result
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi khi lên lịch thi đấu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pairs for a phase.
     */
    public function getPairs(Request $request): JsonResponse
    {
        $request->validate([
            'phase_id' => 'required|integer|exists:phases,id'
        ]);

        try {
            $phase = Phase::with(['pairs.userLo', 'pairs.userHi'])->findOrFail($request->phase_id);
            
            $pairs = $phase->pairs->map(function ($pair) {
                return [
                    'id' => $pair->id,
                    'type' => $pair->type,
                    'type_name' => $pair->getTypeNameAttribute(),
                    'user_lo' => [
                        'id' => $pair->userLo->id,
                        'name' => $pair->userLo->name,
                        'gender' => $pair->userLo->gender
                    ],
                    'user_hi' => [
                        'id' => $pair->userHi->id,
                        'name' => $pair->userHi->name,
                        'gender' => $pair->userHi->gender
                    ],
                    'pair_name' => $pair->getPairNameAttribute(),
                    'created_by_algorithm' => $pair->created_by_algorithm,
                    'created_at' => $pair->created_at
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'phase' => [
                        'id' => $phase->id,
                        'name' => $phase->name,
                        'display_name' => $phase->getPhaseDisplayName(),
                        'type' => $phase->type
                    ],
                    'pairs' => $pairs,
                    'pairs_by_type' => [
                        'XD' => $pairs->where('type', 'XD')->count(),
                        'MD' => $pairs->where('type', 'MD')->count(),
                        'WD' => $pairs->where('type', 'WD')->count(),
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi khi tải danh sách cặp đôi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get scheduled matches for a phase.
     */
    public function getScheduledMatches(Request $request): JsonResponse
    {
        $request->validate([
            'phase_id' => 'required|integer|exists:phases,id'
        ]);

        try {
            $phase = Phase::with(['matches.timeSlot', 'matches.court', 'matches.pairA', 'matches.pairB'])->findOrFail($request->phase_id);
            
            $matches = $phase->matches->map(function ($match) {
                return [
                    'id' => $match->id,
                    'type' => $match->type,
                    'type_name' => $match->getTypeNameAttribute(),
                    'status' => $match->status,
                    'status_name' => $match->getStatusNameAttribute(),
                    'pair_a' => [
                        'id' => $match->pairA->id,
                        'name' => $match->pairA->getPairNameAttribute(),
                        'players' => [
                            ['name' => $match->pairA->userLo->name, 'gender' => $match->pairA->userLo->gender],
                            ['name' => $match->pairA->userHi->name, 'gender' => $match->pairA->userHi->gender]
                        ]
                    ],
                    'pair_b' => [
                        'id' => $match->pairB->id,
                        'name' => $match->pairB->getPairNameAttribute(),
                        'players' => [
                            ['name' => $match->pairB->userLo->name, 'gender' => $match->pairB->userLo->gender],
                            ['name' => $match->pairB->userHi->name, 'gender' => $match->pairB->userHi->gender]
                        ]
                    ],
                    'court' => $match->court ? [
                        'id' => $match->court->id,
                        'name' => $match->court->name
                    ] : null,
                    'time_slot' => $match->timeSlot ? [
                        'id' => $match->timeSlot->id,
                        'start_at' => $match->timeSlot->start_at,
                        'end_at' => $match->timeSlot->end_at,
                        'time_range' => $match->timeSlot->getTimeRangeAttribute()
                    ] : null,
                    'created_at' => $match->created_at
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'phase' => [
                        'id' => $phase->id,
                        'name' => $phase->name,
                        'display_name' => $phase->getPhaseDisplayName(),
                        'type' => $phase->type
                    ],
                    'matches' => $matches,
                    'matches_by_court' => $matches->groupBy('court.name'),
                    'matches_by_status' => [
                        'scheduled' => $matches->where('status', 'scheduled')->count(),
                        'playing' => $matches->where('status', 'playing')->count(),
                        'finished' => $matches->where('status', 'finished')->count(),
                        'canceled' => $matches->where('status', 'canceled')->count(),
                    ]
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi khi tải lịch thi đấu: ' . $e->getMessage()
            ], 500);
        }
    }
}
