<?php

namespace App\Http\Controllers;

use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class LeaderboardController extends Controller
{
    protected LeaderboardService $leaderboardService;

    public function __construct(LeaderboardService $leaderboardService)
    {
        $this->leaderboardService = $leaderboardService;
    }

    /**
     * Display the leaderboard.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'gender' => 'nullable|in:M,F',
            'phase_id' => 'nullable|integer|exists:phases,id'
        ]);

        try {
            $leaderboard = $this->leaderboardService->getLeaderboard($request->gender, $request->phase_id);

            return response()->json([
                'success' => true,
                'data' => $leaderboard
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tải bảng xếp hạng: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed player statistics.
     */
    public function playerStats(int $userId): JsonResponse
    {
        try {
            $stats = $this->leaderboardService->getPlayerStats($userId);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy thông tin người chơi: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get top performers.
     */
    public function topPerformers(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        try {
            $limit = $request->get('limit', 10);
            $topPerformers = $this->leaderboardService->getTopPerformers($limit);

            return response()->json([
                'success' => true,
                'data' => $topPerformers
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tournament summary.
     */
    public function summary(): JsonResponse
    {
        try {
            $summary = $this->leaderboardService->getTournamentSummary();

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export leaderboard data.
     */
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'gender' => 'nullable|in:M,F',
            'format' => 'nullable|in:json,csv'
        ]);

        try {
            $leaderboard = $this->leaderboardService->getLeaderboard($request->gender);
            $format = $request->get('format', 'json');

            if ($format === 'csv') {
                return $this->exportCsv($leaderboard);
            }

            return response()->json([
                'success' => true,
                'data' => $leaderboard,
                'export_info' => [
                    'format' => 'json',
                    'exported_at' => now(),
                    'total_records' => $leaderboard['total_players']
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xuất dữ liệu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export leaderboard as CSV.
     */
    protected function exportCsv(array $leaderboard): JsonResponse
    {
        $csvData = [];
        $csvData[] = ['Hạng', 'Họ Tên', 'Giới Tính', 'Số Trận', 'Thắng', 'Thua', 'Hòa', 'Điểm Tích Lũy', 'Tỷ Lệ Thắng (%)'];

        foreach ($leaderboard['leaderboard'] as $player) {
            $csvData[] = [
                $player['rank'],
                $player['name'],
                $player['gender_name'],
                $player['total_matches'],
                $player['wins'],
                $player['losses'],
                $player['draws'],
                $player['total_points'],
                $player['win_percentage']
            ];
        }

        // Convert to CSV string
        $csvString = '';
        foreach ($csvData as $row) {
            $csvString .= implode(',', array_map(function($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $row)) . "\n";
        }

        return response()->json([
            'success' => true,
            'data' => [
                'csv_content' => $csvString,
                'filename' => 'leaderboard_' . now()->format('Y-m-d_H-i-s') . '.csv',
                'export_info' => [
                    'format' => 'csv',
                    'exported_at' => now(),
                    'total_records' => count($csvData) - 1, // Exclude header
                    'filter' => $leaderboard['filter']
                ]
            ]
        ]);
    }

    // ==================== WEB METHODS ====================

    /**
     * Display leaderboard page.
     */
    public function webIndex()
    {
        return view('admin.leaderboard.index');
    }
}
