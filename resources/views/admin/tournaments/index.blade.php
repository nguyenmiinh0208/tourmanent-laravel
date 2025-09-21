@extends('layouts.admin')

@section('title', 'Quản Lý Vòng Đấu')
@section('page-title', 'Quản Lý Vòng Đấu')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Danh Sách Vòng Đấu</h5>
                <a href="{{ route('admin.tournaments.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Tạo Vòng Đấu Mới
                </a>
            </div>
            <div class="card-body">
                <!-- Filter Section -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <select class="form-select" id="statusFilter">
                            <option value="">Tất cả trạng thái</option>
                            <option value="draft">Draft</option>
                            <option value="scheduled">Scheduled</option>
                            <option value="playing">Playing</option>
                            <option value="completed">Completed</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" id="typeFilter">
                            <option value="">Tất cả loại vòng đấu</option>
                            <option value="vong_loai">Vòng Loại</option>
                            <option value="ban_ket">Bán Kết</option>
                            <option value="chung_ket">Chung Kết</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-outline-secondary" onclick="refreshTournaments()">
                            <i class="fas fa-sync-alt me-2"></i>Làm Mới
                        </button>
                    </div>
                </div>

                <!-- Tournaments Table -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tên Vòng Đấu</th>
                                <th>Loại</th>
                                <th>Trạng Thái</th>
                                <th>Thời Gian</th>
                                <th>Người Chơi/Cặp</th>
                                <th>Trận Đấu</th>
                                <th>Hành Động</th>
                            </tr>
                        </thead>
                        <tbody id="tournamentsTable">
                            <tr>
                                <td colspan="7" class="text-center">
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
</div>

<!-- Tournament Details Modal -->
<div class="modal fade" id="tournamentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tournamentModalTitle">Chi Tiết Vòng Đấu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="tournamentModalBody">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer" id="tournamentModalFooter">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Import Players Modal -->
<div class="modal fade" id="importPlayersModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Người Chơi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="importPlayersForm">
                    <div class="mb-3">
                        <label class="form-label">Danh sách người chơi (JSON format)</label>
                        <textarea class="form-control" id="playersData" rows="10" placeholder='[
  {"name": "Nguyễn Văn A", "gender": "M"},
  {"name": "Trần Thị B", "gender": "F"}
]'></textarea>
                        <div class="form-text">
                            Nhập danh sách người chơi theo format JSON. Mỗi người chơi cần có "name" và "gender" (M/F).
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="importPlayers()">
                    <i class="fas fa-upload me-2"></i>Import
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Generate Pairs Modal -->
<div class="modal fade" id="generatePairsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tạo Cặp Thi Đấu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="generatePairsForm">
                    <div class="mb-3">
                        <label class="form-label">Số trận đấu mỗi người chơi</label>
                        <input type="number" class="form-control" id="matchesPerPlayer" min="1" max="10" value="3">
                        <div class="form-text">
                            Mỗi người chơi sẽ tham gia bao nhiêu trận đấu trong vòng này.
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Hệ thống sẽ tự động tạo cặp theo quy tắc: ưu tiên XD (nam nữ), sau đó MD (đôi nam), cuối cùng WD (đôi nữ).
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-success" onclick="generatePairs()">
                    <i class="fas fa-random me-2"></i>Tạo Cặp
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let tournaments = [];
let currentTournamentId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadTournaments();
    
    // Setup filters
    document.getElementById('statusFilter').addEventListener('change', filterTournaments);
    document.getElementById('typeFilter').addEventListener('change', filterTournaments);
});

async function loadTournaments() {
    try {
        showLoading();
        const response = await fetch('/api/admin/tournaments');
        const data = await response.json();
        
        if (data.success) {
            tournaments = data.data;
            renderTournaments(tournaments);
        } else {
            showToast('Có lỗi khi tải danh sách vòng đấu', 'danger');
        }
    } catch (error) {
        console.error('Error loading tournaments:', error);
        showToast('Có lỗi khi tải danh sách vòng đấu', 'danger');
    } finally {
        hideLoading();
    }
}

