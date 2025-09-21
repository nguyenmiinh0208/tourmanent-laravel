@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Quản Lý Trận Đấu</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.matches.today') }}" class="btn btn-info">
                <i class="fas fa-calendar-day me-2"></i> Lịch Hôm Nay
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Bộ Lọc</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <label for="phaseFilter" class="form-label">Vòng Đấu:</label>
                    <select class="form-select" id="phaseFilter">
                        <option value="">Tất cả</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="statusFilter" class="form-label">Trạng Thái:</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">Tất cả</option>
                        <option value="scheduled">Đã Lên Lịch</option>
                        <option value="playing">Đang Thi Đấu</option>
                        <option value="finished">Đã Kết Thúc</option>
                        <option value="canceled">Đã Hủy</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="courtFilter" class="form-label">Sân:</label>
                    <select class="form-select" id="courtFilter">
                        <option value="">Tất cả</option>
                        <option value="1">Sân 1</option>
                        <option value="2">Sân 2</option>
                        <option value="3">Sân 3</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="dateFilter" class="form-label">Ngày:</label>
                    <input type="date" class="form-control" id="dateFilter">
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <button class="btn btn-primary" onclick="loadMatches()">
                        <i class="fas fa-search me-2"></i> Lọc
                    </button>
                    <button class="btn btn-secondary" onclick="clearFilters()">
                        <i class="fas fa-times me-2"></i> Xóa Bộ Lọc
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Matches Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Danh Sách Trận Đấu</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="matches-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Vòng Đấu</th>
                            <th>Loại</th>
                            <th>Đội A</th>
                            <th>Đội B</th>
                            <th>Tỷ Số</th>
                            <th>Sân</th>
                            <th>Thời Gian</th>
                            <th>Trạng Thái</th>
                            <th>Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="10" class="text-center">Đang tải dữ liệu...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <nav aria-label="Matches pagination" id="matches-pagination" class="mt-3" style="display: none;">
                <ul class="pagination justify-content-center">
                    <!-- Pagination will be dynamically generated -->
                </ul>
            </nav>
        </div>
    </div>

    <!-- Match Details Modal -->
    <div class="modal fade" id="matchDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chi Tiết Trận Đấu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="matchDetailsContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
let currentPage = 1;
let currentFilters = {};

document.addEventListener('DOMContentLoaded', function() {
    loadMatches();
    loadPhases();
});

function loadPhases() {
    fetch('/api/admin/tournaments')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const phaseSelect = document.getElementById('phaseFilter');
                phaseSelect.innerHTML = '<option value="">Tất cả</option>';
                
                data.data.forEach(phase => {
                    const option = document.createElement('option');
                    option.value = phase.id;
                    option.textContent = phase.display_name + ' - ' + phase.name;
                    phaseSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading phases:', error);
        });
}

function loadMatches(page = 1) {
    const filters = getFilters();
    const params = new URLSearchParams(filters);
    params.append('page', page);

    showLoading('Đang tải danh sách trận đấu...');

    fetch(`/api/admin/matches?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderMatchesTable(data.data.data);
                renderPagination(data.data);
            } else {
                showToast('Có lỗi khi tải danh sách trận đấu: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error loading matches:', error);
            showToast('Có lỗi khi tải danh sách trận đấu', 'danger');
        })
        .finally(() => {
            hideLoading();
        });
}

function getFilters() {
    return {
        phase_id: document.getElementById('phaseFilter').value,
        status: document.getElementById('statusFilter').value,
        court_id: document.getElementById('courtFilter').value,
        date: document.getElementById('dateFilter').value,
    };
}

function clearFilters() {
    document.getElementById('phaseFilter').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('courtFilter').value = '';
    document.getElementById('dateFilter').value = '';
    currentPage = 1;
    loadMatches();
}

function renderMatchesTable(matches) {
    const tbody = document.querySelector('#matches-table tbody');
    
    if (matches.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="text-center">Không có trận đấu nào.</td></tr>';
        return;
    }

    tbody.innerHTML = matches.map(match => `
        <tr>
            <td>${match.id}</td>
            <td>
                <span class="badge bg-primary">${match.phase.display_name}</span>
                <br>
                <small class="text-muted">${match.phase.name}</small>
            </td>
            <td>
                <span class="badge bg-info">${match.type_name}</span>
            </td>
            <td>
                <strong>${match.pair_a.name}</strong>
                <br>
                <small class="text-muted">
                    ${match.pair_a.players.map(p => p.name).join(' & ')}
                </small>
            </td>
            <td>
                <strong>${match.pair_b.name}</strong>
                <br>
                <small class="text-muted">
                    ${match.pair_b.players.map(p => p.name).join(' & ')}
                </small>
            </td>
            <td>
                ${match.score_display || '<span class="text-muted">Chưa có</span>'}
                ${match.winner_name ? `<br><small class="text-success">Thắng: ${match.winner_name}</small>` : ''}
            </td>
            <td>
                ${match.court ? `<span class="badge bg-secondary">${match.court.name}</span>` : '<span class="text-muted">Chưa có</span>'}
            </td>
            <td>
                ${match.time_slot ? `
                    <strong>${match.time_slot.time_range}</strong>
                    <br>
                    <small class="text-muted">${new Date(match.time_slot.start_at).toLocaleDateString('vi-VN')}</small>
                ` : '<span class="text-muted">Chưa có</span>'}
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
                    ${['scheduled', 'playing'].includes(match.status) ? `
                        <button class="btn btn-outline-danger" onclick="cancelMatch(${match.id})" title="Hủy">
                            <i class="fas fa-times"></i>
                        </button>
                    ` : ''}
                </div>
            </td>
        </tr>
    `).join('');
}

function renderPagination(paginationData) {
    const pagination = document.getElementById('matches-pagination');
    const paginationList = pagination.querySelector('.pagination');
    
    if (paginationData.last_page <= 1) {
        pagination.style.display = 'none';
        return;
    }
    
    pagination.style.display = 'block';
    
    let paginationHTML = '';
    
    // Previous button
    if (paginationData.current_page > 1) {
        paginationHTML += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="loadMatches(${paginationData.current_page - 1}); return false;">Trước</a>
            </li>
        `;
    }
    
    // Page numbers
    for (let i = 1; i <= paginationData.last_page; i++) {
        if (i === paginationData.current_page) {
            paginationHTML += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
        } else {
            paginationHTML += `
                <li class="page-item">
                    <a class="page-link" href="#" onclick="loadMatches(${i}); return false;">${i}</a>
                </li>
            `;
        }
    }
    
    // Next button
    if (paginationData.current_page < paginationData.last_page) {
        paginationHTML += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="loadMatches(${paginationData.current_page + 1}); return false;">Sau</a>
            </li>
        `;
    }
    
    paginationList.innerHTML = paginationHTML;
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
    const modal = new bootstrap.Modal(document.getElementById('matchDetailsModal'));
    const content = document.getElementById('matchDetailsContent');
    
    content.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    fetch(`/api/admin/matches/${matchId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderMatchDetails(data.data);
            } else {
                content.innerHTML = `<div class="alert alert-danger">Có lỗi khi tải chi tiết trận đấu: ${data.message}</div>`;
            }
        })
        .catch(error => {
            console.error('Error loading match details:', error);
            content.innerHTML = '<div class="alert alert-danger">Có lỗi khi tải chi tiết trận đấu</div>';
        });
}

