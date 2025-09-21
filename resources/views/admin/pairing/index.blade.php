@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Tạo Cặp & Lịch Thi Đấu</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.pairing.generate') }}" class="btn btn-primary">
                <i class="fas fa-random me-2"></i> Tạo Cặp Đôi
            </a>
            <a href="{{ route('admin.pairing.schedule') }}" class="btn btn-success">
                <i class="fas fa-calendar-alt me-2"></i> Lên Lịch Thi Đấu
            </a>
        </div>
    </div>

    <!-- Overview Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="card card-stats bg-primary text-white">
                <div class="card-body">
                    <div class="row">
                        <div class="col-5 col-md-4">
                            <div class="icon-big text-center">
                                <i class="fas fa-trophy"></i>
                            </div>
                        </div>
                        <div class="col-7 col-md-8">
                            <div class="numbers">
                                <p class="card-category">Vòng Đấu</p>
                                <p class="card-title" id="total-phases">0</p>
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
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="col-7 col-md-8">
                            <div class="numbers">
                                <p class="card-category">Người Chơi</p>
                                <p class="card-title" id="total-players">0</p>
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
                                <i class="fas fa-handshake"></i>
                            </div>
                        </div>
                        <div class="col-7 col-md-8">
                            <div class="numbers">
                                <p class="card-category">Cặp Đôi</p>
                                <p class="card-title" id="total-pairs">0</p>
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
                                <i class="fas fa-calendar-check"></i>
                            </div>
                        </div>
                        <div class="col-7 col-md-8">
                            <div class="numbers">
                                <p class="card-category">Trận Đấu</p>
                                <p class="card-title" id="total-matches">0</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Phases Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Danh Sách Vòng Đấu</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="phases-table">
                    <thead>
                        <tr>
                            <th>Vòng Đấu</th>
                            <th>Loại</th>
                            <th>Số Người Chơi</th>
                            <th>Trận/Người</th>
                            <th>Trạng Thái</th>
                            <th>Cặp Đôi</th>
                            <th>Lịch Thi Đấu</th>
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

    <!-- Quick Actions -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-random me-2"></i>Tạo Cặp Đôi Nhanh
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Tạo cặp đôi cho vòng đấu đã có người chơi</p>
                    <div class="d-grid">
                        <a href="{{ route('admin.pairing.generate') }}" class="btn btn-primary">
                            <i class="fas fa-random me-2"></i>Bắt Đầu Tạo Cặp
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>Lên Lịch Thi Đấu
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Lên lịch thi đấu cho các cặp đôi đã tạo</p>
                    <div class="d-grid">
                        <a href="{{ route('admin.pairing.schedule') }}" class="btn btn-success">
                            <i class="fas fa-calendar-alt me-2"></i>Bắt Đầu Lên Lịch
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
let phasesData = [];

document.addEventListener('DOMContentLoaded', function() {
    loadPhases();
});

async function loadPhases() {
    try {
        const response = await fetch('/api/admin/pairing/phases');
        const data = await response.json();
        
        if (data.success) {
            phasesData = data.data;
            renderPhasesTable(data.data);
            updateOverviewStats(data.data);
        } else {
            showToast('Có lỗi khi tải danh sách vòng đấu: ' + data.message, 'danger');
        }
    } catch (error) {
        console.error('Error loading phases:', error);
        showToast('Có lỗi khi tải danh sách vòng đấu', 'danger');
    }
}

