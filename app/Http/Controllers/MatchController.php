<?php

namespace App\Http\Controllers;

use App\Models\BadmintonMatch;
use App\Models\Phase;
use App\Services\TournamentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class MatchController extends Controller
{
    protected TournamentService $tournamentService;

    public function __construct(TournamentService $tournamentService)
    {
        $this->tournamentService = $tournamentService;
    }

    /**
     * Display a listing of matches.
     */
    public function index(Request $request): JsonResponse
    {
        $query = BadmintonMatch::with([
            'phase', 
            'timeSlot', 
            'court', 
            'pairA.userLo', 
            'pairA.userHi',
            'pairB.userLo', 
            'pairB.userHi'
        ]);

        // Filter by phase
        if ($request->has('phase_id')) {
            $query->where('phase_id', $request->phase_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by court
        if ($request->has('court_id')) {
            $query->where('court_id', $request->court_id);
        }

        // Filter by date
        if ($request->has('date')) {
            $query->whereHas('timeSlot', function ($q) use ($request) {
                $q->whereDate('start_at', $request->date);
            });
        }

        $matches = $query->orderBy('created_at', 'desc')->paginate(20);

        $matches->getCollection()->transform(function ($match) {
            return [
                'id' => $match->id,
                'phase' => [
                    'id' => $match->phase->id,
                    'name' => $match->phase->name,
                    'display_name' => $match->phase->getPhaseDisplayName(),
                ],
                'type' => $match->type,
                'type_name' => $match->getTypeNameAttribute(),
                'status' => $match->status,
                'status_name' => $match->getStatusNameAttribute(),
                'pair_a' => [
                    'id' => $match->pairA->id,
                    'name' => $match->pairA->getPairNameAttribute(),
                    'players' => [
                        ['id' => $match->pairA->userLo->id, 'name' => $match->pairA->userLo->name],
                        ['id' => $match->pairA->userHi->id, 'name' => $match->pairA->userHi->name],
                    ]
                ],
                'pair_b' => [
                    'id' => $match->pairB->id,
                    'name' => $match->pairB->getPairNameAttribute(),
                    'players' => [
                        ['id' => $match->pairB->userLo->id, 'name' => $match->pairB->userLo->name],
                        ['id' => $match->pairB->userHi->id, 'name' => $match->pairB->userHi->name],
                    ]
                ],
                'score_display' => $match->getScoreDisplayAttribute(),
                'winner_name' => $match->getWinnerNameAttribute(),
                'court' => $match->court ? [
                    'id' => $match->court->id,
                    'name' => $match->court->name
                ] : null,
                'time_slot' => $match->timeSlot ? [
                    'id' => $match->timeSlot->id,
                    'start_at' => $match->timeSlot->start_at,
                    'end_at' => $match->timeSlot->end_at,
                    'time_range' => $match->timeSlot->getTimeRangeAttribute(),
                ] : null,
                'created_at' => $match->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $matches
        ]);
    }

    /**
     * Display the specified match.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $match = BadmintonMatch::with([
                'phase', 
                'timeSlot', 
                'court', 
                'pairA.userLo', 
                'pairA.userHi',
                'pairB.userLo', 
                'pairB.userHi',
                'participants.user'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $match->id,
                    'phase' => [
                        'id' => $match->phase->id,
                        'name' => $match->phase->name,
                        'display_name' => $match->phase->getPhaseDisplayName(),
                    ],
                    'type' => $match->type,
                    'type_name' => $match->getTypeNameAttribute(),
                    'status' => $match->status,
                    'status_name' => $match->getStatusNameAttribute(),
                    'pair_a' => [
                        'id' => $match->pairA->id,
                        'name' => $match->pairA->getPairNameAttribute(),
                        'players' => [
                            ['id' => $match->pairA->userLo->id, 'name' => $match->pairA->userLo->name, 'gender' => $match->pairA->userLo->gender],
                            ['id' => $match->pairA->userHi->id, 'name' => $match->pairA->userHi->name, 'gender' => $match->pairA->userHi->gender],
                        ]
                    ],
                    'pair_b' => [
                        'id' => $match->pairB->id,
                        'name' => $match->pairB->getPairNameAttribute(),
                        'players' => [
                            ['id' => $match->pairB->userLo->id, 'name' => $match->pairB->userLo->name, 'gender' => $match->pairB->userLo->gender],
                            ['id' => $match->pairB->userHi->id, 'name' => $match->pairB->userHi->name, 'gender' => $match->pairB->userHi->gender],
                        ]
                    ],
                    'scores' => [
                        'team_a' => $match->score_team_a,
                        'team_b' => $match->score_team_b,
                        'display' => $match->getScoreDisplayAttribute(),
                    ],
                    'winner' => $match->winner,
                    'winner_name' => $match->getWinnerNameAttribute(),
                    'court' => $match->court ? [
                        'id' => $match->court->id,
                        'name' => $match->court->name
                    ] : null,
                    'time_slot' => $match->timeSlot ? [
                        'id' => $match->timeSlot->id,
                        'start_at' => $match->timeSlot->start_at,
                        'end_at' => $match->timeSlot->end_at,
                        'time_range' => $match->timeSlot->getTimeRangeAttribute(),
                        'date_time_range' => $match->timeSlot->getDateTimeRangeAttribute(),
                    ] : null,
                    'participants' => $match->participants->map(function ($participant) {
                        return [
                            'id' => $participant->id,
                            'user' => [
                                'id' => $participant->user->id,
                                'name' => $participant->user->name,
                                'gender' => $participant->user->gender,
                            ],
                            'team_side' => $participant->team_side,
                            'team_side_name' => $participant->getTeamSideNameAttribute(),
                            'result' => $participant->result,
                            'result_name' => $participant->getResultNameAttribute(),
                            'points' => $participant->points,
                        ];
                    }),
                    'created_at' => $match->created_at,
                    'updated_at' => $match->updated_at,
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy trận đấu: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update match result (API for external system).
     */
    public function updateResult(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'score_team_a' => 'required|integer|min:0',
            'score_team_b' => 'required|integer|min:0',
            'participants' => 'required|array|min:4|max:4',
            'participants.*.user_id' => 'required|integer|exists:users,id'
        ]);

        try {
            $result = $this->tournamentService->updateMatchResult($id, $request->all());

            if ($result) {
                $match = BadmintonMatch::with(['pairA', 'pairB'])->findOrFail($id);

                return response()->json([
                    'success' => true,
                    'message' => 'Đã cập nhật kết quả trận đấu thành công',
                    'data' => [
                        'id' => $match->id,
                        'status' => $match->status,
                        'scores' => [
                            'team_a' => $match->score_team_a,
                            'team_b' => $match->score_team_b,
                            'display' => $match->getScoreDisplayAttribute(),
                        ],
                        'winner' => $match->winner,
                        'winner_name' => $match->getWinnerNameAttribute(),
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Không thể cập nhật kết quả trận đấu'
            ], 400);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Start a match.
     */
    public function start(int $id): JsonResponse
    {
        try {
            $match = BadmintonMatch::findOrFail($id);

            if ($match->start()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Trận đấu đã bắt đầu',
                    'data' => [
                        'id' => $match->id,
                        'status' => $match->status,
                        'status_name' => $match->getStatusNameAttribute(),
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Không thể bắt đầu trận đấu này'
            ], 400);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel a match.
     */
    public function cancel(int $id): JsonResponse
    {
        try {
            $match = BadmintonMatch::findOrFail($id);

            if ($match->cancel()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Trận đấu đã được hủy',
                    'data' => [
                        'id' => $match->id,
                        'status' => $match->status,
                        'status_name' => $match->getStatusNameAttribute(),
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Không thể hủy trận đấu này'
            ], 400);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get today's schedule.
     */
    public function todaySchedule(): JsonResponse
    {
        $matches = BadmintonMatch::with([
            'phase', 
            'timeSlot', 
            'court', 
            'pairA.userLo', 
            'pairA.userHi',
            'pairB.userLo', 
            'pairB.userHi'
        ])
        ->today()
        ->orderBy('created_at')
        ->get();

        $schedule = $matches->map(function ($match) {
            return [
                'id' => $match->id,
                'phase_name' => $match->phase->getPhaseDisplayName(),
                'type_name' => $match->getTypeNameAttribute(),
                'pair_a_name' => $match->pairA->getPairNameAttribute(),
                'pair_b_name' => $match->pairB->getPairNameAttribute(),
                'court_name' => $match->court?->name,
                'time_range' => $match->timeSlot?->getTimeRangeAttribute(),
                'status' => $match->status,
                'status_name' => $match->getStatusNameAttribute(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'date' => now()->format('Y-m-d'),
                'total_matches' => $schedule->count(),
                'matches' => $schedule
            ]
        ]);
    }

    // ==================== WEB METHODS ====================

    /**
     * Display matches index page.
     */
    public function webIndex()
    {
        return view('admin.matches.index');
    }

    /**
     * Display match details page.
     */
    public function webShow(int $id)
    {
        return view('admin.matches.show', compact('id'));
    }

    /**
     * Display today's matches page.
     */
    public function today()
    {
        return view('admin.matches.today');
    }
}