function renderMatchDetails(match) {
    const content = document.getElementById('matchDetailsContent');
    
    content.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6>Thông Tin Trận Đấu</h6>
                <table class="table table-sm">
                    <tr>
                        <td><strong>ID:</strong></td>
                        <td>${match.id}</td>
                    </tr>
                    <tr>
                        <td><strong>Vòng đấu:</strong></td>
                        <td>${match.phase.display_name} - ${match.phase.name}</td>
                    </tr>
                    <tr>
                        <td><strong>Loại:</strong></td>
                        <td><span class="badge bg-info">${match.type_name}</span></td>
                    </tr>
                    <tr>
                        <td><strong>Trạng thái:</strong></td>
                        <td><span class="badge bg-${getStatusBadgeColor(match.status)}">${match.status_name}</span></td>
                    </tr>
                    <tr>
                        <td><strong>Sân:</strong></td>
                        <td>${match.court ? match.court.name : 'Chưa có'}</td>
                    </tr>
                    <tr>
                        <td><strong>Thời gian:</strong></td>
                        <td>${match.time_slot ? match.time_slot.date_time_range : 'Chưa có'}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Kết Quả</h6>
                <div class="text-center mb-3">
                    <div class="row">
                        <div class="col-5">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Đội A</h6>
                                    <p class="card-text">
                                        <strong>${match.pair_a.name}</strong><br>
                                        <small>${match.pair_a.players.map(p => `${p.name} (${p.gender})`).join('<br>')}</small>
                                    </p>
                                    <h4 class="text-primary">${match.scores.team_a || 0}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-2 d-flex align-items-center justify-content-center">
                            <h3>VS</h3>
                        </div>
                        <div class="col-5">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h6 class="card-title">Đội B</h6>
                                    <p class="card-text">
                                        <strong>${match.pair_b.name}</strong><br>
                                        <small>${match.pair_b.players.map(p => `${p.name} (${p.gender})`).join('<br>')}</small>
                                    </p>
                                    <h4 class="text-primary">${match.scores.team_b || 0}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    ${match.winner_name ? `
                        <div class="alert alert-success mt-3">
                            <i class="fas fa-trophy me-2"></i>
                            <strong>Người thắng: ${match.winner_name}</strong>
                        </div>
                    ` : ''}
                </div>
            </div>
        </div>
        
        ${match.participants.length > 0 ? `
            <hr>
            <h6>Chi Tiết Người Chơi</h6>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Tên</th>
                            <th>Giới Tính</th>
                            <th>Đội</th>
                            <th>Kết Quả</th>
                            <th>Điểm</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${match.participants.map(p => `
                            <tr>
                                <td>${p.user.name}</td>
                                <td>${p.user.gender}</td>
                                <td><span class="badge bg-${p.team_side === 'A' ? 'primary' : 'secondary'}">${p.team_side_name}</span></td>
                                <td>${p.result_name ? `<span class="badge bg-${p.result === 'win' ? 'success' : p.result === 'lose' ? 'danger' : 'warning'}">${p.result_name}</span>` : 'Chưa có'}</td>
                                <td>${p.points}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        ` : ''}
    `;
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
                loadMatches(currentPage);
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

function cancelMatch(matchId) {
    if (confirm('Bạn có chắc muốn hủy trận đấu này?')) {
        showLoading('Đang hủy trận đấu...');
        
        fetch(`/api/admin/matches/${matchId}/cancel`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Trận đấu đã được hủy!', 'success');
                loadMatches(currentPage);
            } else {
                showToast('Có lỗi: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error canceling match:', error);
            showToast('Có lỗi khi hủy trận đấu', 'danger');
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
@endpush