function renderPhasesTable(phases) {
    const tbody = document.querySelector('#phases-table tbody');
    
    if (phases.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-4 text-muted">
                    <i class="fas fa-info-circle me-2"></i>
                    Chưa có vòng đấu nào. 
                    <a href="{{ route('admin.tournaments.create') }}" class="btn btn-sm btn-outline-primary ms-2">
                        Tạo vòng đấu mới
                    </a>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = phases.map(phase => `
        <tr>
            <td>
                <div>
                    <strong>${phase.display_name}</strong>
                    <br>
                    <small class="text-muted">${phase.name}</small>
                </div>
            </td>
            <td>
                <span class="badge bg-info">${getPhaseTypeName(phase.type)}</span>
            </td>
            <td class="text-center">
                <span class="badge ${phase.players_count >= 4 ? 'bg-success' : 'bg-warning'}">
                    ${phase.players_count} người
                </span>
            </td>
            <td class="text-center">${phase.matches_per_player}</td>
            <td>
                <span class="badge bg-${getStatusBadgeColor(phase.status)}">${getStatusName(phase.status)}</span>
            </td>
            <td class="text-center">
                ${phase.can_generate_pairs ? 
                    '<span class="badge bg-success">Có thể tạo</span>' : 
                    '<span class="badge bg-secondary">Chưa đủ người</span>'
                }
            </td>
            <td class="text-center">
                ${phase.can_schedule ? 
                    '<span class="badge bg-success">Có thể lên lịch</span>' : 
                    '<span class="badge bg-secondary">Chưa có cặp</span>'
                }
            </td>
            <td>
                <div class="btn-group btn-group-sm" role="group">
                    ${phase.can_generate_pairs ? `
                        <button class="btn btn-outline-primary" onclick="generatePairs(${phase.id})" title="Tạo cặp đôi">
                            <i class="fas fa-random"></i>
                        </button>
                    ` : ''}
                    ${phase.can_schedule ? `
                        <button class="btn btn-outline-success" onclick="scheduleMatches(${phase.id})" title="Lên lịch thi đấu">
                            <i class="fas fa-calendar-alt"></i>
                        </button>
                    ` : ''}
                    <button class="btn btn-outline-info" onclick="viewPhaseDetails(${phase.id})" title="Xem chi tiết">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function updateOverviewStats(phases) {
    const totalPhases = phases.length;
    const totalPlayers = phases.reduce((sum, phase) => sum + phase.players_count, 0);
    const phasesWithPairs = phases.filter(phase => phase.can_schedule).length;
    const totalMatches = phases.reduce((sum, phase) => sum + (phase.players_count * phase.matches_per_player), 0);
    
    document.getElementById('total-phases').textContent = totalPhases;
    document.getElementById('total-players').textContent = totalPlayers;
    document.getElementById('total-pairs').textContent = phasesWithPairs;
    document.getElementById('total-matches').textContent = totalMatches;
}

function getPhaseTypeName(type) {
    const types = {
        'vong_loai': 'Vòng Loại',
        'ban_ket': 'Bán Kết',
        'chung_ket': 'Chung Kết'
    };
    return types[type] || type;
}

function getStatusName(status) {
    const statuses = {
        'draft': 'Draft',
        'scheduled': 'Đã Lên Lịch',
        'playing': 'Đang Thi Đấu',
        'completed': 'Hoàn Thành',
        'archived': 'Lưu Trữ'
    };
    return statuses[status] || status;
}

function getStatusBadgeColor(status) {
    const colors = {
        'draft': 'secondary',
        'scheduled': 'primary',
        'playing': 'warning',
        'completed': 'success',
        'archived': 'dark'
    };
    return colors[status] || 'secondary';
}

async function generatePairs(phaseId) {
    const phase = phasesData.find(p => p.id === phaseId);
    if (!phase) return;
    
    if (confirm(`Bạn có chắc muốn tạo cặp đôi cho "${phase.display_name}"?\n\nLưu ý: Các cặp đôi cũ (nếu có) sẽ bị xóa và thay thế bằng cặp đôi mới.`)) {
        showLoading('Đang tạo cặp đôi...');
        
        try {
            const response = await fetch('/api/admin/pairing/generate-pairs', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    phase_id: phaseId,
                    matches_per_player: phase.matches_per_player
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Tạo cặp đôi thành công!', 'success');
                loadPhases(); // Reload data
            } else {
                showToast('Có lỗi: ' + data.message, 'danger');
            }
        } catch (error) {
            console.error('Error generating pairs:', error);
            showToast('Có lỗi khi tạo cặp đôi', 'danger');
        } finally {
            hideLoading();
        }
    }
}

async function scheduleMatches(phaseId) {
    const phase = phasesData.find(p => p.id === phaseId);
    if (!phase) return;
    
    if (confirm(`Bạn có chắc muốn lên lịch thi đấu cho "${phase.display_name}"?`)) {
        showLoading('Đang lên lịch thi đấu...');
        
        try {
            const response = await fetch('/api/admin/pairing/schedule-matches', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    phase_id: phaseId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Lên lịch thi đấu thành công!', 'success');
                loadPhases(); // Reload data
            } else {
                showToast('Có lỗi: ' + data.message, 'danger');
            }
        } catch (error) {
            console.error('Error scheduling matches:', error);
            showToast('Có lỗi khi lên lịch thi đấu', 'danger');
        } finally {
            hideLoading();
        }
    }
}

function viewPhaseDetails(phaseId) {
    // Redirect to phase details or show modal
    window.open(`{{ route('admin.tournaments.index') }}#phase-${phaseId}`, '_blank');
}
</script>
@endpush
