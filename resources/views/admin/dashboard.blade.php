@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard Tổng Quan')

@section('content')
<div class="row">
    <!-- Stats Cards -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card bg-gradient-primary">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h3 id="totalTournaments">0</h3>
                        <p>Tổng Vòng Đấu</p>
                    </div>
                    <div class="fs-1 opacity-75">
                        <i class="fas fa-trophy"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card bg-gradient-success">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h3 id="totalPlayers">0</h3>
                        <p>Tổng Người Chơi</p>
                    </div>
                    <div class="fs-1 opacity-75">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card bg-gradient-warning">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h3 id="totalMatches">0</h3>
                        <p>Tổng Trận Đấu</p>
                    </div>
                    <div class="fs-1 opacity-75">
                        <i class="fas fa-table-tennis"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card bg-gradient-info">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h3 id="completionRate">0%</h3>
                        <p>Tỷ Lệ Hoàn Thành</p>
                    </div>
                    <div class="fs-1 opacity-75">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Tournaments -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Vòng Đấu Gần Đây</h5>
                <a href="{{ route('admin.tournaments.index') }}" class="btn btn-sm btn-outline-primary">
                    Xem Tất Cả <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tên Vòng Đấu</th>
                                <th>Loại</th>
                                <th>Trạng Thái</th>
                                <th>Ngày Bắt Đầu</th>
                                <th>Hành Động</th>
                            </tr>
                        </thead>
                        <tbody id="recentTournamentsTable">
                            <tr>
                                <td colspan="5" class="text-center">
                                    <div class="spinner-border spinner-border-sm" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    Đang tải dữ liệu...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Hành Động Nhanh</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.tournaments.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Tạo Vòng Đấu Mới
                    </a>
                    <a href="{{ route('admin.players.import') }}" class="btn btn-success">
                        <i class="fas fa-upload me-2"></i>Import Người Chơi
                    </a>
                    <a href="{{ route('admin.matches.today') }}" class="btn btn-warning">
                        <i class="fas fa-calendar-day me-2"></i>Lịch Thi Đấu Hôm Nay
                    </a>
                    <a href="{{ route('admin.leaderboard.index') }}" class="btn btn-info">
                        <i class="fas fa-chart-line me-2"></i>Xem Bảng Xếp Hạng
                    </a>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-server me-2"></i>Trạng Thái Hệ Thống</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>API Server</span>
                    <span class="badge bg-success" id="apiStatus">Online</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Database</span>
                    <span class="badge bg-success" id="dbStatus">Connected</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span>Last Update</span>
                    <small class="text-muted" id="lastUpdate">Just now</small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Today's Matches -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-calendar-day me-2"></i>Trận Đấu Hôm Nay</h5>
                <a href="{{ route('admin.matches.today') }}" class="btn btn-sm btn-outline-primary">
                    Chi Tiết <i class="fas fa-external-link-alt ms-1"></i>
                </a>
            </div>
            <div class="card-body">
                <div id="todayMatches">
                    <div class="text-center">
                        <div class="spinner-border spinner-border-sm" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Đang tải lịch thi đấu...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Players -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-star me-2"></i>Top Người Chơi</h5>
                <a href="{{ route('admin.leaderboard.index') }}" class="btn btn-sm btn-outline-primary">
                    Bảng Xếp Hạng <i class="fas fa-external-link-alt ms-1"></i>
                </a>
            </div>
            <div class="card-body">
                <div id="topPlayers">
                    <div class="text-center">
                        <div class="spinner-border spinner-border-sm" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Đang tải bảng xếp hạng...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
    
    // Auto refresh every 30 seconds
    setInterval(loadDashboardData, 30000);
});

