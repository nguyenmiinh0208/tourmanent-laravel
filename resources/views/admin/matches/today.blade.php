@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Lịch Thi Đấu Hôm Nay</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.matches.index') }}" class="btn btn-secondary">
                <i class="fas fa-list me-2"></i> Tất Cả Trận Đấu
            </a>
            <button class="btn btn-primary" onclick="loadTodayMatches()">
                <i class="fas fa-sync me-2"></i> Làm Mới
            </button>
        </div>
    </div>

    <!-- Today's Summary -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="card card-stats bg-primary text-white">
                <div class="card-body">
                    <div class="row">
                        <div class="col-5 col-md-4">
                            <div class="icon-big text-center">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                        </div>
                        <div class="col-7 col-md-8">
                            <div class="numbers">
                                <p class="card-category">Hôm Nay</p>
                                <p class="card-title" id="today-date">{{ now()->format('d/m/Y') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="card card-stats bg-info text-white">
                <div class="card-body">
                    <div class="row">
                        <div class="col-5 col-md-4">
                            <div class="icon-big text-center">
                                <i class="fas fa-volleyball-ball"></i>
                            </div>
                        </div>
                        <div class="col-7 col-md-8">
                            <div class="numbers">
                                <p class="card-category">Tổng Trận</p>
                                <p class="card-title" id="total-matches">0</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="card card-stats bg-success text-white">
                <div class="card-body">
                    <div class="row">
                        <div class="col-5 col-md-4">
                            <div class="icon-big text-center">
                                <i class="fas fa-play"></i>
                            </div>
                        </div>
                        <div class="col-7 col-md-8">
                            <div class="numbers">
                                <p class="card-category">Đang Thi Đấu</p>
                                <p class="card-title" id="playing-matches">0</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="card card-stats bg-warning text-dark">
                <div class="card-body">
                    <div class="row">
                        <div class="col-5 col-md-4">
                            <div class="icon-big text-center">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                        <div class="col-7 col-md-8">
                            <div class="numbers">
                                <p class="card-category">Chờ Thi Đấu</p>
                                <p class="card-title" id="scheduled-matches">0</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Courts Schedule -->
    <div class="row">
        <div class="col-lg-4 col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-map-marker-alt me-2"></i>Sân 1
                    </h5>
                </div>
                <div class="card-body" id="court-1-matches">
                    <div class="text-center text-muted">
                        <i class="fas fa-spinner fa-spin"></i> Đang tải...
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-map-marker-alt me-2"></i>Sân 2
                    </h5>
                </div>
                <div class="card-body" id="court-2-matches">
                    <div class="text-center text-muted">
                        <i class="fas fa-spinner fa-spin"></i> Đang tải...
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-map-marker-alt me-2"></i>Sân 3
                    </h5>
                </div>
                <div class="card-body" id="court-3-matches">
                    <div class="text-center text-muted">
                        <i class="fas fa-spinner fa-spin"></i> Đang tải...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- All Today's Matches -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Tất Cả Trận Đấu Hôm Nay</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="today-matches-table">
                    <thead>
                        <tr>
                            <th>Thời Gian</th>
                            <th>Sân</th>
                            <th>Vòng Đấu</th>
                            <th>Loại</th>
                            <th>Đội A</th>
                            <th>Đội B</th>
                            <th>Trạng Thái</th>
                            <th>Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="8" class="text-center">Đang tải dữ liệu...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    loadTodayMatches();
    
    // Auto refresh every 30 seconds
    setInterval(loadTodayMatches, 30000);
});

function loadTodayMatches() {
    showLoading('Đang tải lịch thi đấu hôm nay...');
    
    fetch('/api/admin/matches/today-schedule')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderTodaySchedule(data.data);
            } else {
                showToast('Có lỗi khi tải lịch thi đấu: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error loading today matches:', error);
            showToast('Có lỗi khi tải lịch thi đấu hôm nay', 'danger');
        })
        .finally(() => {
            hideLoading();
        });
}

function renderTodaySchedule(scheduleData) {
    // Update summary stats
    document.getElementById('total-matches').textContent = scheduleData.total_matches;
    
    const playingCount = scheduleData.matches.filter(m => m.status === 'playing').length;
    const scheduledCount = scheduleData.matches.filter(m => m.status === 'scheduled').length;
    
    document.getElementById('playing-matches').textContent = playingCount;
    document.getElementById('scheduled-matches').textContent = scheduledCount;
    
    // Group matches by court
    const matchesByCourt = {
        1: scheduleData.matches.filter(m => m.court_name === 'Sân 1'),
        2: scheduleData.matches.filter(m => m.court_name === 'Sân 2'),
        3: scheduleData.matches.filter(m => m.court_name === 'Sân 3')
    };
    
    // Render court schedules
    for (let courtNum = 1; courtNum <= 3; courtNum++) {
        const courtMatches = matchesByCourt[courtNum];
        const courtElement = document.getElementById(`court-${courtNum}-matches`);
        
        if (courtMatches.length === 0) {
            courtElement.innerHTML = `
                <div class="text-center text-muted">
                    <i class="fas fa-calendar-times"></i>
                    <p class="mt-2">Không có trận đấu</p>
                </div>
            `;
        } else {
            courtElement.innerHTML = courtMatches.map(match => `
                <div class="match-item mb-3 p-3 border rounded ${getMatchItemClass(match.status)}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-primary">${match.time_range || 'Chưa có giờ'}</span>
                                <span class="badge bg-${getStatusBadgeColor(match.status)}">${match.status_name}</span>
                            </div>
                            <h6 class="mb-2">${match.phase_name} - ${match.type_name}</h6>
                            <div class="vs-display">
                                <div class="team-name">${match.pair_a_name}</div>
                                <div class="vs-text">VS</div>
                                <div class="team-name">${match.pair_b_name}</div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2">
                        <button class="btn btn-sm btn-outline-primary" onclick="viewMatchDetails(${match.id})">
                            <i class="fas fa-eye"></i> Chi tiết
                        </button>
                        ${match.status === 'scheduled' ? `
                            <button class="btn btn-sm btn-outline-success" onclick="startMatch(${match.id})">
                                <i class="fas fa-play"></i> Bắt đầu
                            </button>
                        ` : ''}
                    </div>
                </div>
            `).join('');
        }
    }
    
    // Render all matches table
    renderTodayMatchesTable(scheduleData.matches);
}

