@extends('layouts.admin')

@section('title', 'Tạo Vòng Đấu Mới')
@section('page-title', 'Tạo Vòng Đấu Mới')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-plus me-2"></i>Tạo Vòng Đấu Mới</h5>
            </div>
            <div class="card-body">
                <form id="createTournamentForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="type" class="form-label">Loại Vòng Đấu <span class="text-danger">*</span></label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="">Chọn loại vòng đấu</option>
                                <option value="vong_loai">Vòng Loại</option>
                                <option value="ban_ket">Bán Kết</option>
                                <option value="chung_ket">Chung Kết</option>
                            </select>
                            <div class="form-text">Chọn loại vòng đấu phù hợp</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Tên Vòng Đấu <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required placeholder="Ví dụ: Giải Cầu Lông Mùa Hè 2024">
                            <div class="form-text">Tên mô tả cho vòng đấu này</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_at" class="form-label">Thời Gian Bắt Đầu</label>
                            <input type="datetime-local" class="form-control" id="start_at" name="start_at">
                            <div class="form-text">Thời gian dự kiến bắt đầu vòng đấu</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="end_at" class="form-label">Thời Gian Kết Thúc</label>
                            <input type="datetime-local" class="form-control" id="end_at" name="end_at">
                            <div class="form-text">Thời gian dự kiến kết thúc vòng đấu</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="matches_per_player" class="form-label">Số Trận Đấu Mỗi Người <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="matches_per_player" name="matches_per_player" min="1" max="10" value="3" required>
                            <div class="form-text">Mỗi người chơi sẽ tham gia bao nhiêu trận đấu</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Trạng Thái Ban Đầu</label>
                            <select class="form-select" id="status" name="status">
                                <option value="draft">Draft (Bản nháp)</option>
                                <option value="scheduled">Scheduled (Đã lên lịch)</option>
                            </select>
                            <div class="form-text">Trạng thái của vòng đấu khi tạo</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="seed" class="form-label">Seed (Tùy chọn)</label>
                        <input type="text" class="form-control" id="seed" name="seed" placeholder="Để trống để tự động tạo">
                        <div class="form-text">Seed để tạo cặp ngẫu nhiên có thể lặp lại. Để trống sẽ tự động tạo seed mới.</div>
                    </div>

                    <!-- Tournament Rules Information -->
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>Quy Tắc Tạo Cặp Thi Đấu</h6>
                        <ul class="mb-0">
                            <li><strong>Ưu tiên XD (Mixed Doubles):</strong> Ghép nam và nữ làm cặp đôi</li>
                            <li><strong>Tiếp theo MD (Men's Doubles):</strong> Ghép nam với nam còn lại</li>
                            <li><strong>Cuối cùng WD (Women's Doubles):</strong> Ghép nữ với nữ còn lại</li>
                            <li>Hệ thống đảm bảo mỗi người chơi tham gia đúng số trận đã chỉ định</li>
                            <li>Lịch thi đấu được phân bổ đều trên 3 sân từ 8h-12h</li>
                        </ul>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.tournaments.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Quay Lại
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Tạo Vòng Đấu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Preview Section -->
<div class="row justify-content-center mt-4" id="previewSection" style="display: none;">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-eye me-2"></i>Xem Trước Vòng Đấu</h5>
            </div>
            <div class="card-body" id="previewContent">
                <!-- Preview content will be populated here -->
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('createTournamentForm');
    
    // Form validation and preview
    form.addEventListener('input', updatePreview);
    form.addEventListener('change', updatePreview);
    
    // Form submission
    form.addEventListener('submit', handleSubmit);
    
    // Set default dates
    const now = new Date();
    const tomorrow = new Date(now);
    tomorrow.setDate(tomorrow.getDate() + 1);
    tomorrow.setHours(8, 0, 0, 0);
    
    const endDate = new Date(tomorrow);
    endDate.setHours(12, 0, 0, 0);
    
    document.getElementById('start_at').value = formatDateTimeLocal(tomorrow);
    document.getElementById('end_at').value = formatDateTimeLocal(endDate);
    
    updatePreview();
});

