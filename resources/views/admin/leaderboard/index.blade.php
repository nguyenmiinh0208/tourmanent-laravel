@extends('layouts.admin')

@section('title', 'Bảng Xếp Hạng')
@section('page-title', 'Bảng Xếp Hạng')

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Filters -->
        <div class="leaderboard-filters">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <label for="phaseFilter" class="form-label">Vòng Đấu:</label>
                    <select class="form-select" id="phaseFilter">
                        <option value="">Tất cả vòng đấu</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="genderFilter" class="form-label">Giới Tính:</label>
                    <div class="btn-group" role="group" id="genderFilter">
                        <input type="radio" class="btn-check" name="gender" id="all" value="" checked>
                        <label class="btn btn-outline-primary" for="all">Tất Cả</label>

                        <input type="radio" class="btn-check" name="gender" id="male" value="M">
                        <label class="btn btn-outline-primary" for="male">Nam</label>

                        <input type="radio" class="btn-check" name="gender" id="female" value="F">
                        <label class="btn btn-outline-primary" for="female">Nữ</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label for="searchInput" class="form-label">Tìm Kiếm:</label>
                    <input type="text" class="form-control" id="searchInput" placeholder="Tìm kiếm theo tên...">
                </div>
                <div class="col-md-3 text-end">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button class="btn btn-success" onclick="exportLeaderboard()">
                            <i class="fas fa-download me-2"></i>Export
                        </button>
                        <button class="btn btn-outline-secondary" onclick="refreshLeaderboard()">
                            <i class="fas fa-sync-alt me-2"></i>Làm Mới
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card bg-gradient-primary">
                    <div class="card-body text-center">
                        <h3 id="totalPlayers">0</h3>
                        <p>Tổng Người Chơi</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-gradient-success">
                    <div class="card-body text-center">
                        <h3 id="totalMatches">0</h3>
                        <p>Tổng Trận Đấu</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-gradient-warning">
                    <div class="card-body text-center">
                        <h3 id="avgPoints">0</h3>
                        <p>Điểm Trung Bình</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-gradient-info">
                    <div class="card-body text-center">
                        <h3 id="topScore">0</h3>
                        <p>Điểm Cao Nhất</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leaderboard Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Bảng Xếp Hạng</h5>
                    <small class="text-muted" id="filterInfo">Tất cả người chơi</small>
                </div>
                <div class="d-flex align-items-center">
                    <small class="text-muted me-3" id="lastUpdated">Cập nhật: --</small>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="autoRefresh" checked>
                        <label class="form-check-label" for="autoRefresh">
                            Tự động làm mới
                        </label>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th width="80" class="text-center">Hạng</th>
                                <th>Họ Tên</th>
                                <th width="100" class="text-center">Giới Tính</th>
                                <th width="120" class="text-center">Số Trận</th>
                                <th width="120" class="text-center">Thắng</th>
                                <th width="120" class="text-center">Thua</th>
                                <th width="120" class="text-center">Tỷ Lệ Thắng</th>
                                <th width="120" class="text-center">Điểm</th>
                                <th width="100" class="text-center">Hành Động</th>
                            </tr>
                        </thead>
                        <tbody id="leaderboardTable">
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <div class="spinner-border spinner-border-sm me-2" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    Đang tải bảng xếp hạng...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div>
                <small class="text-muted" id="paginationInfo">Hiển thị 0 - 0 của 0 kết quả</small>
            </div>
            <nav>
                <ul class="pagination pagination-sm mb-0" id="pagination">
                    <!-- Pagination will be generated here -->
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Player Detail Modal -->
<div class="modal fade" id="playerDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="playerModalTitle">Chi Tiết Người Chơi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="playerModalBody">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.rank-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    font-weight: bold;
    font-size: 14px;
    color: white;
}

