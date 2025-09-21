@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Tạo Cặp Đôi</h1>
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
                    <select class="form-select" id="phaseSelect" onchange="loadPhaseDetails()">
                        <option value="">Chọn vòng đấu...</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="matchesPerPlayer" class="form-label">Số Trận Mỗi Người Chơi:</label>
                    <input type="number" class="form-control" id="matchesPerPlayer" min="1" max="10" value="1">
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

    <!-- Players List -->
    <div class="card mb-4" id="playersCard" style="display: none;">
        <div class="card-header">
            <h5 class="card-title mb-0">Danh Sách Người Chơi</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="playersTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Họ Tên</th>
                            <th>Giới Tính</th>
                            <th>Trạng Thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Players will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pairing Algorithm Settings -->
    <div class="card mb-4" id="algorithmCard" style="display: none;">
        <div class="card-header">
            <h5 class="card-title mb-0">Cài Đặt Thuật Toán Tạo Cặp</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <h6>Quy Tắc Ưu Tiên:</h6>
                    <ol>
                        <li><strong>Mixed Doubles (XD):</strong> Ưu tiên tạo cặp nam-nữ</li>
                        <li><strong>Men's Doubles (MD):</strong> Cặp đôi nam cho nam còn lại</li>
                        <li><strong>Women's Doubles (WD):</strong> Cặp đôi nữ cho nữ còn lại</li>
                    </ol>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Hệ thống sẽ tự động tạo cặp đôi tối ưu dựa trên số lượng người chơi và quy tắc trên.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Generate Pairs Button -->
    <div class="card" id="generateCard" style="display: none;">
        <div class="card-body text-center">
            <button class="btn btn-primary btn-lg" onclick="generatePairs()" id="generateBtn">
                <i class="fas fa-random me-2"></i> Tạo Cặp Đôi
            </button>
        </div>
    </div>

    <!-- Results Modal -->
    <div class="modal fade" id="resultsModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Kết Quả Tạo Cặp Đôi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="resultsContent">
                    <!-- Results will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-success" onclick="confirmGenerate()">Xác Nhận Tạo Cặp</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
let selectedPhase = null;
let playersData = [];

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
                if (phase.can_generate_pairs) {
                    const option = document.createElement('option');
                    option.value = phase.id;
                    option.textContent = `${phase.display_name} - ${phase.name} (${phase.players_count} người)`;
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

async function loadPhaseDetails() {
    const phaseId = document.getElementById('phaseSelect').value;
    
    if (!phaseId) {
        hideAllCards();
        return;
    }
    
    try {
        const response = await fetch(`/api/admin/pairing/phases`);
        const data = await response.json();
        
        if (data.success) {
            selectedPhase = data.data.find(p => p.id == phaseId);
            if (selectedPhase) {
                displayPhaseDetails(selectedPhase);
                loadPlayers(selectedPhase);
                showCards();
            }
        }
    } catch (error) {
        console.error('Error loading phase details:', error);
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
                <h6>Trận/Người</h6>
                <p class="mb-0"><strong>${phase.matches_per_player}</strong></p>
            </div>
        </div>
    `;
    
    document.getElementById('phaseDetailsCard').style.display = 'block';
}

async function loadPlayers(phase) {
    // For now, we'll use the phase data we already have
    // In a real implementation, you might want to fetch detailed player info
    const playersTable = document.querySelector('#playersTable tbody');
    
    if (phase.players_count > 0) {
        playersTable.innerHTML = `
            <tr>
                <td colspan="4" class="text-center">
                    <div class="spinner-border spinner-border-sm me-2" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    Đang tải danh sách người chơi...
                </td>
            </tr>
        `;
        
        // Simulate loading players (in real app, fetch from API)
        setTimeout(() => {
            playersTable.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center text-muted">
                        <i class="fas fa-users me-2"></i>
                        ${phase.players_count} người chơi đã sẵn sàng
                    </td>
                </tr>
            `;
        }, 1000);
    } else {
        playersTable.innerHTML = `
            <tr>
                <td colspan="4" class="text-center text-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Chưa có người chơi nào
                </td>
            </tr>
        `;
    }
}

function showCards() {
    document.getElementById('playersCard').style.display = 'block';
    document.getElementById('algorithmCard').style.display = 'block';
    document.getElementById('generateCard').style.display = 'block';
}

