<?php

namespace App\Http\Controllers;

use App\Models\Phase;
use App\Services\TournamentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Exception;

class TournamentController extends Controller
{
    protected TournamentService $tournamentService;

    public function __construct(TournamentService $tournamentService)
    {
        $this->tournamentService = $tournamentService;
    }

    /**
     * Display a listing of tournament phases for web interface.
     */
    public function webIndex(): View
    {
        return view('admin.tournaments.index');
    }

    /**
     * Show the form for creating a new tournament.
     */
    public function create(): View
    {
        return view('admin.tournaments.create');
    }

    /**
     * Show tournament details for web interface.
     */
    public function webShow(int $id): View
    {
        return view('admin.tournaments.show', compact('id'));
    }

    /**
     * Show the form for editing a tournament.
     */
    public function edit(int $id): View
    {
        return view('admin.tournaments.edit', compact('id'));
    }

    /**
     * Display a listing of tournament phases for API.
     */
    public function index(): JsonResponse
    {
        $phases = Phase::with(['pairs', 'matches'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($phase) {
                return [
                    'id' => $phase->id,
                    'type' => $phase->type,
                    'name' => $phase->name,
                    'display_name' => $phase->getPhaseDisplayName(),
                    'status' => $phase->status,
                    'status_name' => $phase->getStatusNameAttribute(),
                    'matches_per_player' => $phase->matches_per_player,
                    'start_at' => $phase->start_at,
                    'end_at' => $phase->end_at,
                    'total_pairs' => $phase->pairs->count(),
                    'total_matches' => $phase->matches->count(),
                    'can_generate_pairs' => $phase->canGeneratePairs(),
                    'can_schedule_matches' => $phase->canScheduleMatches(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $phases
        ]);
    }

    /**
     * Store a newly created tournament phase.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:vong_loai,ban_ket,chung_ket',
            'name' => 'required|string|max:255',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after:start_at',
            'matches_per_player' => 'integer|min:1|max:10'
        ]);

        try {
            $phase = Phase::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Vòng đấu đã được tạo thành công',
                'data' => [
                    'id' => $phase->id,
                    'type' => $phase->type,
                    'name' => $phase->name,
                    'display_name' => $phase->getPhaseDisplayName(),
                    'status' => $phase->status,
                    'matches_per_player' => $phase->matches_per_player,
                ]
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo vòng đấu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified tournament phase.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $stats = $this->tournamentService->getPhaseStats($id);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy vòng đấu: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Import players into a tournament phase.
     */
    public function importPlayers(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'players' => 'required|array|min:4',
            'players.*.name' => 'required|string|max:255',
            'players.*.gender' => 'required|in:M,F'
        ]);

        try {
            $result = $this->tournamentService->importPlayers($request->players, $id);

            return response()->json([
                'success' => true,
                'message' => "Đã import thành công {$result['total_imported']} người chơi",
                'data' => $result
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi import: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Generate pairs for a tournament phase.
     */
    public function generatePairs(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'matches_per_player' => 'required|integer|min:1|max:10'
        ]);

        try {
            $result = $this->tournamentService->generatePairings($id, $request->matches_per_player);

            return response()->json([
                'success' => true,
                'message' => "Đã tạo thành công {$result['stats']['total_pairs']} cặp thi đấu",
                'data' => $result
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo cặp: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Schedule matches for a tournament phase.
     */
    public function scheduleMatches(int $id): JsonResponse
    {
        try {
            $result = $this->tournamentService->scheduleMatches($id);

            return response()->json([
                'success' => true,
                'message' => "Đã lên lịch thành công {$result['total_matches']} trận đấu",
                'data' => $result
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lên lịch: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update tournament phase status.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:draft,scheduled,playing,completed,archived'
        ]);

        try {
            $phase = Phase::findOrFail($id);

            if (!$phase->canTransitionTo($request->status)) {
                return response()->json([
                    'success' => false,
                    'message' => "Không thể chuyển từ trạng thái '{$phase->status}' sang '{$request->status}'"
                ], 400);
            }

            $phase->update(['status' => $request->status]);

            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật trạng thái vòng đấu',
                'data' => [
                    'id' => $phase->id,
                    'status' => $phase->status,
                    'status_name' => $phase->getStatusNameAttribute()
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete tournament phase.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $phase = Phase::findOrFail($id);

            if ($phase->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Chỉ có thể xóa vòng đấu ở trạng thái draft'
                ], 400);
            }

            $phase->delete();

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa vòng đấu thành công'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa: ' . $e->getMessage()
            ], 500);
        }
    }
}