function formatDateTimeLocal(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    
    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

function updatePreview() {
    const formData = new FormData(document.getElementById('createTournamentForm'));
    const data = Object.fromEntries(formData.entries());
    
    if (!data.type || !data.name) {
        document.getElementById('previewSection').style.display = 'none';
        return;
    }
    
    const typeNames = {
        'vong_loai': 'Vòng Loại',
        'ban_ket': 'Bán Kết',
        'chung_ket': 'Chung Kết'
    };
    
    const statusNames = {
        'draft': 'Draft (Bản nháp)',
        'scheduled': 'Scheduled (Đã lên lịch)'
    };
    
    const previewHtml = `
        <div class="row">
            <div class="col-md-6">
                <h6>Thông Tin Vòng Đấu</h6>
                <table class="table table-sm">
                    <tr><td><strong>Tên:</strong></td><td>${data.name}</td></tr>
                    <tr><td><strong>Loại:</strong></td><td><span class="badge badge-${data.type}">${typeNames[data.type]}</span></td></tr>
                    <tr><td><strong>Trạng thái:</strong></td><td><span class="badge badge-${data.status}">${statusNames[data.status]}</span></td></tr>
                    <tr><td><strong>Trận đấu/người:</strong></td><td>${data.matches_per_player}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Thời Gian</h6>
                <table class="table table-sm">
                    <tr><td><strong>Bắt đầu:</strong></td><td>${data.start_at ? new Date(data.start_at).toLocaleString('vi-VN') : 'Chưa định'}</td></tr>
                    <tr><td><strong>Kết thúc:</strong></td><td>${data.end_at ? new Date(data.end_at).toLocaleString('vi-VN') : 'Chưa định'}</td></tr>
                    <tr><td><strong>Seed:</strong></td><td>${data.seed || 'Tự động tạo'}</td></tr>
                </table>
            </div>
        </div>
        
        <div class="alert alert-success mt-3">
            <h6><i class="fas fa-check-circle me-2"></i>Các Bước Tiếp Theo</h6>
            <ol class="mb-0">
                <li>Tạo vòng đấu</li>
                <li>Import danh sách người chơi</li>
                <li>Tạo cặp thi đấu tự động</li>
                <li>Lên lịch thi đấu trên 3 sân</li>
                <li>Bắt đầu thi đấu và cập nhật kết quả</li>
            </ol>
        </div>
    `;
    
    document.getElementById('previewContent').innerHTML = previewHtml;
    document.getElementById('previewSection').style.display = 'block';
}

async function handleSubmit(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    // Validation
    if (!data.type || !data.name || !data.matches_per_player) {
        showToast('Vui lòng điền đầy đủ thông tin bắt buộc', 'warning');
        return;
    }
    
    if (parseInt(data.matches_per_player) < 1 || parseInt(data.matches_per_player) > 10) {
        showToast('Số trận đấu mỗi người phải từ 1 đến 10', 'warning');
        return;
    }
    
    // Check date logic
    if (data.start_at && data.end_at && new Date(data.start_at) >= new Date(data.end_at)) {
        showToast('Thời gian kết thúc phải sau thời gian bắt đầu', 'warning');
        return;
    }
    
    try {
        showLoading();
        
        // Prepare data for API
        const apiData = {
            type: data.type,
            name: data.name,
            start_at: data.start_at || null,
            end_at: data.end_at || null,
            status: data.status || 'draft',
            matches_per_player: parseInt(data.matches_per_player),
            seed: data.seed || null
        };
        
        const response = await fetch('/api/admin/tournaments', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.csrfToken
            },
            body: JSON.stringify(apiData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Tạo vòng đấu thành công!', 'success');
            
            // Redirect back to tournaments list after a short delay
            setTimeout(() => {
                window.location.href = '/admin/tournaments';
            }, 1500);
        } else {
            showToast('Có lỗi khi tạo vòng đấu: ' + result.message, 'danger');
        }
        
    } catch (error) {
        console.error('Error creating tournament:', error);
        showToast('Có lỗi khi tạo vòng đấu', 'danger');
    } finally {
        hideLoading();
    }
}

// Form validation helpers
function validateForm() {
    const form = document.getElementById('createTournamentForm');
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

// Real-time validation
document.getElementById('createTournamentForm').addEventListener('input', function(e) {
    const field = e.target;
    if (field.hasAttribute('required')) {
        if (field.value.trim()) {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
        } else {
            field.classList.remove('is-valid');
            field.classList.add('is-invalid');
        }
    }
});
</script>
@endpush