.rank-badge.rank-1 {
    background: linear-gradient(135deg, #FFD700, #FFA500);
    box-shadow: 0 2px 4px rgba(255, 215, 0, 0.3);
}

.rank-badge.rank-2 {
    background: linear-gradient(135deg, #C0C0C0, #A0A0A0);
    box-shadow: 0 2px 4px rgba(192, 192, 192, 0.3);
}

.rank-badge.rank-3 {
    background: linear-gradient(135deg, #CD7F32, #B8860B);
    box-shadow: 0 2px 4px rgba(205, 127, 50, 0.3);
}

.rank-badge.rank-other {
    background: linear-gradient(135deg, #6c757d, #495057);
    box-shadow: 0 2px 4px rgba(108, 117, 125, 0.3);
}

.stats-card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.stats-card:hover {
    transform: translateY(-2px);
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #007bff, #0056b3);
}

.bg-gradient-success {
    background: linear-gradient(135deg, #28a745, #1e7e34);
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #ffc107, #e0a800);
}

.bg-gradient-info {
    background: linear-gradient(135deg, #17a2b8, #138496);
}

.leaderboard-filters {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.table th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    color: #495057;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

.btn-check:checked + .btn-outline-primary {
    background-color: #007bff;
    border-color: #007bff;
    color: white;
}
</style>
@endpush

@push('scripts')
<script>
let leaderboardData = [];
let filteredData = [];
let currentPage = 1;
let itemsPerPage = 20;
let autoRefreshInterval = null;

document.addEventListener('DOMContentLoaded', function() {
    loadPhases();
    loadLeaderboard();
    setupEventListeners();
    setupAutoRefresh();
});

function setupEventListeners() {
    // Phase filter
    document.getElementById('phaseFilter').addEventListener('change', loadLeaderboard);
    
    // Gender filter
    document.querySelectorAll('input[name="gender"]').forEach(radio => {
        radio.addEventListener('change', loadLeaderboard);
    });
    
    // Search input
    document.getElementById('searchInput').addEventListener('input', 
        debounce(filterLeaderboard, 300)
    );
    
    // Auto refresh toggle
    document.getElementById('autoRefresh').addEventListener('change', function() {
        if (this.checked) {
            setupAutoRefresh();
        } else {
            clearAutoRefresh();
        }
    });
}

function setupAutoRefresh() {
    clearAutoRefresh();
    if (document.getElementById('autoRefresh').checked) {
        autoRefreshInterval = setInterval(loadLeaderboard, 30000); // 30 seconds
    }
}

function clearAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
    }
}

async function loadPhases() {
    try {
        const response = await fetch('/api/admin/tournaments');
        const data = await response.json();
        
        if (data.success) {
            const phaseSelect = document.getElementById('phaseFilter');
            phaseSelect.innerHTML = '<option value="">Tất cả vòng đấu</option>';
            
            data.data.forEach(phase => {
                const option = document.createElement('option');
                option.value = phase.id;
                option.textContent = phase.display_name + ' - ' + phase.name;
                phaseSelect.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading phases:', error);
    }
}

async function loadLeaderboard() {
    try {
        const phaseId = document.getElementById('phaseFilter').value;
        const gender = document.querySelector('input[name="gender"]:checked').value;
        
        let url = '/api/admin/leaderboard?';
        const params = new URLSearchParams();
        if (phaseId) params.append('phase_id', phaseId);
        if (gender) params.append('gender', gender);
        url += params.toString();
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            leaderboardData = data.data.leaderboard;
            updateStatistics(data.data);
            filterLeaderboard();
            updateLastUpdated();
            updateFilterInfo(data.data.filter);
        } else {
            showToast('Có lỗi khi tải bảng xếp hạng: ' + data.message, 'danger');
        }
    } catch (error) {
        console.error('Error loading leaderboard:', error);
        showToast('Có lỗi khi tải bảng xếp hạng', 'danger');
        
        // Show error state
        document.getElementById('leaderboardTable').innerHTML = `
            <tr>
                <td colspan="9" class="text-center py-4 text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Có lỗi khi tải dữ liệu. 
                    <button class="btn btn-sm btn-outline-primary ms-2" onclick="loadLeaderboard()">
                        Thử lại
                    </button>
                </td>
            </tr>
        `;
    }
}

function updateStatistics(data) {
    document.getElementById('totalPlayers').textContent = data.total_players;
    document.getElementById('totalMatches').textContent = data.stats.total_matches;
    document.getElementById('avgPoints').textContent = data.stats.average_points_per_player.toFixed(1);
    
    // Find top score from leaderboard
    const topScore = data.leaderboard.length > 0 ? data.leaderboard[0].total_points : 0;
    document.getElementById('topScore').textContent = topScore;
}

function filterLeaderboard() {
    const genderFilter = document.querySelector('input[name="gender"]:checked').value;
    const searchQuery = document.getElementById('searchInput').value.toLowerCase();
    
    filteredData = leaderboardData.filter(player => {
        const matchesGender = !genderFilter || player.gender === genderFilter;
        const matchesSearch = !searchQuery || player.name.toLowerCase().includes(searchQuery);
        return matchesGender && matchesSearch;
    });
    
    currentPage = 1;
    renderLeaderboard();
}

function renderLeaderboard() {
    const tbody = document.getElementById('leaderboardTable');
    
    if (filteredData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center py-4 text-muted">
                    <i class="fas fa-search me-2"></i>
                    Không tìm thấy kết quả nào
                </td>
            </tr>
        `;
        updatePaginationInfo(0, 0, 0);
        return;
    }
    
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, filteredData.length);
    const pageData = filteredData.slice(startIndex, endIndex);
    
    tbody.innerHTML = pageData.map(player => `
        <tr>
            <td class="text-center">
                <div class="rank-badge rank-${player.rank <= 3 ? player.rank : 'other'}">
                    ${player.rank}
                </div>
            </td>
            <td>
                <strong>${player.name}</strong>
            </td>
            <td class="text-center">
                <span class="badge ${player.gender === 'M' ? 'bg-primary' : 'bg-danger'}">
                    ${player.gender_name}
                </span>
            </td>
            <td class="text-center">${player.total_matches}</td>
            <td class="text-center text-success">${player.wins}</td>
            <td class="text-center text-danger">${player.losses}</td>
            <td class="text-center">
                <span class="badge ${player.win_percentage >= 60 ? 'bg-success' : player.win_percentage >= 40 ? 'bg-warning' : 'bg-danger'}">
                    ${player.win_percentage}%
                </span>
            </td>
            <td class="text-center">
                <strong class="text-primary">${player.total_points}</strong>
            </td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-info" onclick="viewPlayerDetails(${player.user_id})" title="Xem chi tiết">
                    <i class="fas fa-eye"></i>
                </button>
            </td>
        </tr>
    `).join('');
    
    updatePaginationInfo(startIndex + 1, endIndex, filteredData.length);
    renderPagination();
}

function updatePaginationInfo(start, end, total) {
    document.getElementById('paginationInfo').textContent = 
        `Hiển thị ${start} - ${end} của ${total} kết quả`;
}

function renderPagination() {
    const totalPages = Math.ceil(filteredData.length / itemsPerPage);
    const pagination = document.getElementById('pagination');
    
    if (totalPages <= 1) {
        pagination.innerHTML = '';
        return;
    }
    
    let paginationHtml = '';
    
    // Previous button
    paginationHtml += `
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="changePage(${currentPage - 1})">Trước</a>
        </li>
    `;
    
    // Page numbers
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    if (startPage > 1) {
        paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(1)">1</a></li>`;
        if (startPage > 2) {
            paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        paginationHtml += `
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
            </li>
        `;
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
        paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(${totalPages})">${totalPages}</a></li>`;
    }
    
    // Next button
    paginationHtml += `
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="changePage(${currentPage + 1})">Sau</a>
        </li>
    `;
    
    pagination.innerHTML = paginationHtml;
}

function changePage(page) {
    const totalPages = Math.ceil(filteredData.length / itemsPerPage);
    if (page < 1 || page > totalPages) return;
    
    currentPage = page;
    renderLeaderboard();
}

async function viewPlayerDetails(userId) {
    try {
        const modal = new bootstrap.Modal(document.getElementById('playerDetailModal'));
        
        // Show loading
        document.getElementById('playerModalBody').innerHTML = `
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Đang tải thông tin...</p>
            </div>
        `;
        
        modal.show();
        
        // Find player in current data
        const player = leaderboardData.find(p => p.user_id === userId);
        if (player) {
            document.getElementById('playerModalTitle').textContent = `Chi Tiết - ${player.name}`;
            renderPlayerDetails(player);
        }
        
    } catch (error) {
        console.error('Error loading player details:', error);
        document.getElementById('playerModalBody').innerHTML = 
            '<div class="alert alert-danger">Có lỗi khi tải thông tin người chơi</div>';
    }
}

function renderPlayerDetails(player) {
    const modalBody = document.getElementById('playerModalBody');
    
    modalBody.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6>Thông Tin Cơ Bản</h6>
                <table class="table table-sm">
                    <tr><td><strong>Họ tên:</strong></td><td>${player.name}</td></tr>
                    <tr><td><strong>Giới tính:</strong></td><td><span class="badge ${player.gender === 'M' ? 'bg-primary' : 'bg-danger'}">${player.gender_name}</span></td></tr>
                    <tr><td><strong>Xếp hạng:</strong></td><td><div class="rank-badge rank-${player.rank <= 3 ? player.rank : 'other'}">${player.rank}</div></td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Thống Kê Thi Đấu</h6>
                <table class="table table-sm">
                    <tr><td><strong>Tổng trận:</strong></td><td>${player.total_matches}</td></tr>
                    <tr><td><strong>Thắng:</strong></td><td class="text-success">${player.wins}</td></tr>
                    <tr><td><strong>Thua:</strong></td><td class="text-danger">${player.losses}</td></tr>
                    <tr><td><strong>Tỷ lệ thắng:</strong></td><td><span class="badge ${player.win_percentage >= 60 ? 'bg-success' : player.win_percentage >= 40 ? 'bg-warning' : 'bg-danger'}">${player.win_percentage}%</span></td></tr>
                    <tr><td><strong>Tổng điểm:</strong></td><td><strong class="text-primary">${player.total_points}</strong></td></tr>
                </table>
            </div>
        </div>
        
        <div class="alert alert-info mt-3">
            <h6><i class="fas fa-chart-line me-2"></i>Phân Tích</h6>
            <ul class="mb-0">
                <li>Điểm trung bình mỗi trận: <strong>${(player.total_points / Math.max(player.total_matches, 1)).toFixed(2)}</strong></li>
                <li>Form hiện tại: ${player.win_percentage >= 60 ? '<span class="text-success">Tốt</span>' : player.win_percentage >= 40 ? '<span class="text-warning">Trung bình</span>' : '<span class="text-danger">Cần cải thiện</span>'}</li>
                <li>Cần ${Math.max(0, player.rank > 1 ? 1 : 0)} điểm để lên hạng</li>
            </ul>
        </div>
    `;
}

function refreshLeaderboard() {
    loadLeaderboard();
    showToast('Đã làm mới bảng xếp hạng', 'success');
}

function updateLastUpdated() {
    document.getElementById('lastUpdated').textContent = 
        'Cập nhật: ' + new Date().toLocaleTimeString('vi-VN');
}

function updateFilterInfo(filter) {
    let filterText = '';
    
    if (filter.phase) {
        filterText += filter.phase.display_name + ' - ' + filter.phase.name;
    } else {
        filterText += 'Tất cả vòng đấu';
    }
    
    filterText += ' | ';
    
    if (filter.gender) {
        filterText += filter.gender_name;
    } else {
        filterText += 'Tất cả giới tính';
    }
    
    document.getElementById('filterInfo').textContent = filterText;
}

async function exportLeaderboard() {
    try {
        showLoading();
        
        // Create CSV content
        const headers = ['Hạng', 'Họ Tên', 'Giới Tính', 'Số Trận', 'Thắng', 'Thua', 'Tỷ Lệ Thắng (%)', 'Điểm'];
        const csvContent = [
            headers.join(','),
            ...filteredData.map(player => [
                player.rank,
                `"${player.name}"`,
                player.gender_name,
                player.total_matches,
                player.wins,
                player.losses,
                player.win_percentage,
                player.total_points
            ].join(','))
        ].join('\n');
        
        // Download file
        const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `leaderboard_${new Date().toISOString().split('T')[0]}.csv`;
        link.click();
        
        showToast('Đã export bảng xếp hạng thành công', 'success');
        
    } catch (error) {
        console.error('Error exporting leaderboard:', error);
        showToast('Có lỗi khi export bảng xếp hạng', 'danger');
    } finally {
        hideLoading();
    }
}

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    clearAutoRefresh();
});
</script>
@endpush