function hideAllCards() {
    document.getElementById('phaseDetailsCard').style.display = 'none';
    document.getElementById('playersCard').style.display = 'none';
    document.getElementById('algorithmCard').style.display = 'none';
    document.getElementById('generateCard').style.display = 'none';
}

async function generatePairs() {
    if (!selectedPhase) {
        showToast('Vui lòng chọn vòng đấu', 'warning');
        return;
    }
    
    const matchesPerPlayer = document.getElementById('matchesPerPlayer').value;
    if (!matchesPerPlayer || matchesPerPlayer < 1) {
        showToast('Vui lòng nhập số trận mỗi người chơi', 'warning');
        return;
    }
    
    // Confirm before generating pairs (will replace existing ones)
    if (!confirm(`Bạn có chắc muốn tạo cặp đôi cho "${selectedPhase.display_name}"?\n\nLưu ý: Các cặp đôi cũ (nếu có) sẽ bị xóa và thay thế bằng cặp đôi mới.`)) {
        return;
    }
    
    showLoading('Đang tạo cặp đôi...');
    
    try {
        const response = await fetch('/api/admin/pairing/generate-pairs', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                phase_id: selectedPhase.id,
                matches_per_player: parseInt(matchesPerPlayer)
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showResults(data.data);
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

function showResults(data) {
    const resultsContent = document.getElementById('resultsContent');
    
    resultsContent.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6>Thống Kê</h6>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Tổng người chơi:</span>
                        <strong>${data.stats.total_players}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Nam:</span>
                        <strong>${data.stats.male_players}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Nữ:</span>
                        <strong>${data.stats.female_players}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Trận/người:</span>
                        <strong>${data.stats.matches_per_player}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Tổng cặp đôi:</span>
                        <strong>${data.stats.total_pairs}</strong>
                    </li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6>Phân Loại Cặp Đôi</h6>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Mixed Doubles (XD):</span>
                        <strong class="text-primary">${data.stats.pairs_by_type.XD}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Men's Doubles (MD):</span>
                        <strong class="text-info">${data.stats.pairs_by_type.MD}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Women's Doubles (WD):</span>
                        <strong class="text-danger">${data.stats.pairs_by_type.WD}</strong>
                    </li>
                </ul>
            </div>
        </div>
        
        ${data.warnings && data.warnings.length > 0 ? `
            <div class="alert alert-warning mt-3">
                <h6><i class="fas fa-exclamation-triangle me-2"></i>Cảnh Báo</h6>
                <ul class="mb-0">
                    ${data.warnings.map(warning => `<li>${warning}</li>`).join('')}
                </ul>
            </div>
        ` : ''}
        
        <div class="mt-3">
            <h6>Danh Sách Cặp Đôi</h6>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Loại</th>
                            <th>Người Chơi 1</th>
                            <th>Người Chơi 2</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.pairs.map((pair, index) => `
                            <tr>
                                <td>${index + 1}</td>
                                <td><span class="badge bg-${getPairTypeColor(pair.type)}">${pair.type}</span></td>
                                <td>${pair.user_lo.name} (${pair.user_lo.gender})</td>
                                <td>${pair.user_hi.name} (${pair.user_hi.gender})</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        </div>
    `;
    
    const modal = new bootstrap.Modal(document.getElementById('resultsModal'));
    modal.show();
}

function getPhaseTypeName(type) {
    const types = {
        'vong_loai': 'Vòng Loại',
        'ban_ket': 'Bán Kết',
        'chung_ket': 'Chung Kết'
    };
    return types[type] || type;
}

function getPairTypeColor(type) {
    const colors = {
        'XD': 'primary',
        'MD': 'info',
        'WD': 'danger'
    };
    return colors[type] || 'secondary';
}

async function confirmGenerate() {
    // This would actually save the pairs to database
    showToast('Cặp đôi đã được tạo thành công!', 'success');
    
    const modal = bootstrap.Modal.getInstance(document.getElementById('resultsModal'));
    modal.hide();
    
    // Redirect back to pairing index
    setTimeout(() => {
        window.location.href = '{{ route("admin.pairing.index") }}';
    }, 1500);
}
</script>
@endpush
