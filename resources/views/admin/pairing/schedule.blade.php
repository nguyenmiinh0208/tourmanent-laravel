@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Lên Lịch Thi Đấu</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.pairing.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i> Quay Lại
            </a>
        </div>
    </div>

    <!-- Phase Selection -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Chọn Vòng Đấu</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <label for="phaseSelect" class="form-label">Vòng Đấu:</label>
                    <select class="form-select" id="phaseSelect" onchange="loadPhaseSchedule()">
                        <option value="">Chọn vòng đấu...</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-end h-100">
                        <button class="btn btn-primary" onclick="scheduleMatches()" id="scheduleBtn" disabled>
                            <i class="fas fa-calendar-alt me-2"></i> Lên Lịch Thi Đấu
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Phase Details -->
    <div class="card mb-4" id="phaseDetailsCard" style="display: none;">
        <div class="card-header">
            <h5 class="card-title mb-0">Thông Tin Vòng Đấu</h5>
        </div>
        <div class="card-body">
            <div class="row" id="phaseDetails">
                <!-- Phase details will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Pairs List -->
    <div class="card mb-4" id="pairsCard" style="display: none;">
        <div class="card-header">
            <h5 class="card-title mb-0">Danh Sách Cặp Đôi</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="pairsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Loại</th>
                            <th>Người Chơi 1</th>
                            <th>Người Chơi 2</th>
                            <th>Trạng Thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Pairs will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Schedule Preview -->
    <div class="card mb-4" id="scheduleCard" style="display: none;">
        <div class="card-header">
            <h5 class="card-title mb-0">Lịch Thi Đấu</h5>
        </div>
        <div class="card-body">
            <div class="row" id="schedulePreview">
                <!-- Schedule will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Courts Schedule -->
    <div class="card" id="courtsCard" style="display: none;">
        <div class="card-header">
            <h5 class="card-title mb-0">Lịch Theo Sân</h5>
        </div>
        <div class="card-body">
            <div class="row" id="courtsSchedule">
                <!-- Courts schedule will be loaded here -->
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
let selectedPhase = null;
let pairsData = [];
let scheduleData = [];

document.addEventListener('DOMContentLoaded', function() {
    loadPhases();
});

async function loadPhases() {
    try {
        const response = await fetch('/api/admin/pairing/phases');
        const data = await response.json();
        
        if (data.success) {
            const phaseSelect = document.getElementById('phaseSelect');
            phaseSelect.innerHTML = '<option value="">Chọn vòng đấu...</option>';
            
            data.data.forEach(phase => {
                if (phase.can_schedule) {
                    const option = document.createElement('option');
                    option.value = phase.id;
                    option.textContent = `${phase.display_name} - ${phase.name}`;
                    phaseSelect.appendChild(option);
                }
            });
        } else {
            showToast('Có lỗi khi tải danh sách vòng đấu: ' + data.message, 'danger');
        }
    } catch (error) {
        console.error('Error loading phases:', error);
        showToast('Có lỗi khi tải danh sách vòng đấu', 'danger');
    }
}

async function loadPhaseSchedule() {
    const phaseId = document.getElementById('phaseSelect').value;
    
    if (!phaseId) {
        hideAllCards();
        return;
    }
    
    try {
        // Load phase details
        const phaseResponse = await fetch('/api/admin/pairing/phases');
        const phaseData = await phaseResponse.json();
        
        if (phaseData.success) {
            selectedPhase = phaseData.data.find(p => p.id == phaseId);
            if (selectedPhase) {
                displayPhaseDetails(selectedPhase);
                loadPairs(phaseId);
                loadSchedule(phaseId);
                showCards();
            }
        }
    } catch (error) {
        console.error('Error loading phase schedule:', error);
        showToast('Có lỗi khi tải thông tin vòng đấu', 'danger');
    }
}

function displayPhaseDetails(phase) {
    const phaseDetails = document.getElementById('phaseDetails');
    phaseDetails.innerHTML = `
        <div class="col-md-3">
            <div class="text-center">
                <h6>Vòng Đấu</h6>
                <p class="mb-0"><strong>${phase.display_name}</strong></p>
                <small class="text-muted">${phase.name}</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="text-center">
                <h6>Loại</h6>
                <span class="badge bg-info">${getPhaseTypeName(phase.type)}</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="text-center">
                <h6>Số Người Chơi</h6>
                <p class="mb-0"><strong>${phase.players_count}</strong></p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="text-center">
                <h6>Trạng Thái</h6>
                <span class="badge bg-${getStatusBadgeColor(phase.status)}">${getStatusName(phase.status)}</span>
            </div>
        </div>
    `;
    
    document.getElementById('phaseDetailsCard').style.display = 'block';
}

async function loadPairs(phaseId) {
    try {
        const response = await fetch(`/api/admin/pairing/pairs?phase_id=${phaseId}`);
        const data = await response.json();
        
        if (data.success) {
            pairsData = data.data.pairs;
            renderPairsTable(data.data.pairs);
        } else {
            showToast('Có lỗi khi tải danh sách cặp đôi: ' + data.message, 'danger');
        }
    } catch (error) {
        console.error('Error loading pairs:', error);
        showToast('Có lỗi khi tải danh sách cặp đôi', 'danger');
    }
}