function renderTodayMatchesTable(matches) {
    const tbody = document.querySelector('#today-matches-table tbody');
    
    if (matches.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">Không có trận đấu nào hôm nay.</td></tr>';
        return;
    }
    
    // Sort matches by time
    const sortedMatches = matches.sort((a, b) => {
        if (!a.time_range && !b.time_range) return 0;
        if (!a.time_range) return 1;
        if (!b.time_range) return -1;
        return a.time_range.localeCompare(b.time_range);
    });
    
    tbody.innerHTML = sortedMatches.map(match => `
        <tr class="${getMatchRowClass(match.status)}">
            <td>
                <strong>${match.time_range || 'Chưa có'}</strong>
            </td>
            <td>
                <span class="badge bg-secondary">${match.court_name || 'Chưa có'}</span>
            </td>
            <td>
                <span class="badge bg-primary">${match.phase_name}</span>
            </td>
            <td>
                <span class="badge bg-info">${match.type_name}</span>
            </td>
            <td>
                <strong>${match.pair_a_name}</strong>
            </td>
            <td>
                <strong>${match.pair_b_name}</strong>
            </td>
            <td>
                <span class="badge bg-${getStatusBadgeColor(match.status)}">${match.status_name}</span>
            </td>
            <td>
                <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-outline-primary" onclick="viewMatchDetails(${match.id})" title="Xem chi tiết">
                        <i class="fas fa-eye"></i>
                    </button>
                    ${match.status === 'scheduled' ? `
                        <button class="btn btn-outline-success" onclick="startMatch(${match.id})" title="Bắt đầu">
                            <i class="fas fa-play"></i>
                        </button>
                    ` : ''}
                    ${match.status === 'playing' ? `
                        <button class="btn btn-outline-warning" onclick="updateResult(${match.id})" title="Nhập kết quả">
                            <i class="fas fa-edit"></i>
                        </button>
                    ` : ''}
                </div>
            </td>
        </tr>
    `).join('');
}

function getMatchItemClass(status) {
    const classes = {
        'scheduled': 'border-primary',
        'playing': 'border-warning bg-warning bg-opacity-10',
        'finished': 'border-success bg-success bg-opacity-10',
        'canceled': 'border-danger bg-danger bg-opacity-10'
    };
    return classes[status] || '';
}

function getMatchRowClass(status) {
    const classes = {
        'playing': 'table-warning',
        'finished': 'table-success',
        'canceled': 'table-danger'
    };
    return classes[status] || '';
}

function getStatusBadgeColor(status) {
    const colors = {
        'scheduled': 'primary',
        'playing': 'warning',
        'finished': 'success',
        'canceled': 'danger'
    };
    return colors[status] || 'secondary';
}

function viewMatchDetails(matchId) {
    // Redirect to match details page or open modal
    window.open(`{{ route('admin.matches.index') }}#match-${matchId}`, '_blank');
}

function startMatch(matchId) {
    if (confirm('Bạn có chắc muốn bắt đầu trận đấu này?')) {
        showLoading('Đang bắt đầu trận đấu...');
        
        fetch(`/api/admin/matches/${matchId}/start`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Trận đấu đã bắt đầu!', 'success');
                loadTodayMatches(); // Refresh the schedule
            } else {
                showToast('Có lỗi: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error starting match:', error);
            showToast('Có lỗi khi bắt đầu trận đấu', 'danger');
        })
        .finally(() => {
            hideLoading();
        });
    }
}

function updateResult(matchId) {
    alert('Chức năng nhập kết quả sẽ được phát triển trong phiên bản tiếp theo.');
}
</script>

<style>
.match-item {
    transition: all 0.3s ease;
}

.match-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.vs-display {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 0.5rem 0;
}

.team-name {
    font-weight: bold;
    flex: 1;
    text-align: center;
}

.vs-text {
    margin: 0 1rem;
    font-weight: bold;
    color: #6c757d;
}

.card-stats .icon-big {
    font-size: 2.5rem;
    line-height: 1;
}

@media (max-width: 768px) {
    .vs-display {
        flex-direction: column;
        text-align: center;
    }
    
    .vs-text {
        margin: 0.25rem 0;
    }
    
    .team-name {
        font-size: 0.9rem;
    }
}
</style>
@endpush