function renderTournaments(tournamentsToRender) {
    const tbody = document.getElementById('tournamentsTable');
    
    if (tournamentsToRender.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Không có vòng đấu nào</td></tr>';
        return;
    }
    
    tbody.innerHTML = tournamentsToRender.map(tournament => `
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
                <small>
                    ${tournament.start_at ? new Date(tournament.start_at).toLocaleDateString('vi-VN') : 'Chưa định'}<br>
                    ${tournament.end_at ? new Date(tournament.end_at).toLocaleDateString('vi-VN') : 'Chưa định'}
                </small>
            </td>
            <td>
                <span class="text-primary">${tournament.total_pairs} cặp</span>
            </td>
            <td>
                <span class="text-success">${tournament.total_matches} trận</span>
            </td>
            <td>
                <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-outline-primary" onclick="viewTournament(${tournament.id})" title="Xem chi tiết">
                        <i class="fas fa-eye"></i>
                    </button>
                    ${tournament.status === 'draft' ? `
                        <button class="btn btn-outline-success" onclick="showImportModal(${tournament.id})" title="Import người chơi">
                            <i class="fas fa-users"></i>
                        </button>
                        ${tournament.can_generate_pairs ? `
                            <button class="btn btn-outline-warning" onclick="showGeneratePairsModal(${tournament.id})" title="Tạo cặp">
                                <i class="fas fa-random"></i>
                            </button>
                        ` : ''}
                        ${tournament.can_schedule_matches ? `
                            <button class="btn btn-outline-info" onclick="scheduleMatches(${tournament.id})" title="Lên lịch">
                                <i class="fas fa-calendar"></i>
                            </button>
                        ` : ''}
                    ` : ''}
                    <button class="btn btn-outline-danger" onclick="deleteTournament(${tournament.id})" title="Xóa">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function filterTournaments() {
    const statusFilter = document.getElementById('statusFilter').value;
    const typeFilter = document.getElementById('typeFilter').value;
    
    let filtered = tournaments;
    
    if (statusFilter) {
        filtered = filtered.filter(t => t.status === statusFilter);
    }
    
    if (typeFilter) {
        filtered = filtered.filter(t => t.type === typeFilter);
    }
    
    renderTournaments(filtered);
}

function refreshTournaments() {
    loadTournaments();
}

async function viewTournament(id) {
    try {
        currentTournamentId = id;
        const modal = new bootstrap.Modal(document.getElementById('tournamentModal'));
        
        // Show loading in modal
        document.getElementById('tournamentModalBody').innerHTML = `
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Đang tải thông tin...</p>
            </div>
        `;
        
        modal.show();
        
        const response = await fetch(`/api/admin/tournaments/${id}`);
        const data = await response.json();
        
        if (data.success) {
            renderTournamentDetails(data.data);
        } else {
            document.getElementById('tournamentModalBody').innerHTML = 
                '<div class="alert alert-danger">Có lỗi khi tải thông tin vòng đấu</div>';
        }
    } catch (error) {
        console.error('Error loading tournament details:', error);
        document.getElementById('tournamentModalBody').innerHTML = 
            '<div class="alert alert-danger">Có lỗi khi tải thông tin vòng đấu</div>';
    }
}

function renderTournamentDetails(data) {
    const tournament = data.phase;
    const stats = data;
    
    document.getElementById('tournamentModalTitle').textContent = tournament.name;
    document.getElementById('tournamentModalBody').innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6>Thông Tin Cơ Bản</h6>
                <table class="table table-sm">
                    <tr><td>Loại:</td><td><span class="badge badge-${tournament.type}">${tournament.display_name}</span></td></tr>
                    <tr><td>Trạng thái:</td><td><span class="badge badge-${tournament.status}">${tournament.status_name}</span></td></tr>
                    <tr><td>Số trận/người:</td><td>${tournament.matches_per_player}</td></tr>
                    <tr><td>Bắt đầu:</td><td>${tournament.start_at ? new Date(tournament.start_at).toLocaleString('vi-VN') : 'Chưa định'}</td></tr>
                    <tr><td>Kết thúc:</td><td>${tournament.end_at ? new Date(tournament.end_at).toLocaleString('vi-VN') : 'Chưa định'}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Thống Kê</h6>
                <table class="table table-sm">
                    <tr><td>Tổng người chơi:</td><td>${stats.total_players}</td></tr>
                    <tr><td>Nam:</td><td>${stats.players_by_gender.male}</td></tr>
                    <tr><td>Nữ:</td><td>${stats.players_by_gender.female}</td></tr>
                    <tr><td>Tổng cặp:</td><td>${stats.total_pairs}</td></tr>
                    <tr><td>XD:</td><td>${stats.pairs_by_type.XD}</td></tr>
                    <tr><td>MD:</td><td>${stats.pairs_by_type.MD}</td></tr>
                    <tr><td>WD:</td><td>${stats.pairs_by_type.WD}</td></tr>
                    <tr><td>Tổng trận:</td><td>${stats.total_matches}</td></tr>
                    <tr><td>Hoàn thành:</td><td>${stats.completion_percentage}%</td></tr>
                </table>
            </div>
        </div>
    `;
}

function showImportModal(tournamentId) {
    currentTournamentId = tournamentId;
    const modal = new bootstrap.Modal(document.getElementById('importPlayersModal'));
    modal.show();
}

function showGeneratePairsModal(tournamentId) {
    currentTournamentId = tournamentId;
    const modal = new bootstrap.Modal(document.getElementById('generatePairsModal'));
    modal.show();
}

async function importPlayers() {
    try {
        const playersDataText = document.getElementById('playersData').value.split('\n').map(line => line.trim());
        if (playersDataText.length === 0) {
            showToast('Vui lòng nhập danh sách người chơi', 'warning');
            return;
        }
        
        let playersData;
        try {
            playersData = playersDataText.reduce((acc, line) => {
                const [name, gender] = line.split(',');
                acc.push({ name: name.trim(), gender: gender.trim().toUpperCase() });
                return acc;
            }, []);
            
        } catch (e) {
            showToast('Dữ liệu không hợp lệ', 'danger');
            return;
        }
        
        showLoading();
        
        const response = await fetch(`/api/admin/tournaments/${currentTournamentId}/import-players`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.csrfToken
            },
            body: JSON.stringify({ players: playersData })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(`Import thành công ${data.data.total_imported} người chơi`, 'success');
            bootstrap.Modal.getInstance(document.getElementById('importPlayersModal')).hide();
            loadTournaments(); // Refresh data
        } else {
            showToast('Có lỗi khi import: ' + data.message, 'danger');
        }
        
    } catch (error) {
        console.error('Error importing players:', error);
        showToast('Có lỗi khi import người chơi', 'danger');
    } finally {
        hideLoading();
    }
}