function renderPairsTable(pairs) {
    const tbody = document.querySelector('#pairsTable tbody');
    
    if (pairs.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center text-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Chưa có cặp đôi nào
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = pairs.map((pair, index) => `
        <tr>
            <td>${index + 1}</td>
            <td><span class="badge bg-${getPairTypeColor(pair.type)}">${pair.type}</span></td>
            <td>${pair.user_lo.name} (${pair.user_lo.gender})</td>
            <td>${pair.user_hi.name} (${pair.user_hi.gender})</td>
            <td><span class="badge bg-success">Sẵn sàng</span></td>
        </tr>
    `).join('');
    
    document.getElementById('pairsCard').style.display = 'block';
}

async function loadSchedule(phaseId) {
    try {
        const response = await fetch(`/api/admin/pairing/scheduled-matches?phase_id=${phaseId}`);
        const data = await response.json();
        
        if (data.success) {
            scheduleData = data.data.matches;
            renderSchedule(data.data);
        } else {
            // No schedule yet, show empty state
            renderEmptySchedule();
        }
    } catch (error) {
        console.error('Error loading schedule:', error);
        renderEmptySchedule();
    }
}

function renderSchedule(data) {
    if (data.matches.length === 0) {
        renderEmptySchedule();
        return;
    }
    
    const schedulePreview = document.getElementById('schedulePreview');
    schedulePreview.innerHTML = `
        <div class="col-md-12">
            <h6>Thống Kê Lịch Thi Đấu</h6>
            <div class="row">
                <div class="col-md-3">
                    <div class="text-center">
                        <h5 class="text-primary">${data.matches_by_status.scheduled}</h5>
                        <small>Đã Lên Lịch</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h5 class="text-warning">${data.matches_by_status.playing}</h5>
                        <small>Đang Thi Đấu</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h5 class="text-success">${data.matches_by_status.finished}</h5>
                        <small>Đã Kết Thúc</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h5 class="text-danger">${data.matches_by_status.canceled}</h5>
                        <small>Đã Hủy</small>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    renderCourtsSchedule(data.matches_by_court);
    document.getElementById('scheduleCard').style.display = 'block';
}

function renderEmptySchedule() {
    const schedulePreview = document.getElementById('schedulePreview');
    schedulePreview.innerHTML = `
        <div class="col-md-12 text-center text-muted">
            <i class="fas fa-calendar-times fa-3x mb-3"></i>
            <h5>Chưa có lịch thi đấu</h5>
            <p>Nhấn "Lên Lịch Thi Đấu" để tạo lịch cho vòng đấu này</p>
        </div>
    `;
    
    document.getElementById('scheduleCard').style.display = 'block';
}

function renderCourtsSchedule(matchesByCourt) {
    const courtsSchedule = document.getElementById('courtsSchedule');
    
    let html = '';
    for (const [courtName, matches] of Object.entries(matchesByCourt)) {
        html += `
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">${courtName}</h6>
                    </div>
                    <div class="card-body">
                        ${matches.length === 0 ? 
                            '<p class="text-muted text-center">Không có trận đấu</p>' :
                            matches.map(match => `
                                <div class="border rounded p-2 mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">${match.time_slot ? match.time_slot.time_range : 'Chưa có giờ'}</small>
                                        <span class="badge bg-${getMatchStatusColor(match.status)}">${match.status_name}</span>
                                    </div>
                                    <div class="mt-1">
                                        <small><strong>${match.pair_a.name}</strong> vs <strong>${match.pair_b.name}</strong></small>
                                    </div>
                                </div>
                            `).join('')
                        }
                    </div>
                </div>
            </div>
        `;
    }
    
    courtsSchedule.innerHTML = html;
    document.getElementById('courtsCard').style.display = 'block';
}

function showCards() {
    document.getElementById('pairsCard').style.display = 'block';
    document.getElementById('scheduleCard').style.display = 'block';
    document.getElementById('courtsCard').style.display = 'block';
}

function hideAllCards() {
    document.getElementById('phaseDetailsCard').style.display = 'none';
    document.getElementById('pairsCard').style.display = 'none';
    document.getElementById('scheduleCard').style.display = 'none';
    document.getElementById('courtsCard').style.display = 'none';
}

async function scheduleMatches() {
    if (!selectedPhase) {
        showToast('Vui lòng chọn vòng đấu', 'warning');
        return;
    }
    
    if (confirm(`Bạn có chắc muốn lên lịch thi đấu cho "${selectedPhase.display_name}"?`)) {
        showLoading('Đang lên lịch thi đấu...');
        
        try {
            const response = await fetch('/api/admin/pairing/schedule-matches', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    phase_id: selectedPhase.id
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Lên lịch thi đấu thành công!', 'success');
                loadPhaseSchedule(); // Reload data
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

function getPairTypeColor(type) {
    const colors = {
        'XD': 'primary',
        'MD': 'info',
        'WD': 'danger'
    };
    return colors[type] || 'secondary';
}

function getMatchStatusColor(status) {
    const colors = {
        'scheduled': 'primary',
        'playing': 'warning',
        'finished': 'success',
        'canceled': 'danger'
    };
    return colors[status] || 'secondary';
}

// Enable schedule button when phase is selected
document.getElementById('phaseSelect').addEventListener('change', function() {
    const scheduleBtn = document.getElementById('scheduleBtn');
    scheduleBtn.disabled = !this.value;
});
</script>
@endpush