async function loadDashboardData() {
    try {
        // Load tournaments summary
        const tournamentsResponse = await fetch('/api/admin/tournaments');
        const tournamentsData = await tournamentsResponse.json();
        
        if (tournamentsData.success) {
            updateStatsCards(tournamentsData.data);
            updateRecentTournaments(tournamentsData.data);
        }
        
        // Load today's matches
        const matchesResponse = await fetch('/api/matches/today');
        const matchesData = await matchesResponse.json();
        
        if (matchesData.success) {
            updateTodayMatches(matchesData.data);
        }
        
        // Load leaderboard summary
        const leaderboardResponse = await fetch('/api/leaderboard?limit=5');
        const leaderboardData = await leaderboardResponse.json();
        
        if (leaderboardData.success) {
            updateTopPlayers(leaderboardData.data.leaderboard);
        }
        
        updateLastUpdate();
        
    } catch (error) {
        console.error('Error loading dashboard data:', error);
        showToast('Có lỗi khi tải dữ liệu dashboard', 'danger');
    }
}

function updateStatsCards(tournaments) {
    const totalTournaments = tournaments.length;
    const totalMatches = tournaments.reduce((sum, t) => sum + t.total_matches, 0);
    const completedMatches = tournaments.reduce((sum, t) => sum + (t.matches_completed || 0), 0);
    const completionRate = totalMatches > 0 ? Math.round((completedMatches / totalMatches) * 100) : 0;
    
    document.getElementById('totalTournaments').textContent = totalTournaments;
    document.getElementById('totalMatches').textContent = totalMatches;
    document.getElementById('completionRate').textContent = completionRate + '%';
    
    // This would need to be calculated from actual player data
    document.getElementById('totalPlayers').textContent = '10'; // Placeholder
}

function updateRecentTournaments(tournaments) {
    const tbody = document.getElementById('recentTournamentsTable');
    
    if (tournaments.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Chưa có vòng đấu nào</td></tr>';
        return;
    }
    
    tbody.innerHTML = tournaments.slice(0, 5).map(tournament => `
        <tr>
            <td>
                <strong>${tournament.display_name}</strong><br>
                <small class="text-muted">${tournament.name}</small>
            </td>
            <td>
                <span class="badge badge-${tournament.type}">${tournament.display_name}</span>
            </td>
            <td>
                <span class="badge badge-${tournament.status}">${tournament.status_name}</span>
            </td>
            <td>
                <small>${tournament.start_at ? new Date(tournament.start_at).toLocaleDateString('vi-VN') : 'Chưa định'}</small>
            </td>
            <td>
                <a href="/admin/tournaments/${tournament.id}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-eye"></i>
                </a>
            </td>
        </tr>
    `).join('');
}

function updateTodayMatches(matchesData) {
    const container = document.getElementById('todayMatches');
    
    if (!matchesData.matches || matchesData.matches.length === 0) {
        container.innerHTML = '<p class="text-center text-muted">Không có trận đấu nào hôm nay</p>';
        return;
    }
    
    container.innerHTML = matchesData.matches.slice(0, 5).map(match => `
        <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
            <div>
                <strong>${match.pair_a_name}</strong> vs <strong>${match.pair_b_name}</strong><br>
                <small class="text-muted">
                    <i class="fas fa-clock me-1"></i>${match.time_range || 'Chưa định giờ'}
                    <i class="fas fa-map-marker-alt ms-2 me-1"></i>${match.court_name || 'Chưa định sân'}
                </small>
            </div>
            <span class="badge badge-${match.status}">${match.status_name}</span>
        </div>
    `).join('');
}

function updateTopPlayers(players) {
    const container = document.getElementById('topPlayers');
    
    if (!players || players.length === 0) {
        container.innerHTML = '<p class="text-center text-muted">Chưa có dữ liệu xếp hạng</p>';
        return;
    }
    
    container.innerHTML = players.slice(0, 5).map((player, index) => `
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="d-flex align-items-center">
                <div class="rank-badge rank-${index < 3 ? index + 1 : 'other'} me-3">
                    ${player.rank}
                </div>
                <div>
                    <strong>${player.name}</strong><br>
                    <small class="text-muted">${player.gender_name} • ${player.total_matches} trận</small>
                </div>
            </div>
            <div class="text-end">
                <strong>${player.total_points} điểm</strong><br>
                <small class="text-muted">${player.win_percentage}% thắng</small>
            </div>
        </div>
    `).join('');
}

function updateLastUpdate() {
    document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString('vi-VN');
}
</script>
@endpush