async function generatePairs() {
    try {
        const matchesPerPlayer = parseInt(document.getElementById('matchesPerPlayer').value);
        
        if (matchesPerPlayer < 1 || matchesPerPlayer > 10) {
            showToast('Số trận đấu mỗi người phải từ 1 đến 10', 'warning');
            return;
        }
        
        showLoading();
        
        const response = await fetch(`/api/admin/tournaments/${currentTournamentId}/generate-pairs`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.csrfToken
            },
            body: JSON.stringify({ matches_per_player: matchesPerPlayer })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(`Tạo thành công ${data.data.stats.total_pairs} cặp thi đấu`, 'success');
            bootstrap.Modal.getInstance(document.getElementById('generatePairsModal')).hide();
            loadTournaments(); // Refresh data
        } else {
            showToast('Có lỗi khi tạo cặp: ' + data.message, 'danger');
        }
        
    } catch (error) {
        console.error('Error generating pairs:', error);
        showToast('Có lỗi khi tạo cặp thi đấu', 'danger');
    } finally {
        hideLoading();
    }
}

async function scheduleMatches(tournamentId) {
    if (!confirm('Bạn có chắc chắn muốn lên lịch thi đấu cho vòng này?')) {
        return;
    }
    
    try {
        showLoading();
        
        const response = await fetch(`/api/admin/tournaments/${tournamentId}/schedule-matches`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.csrfToken
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(`Lên lịch thành công ${data.data.total_matches} trận đấu`, 'success');
            loadTournaments(); // Refresh data
        } else {
            showToast('Có lỗi khi lên lịch: ' + data.message, 'danger');
        }
        
    } catch (error) {
        console.error('Error scheduling matches:', error);
        showToast('Có lỗi khi lên lịch thi đấu', 'danger');
    } finally {
        hideLoading();
    }
}

async function deleteTournament(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa vòng đấu này? Hành động này không thể hoàn tác.')) {
        return;
    }
    
    try {
        showLoading();
        
        const response = await fetch(`/api/admin/tournaments/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': window.csrfToken
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Xóa vòng đấu thành công', 'success');
            loadTournaments(); // Refresh data
        } else {
            showToast('Có lỗi khi xóa: ' + data.message, 'danger');
        }
        
    } catch (error) {
        console.error('Error deleting tournament:', error);
        showToast('Có lỗi khi xóa vòng đấu', 'danger');
    } finally {
        hideLoading();
    }
}
</script>
@endpush
