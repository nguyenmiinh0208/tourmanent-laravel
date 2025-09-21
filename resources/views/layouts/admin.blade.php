<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Admin Dashboard') - {{ config('app.name', 'Tournament Manager') }}</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    
    @stack('styles')
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <h4><i class="fas fa-trophy"></i> Tournament</h4>
        </div>
        
        <div class="nav flex-column px-3">
            <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="fas fa-home"></i>
                Dashboard
            </a>
            
            <a href="{{ route('admin.tournaments.index') }}" class="nav-link {{ request()->routeIs('admin.tournaments.*') ? 'active' : '' }}">
                <i class="fas fa-trophy"></i>
                Quản Lý Vòng Đấu
            </a>
            
            <a href="#" class="nav-link" onclick="alert('Player management coming soon!');">
                <i class="fas fa-users"></i>
                Quản Lý Người Chơi
            </a>
            
            <a href="{{ route('admin.pairing.index') }}" class="nav-link {{ request()->routeIs('admin.pairing.*') ? 'active' : '' }}">
                <i class="fas fa-random"></i>
                Tạo Cặp & Lịch Thi Đấu
            </a>
            
                    <a href="{{ route('admin.matches.index') }}" class="nav-link {{ request()->routeIs('admin.matches.*') ? 'active' : '' }}">
                        <i class="fas fa-table-tennis"></i>
                        Quản Lý Trận Đấu
                    </a>
            
            <a href="{{ route('admin.leaderboard.index') }}" class="nav-link {{ request()->routeIs('admin.leaderboard.*') ? 'active' : '' }}">
                <i class="fas fa-chart-line"></i>
                Bảng Xếp Hạng
            </a>
            
            <hr class="my-3" style="border-color: rgba(255,255,255,0.2);">
            
            <a href="#" class="nav-link" onclick="alert('Settings coming soon!');">
                <i class="fas fa-cog"></i>
                Cài Đặt
            </a>
            
            <a href="#" class="nav-link" onclick="alert('Logout functionality not implemented yet');">
                <i class="fas fa-sign-out-alt"></i>
                Đăng Xuất
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Navigation Bar -->
        <div class="top-navbar d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <button class="btn btn-outline-secondary mobile-toggle d-md-none" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h5 class="mb-0 ms-2">@yield('page-title', 'Dashboard')</h5>
            </div>
            
            <div class="d-flex align-items-center">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i>
                        Admin
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user"></i> Hồ Sơ</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog"></i> Cài Đặt</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" onclick="alert('Logout functionality not implemented yet');"><i class="fas fa-sign-out-alt"></i> Đăng Xuất</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Breadcrumb -->
            @if(isset($breadcrumbs))
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    @foreach($breadcrumbs as $breadcrumb)
                        @if($loop->last)
                            <li class="breadcrumb-item active">{{ $breadcrumb['title'] }}</li>
                        @else
                            <li class="breadcrumb-item">
                                <a href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['title'] }}</a>
                            </li>
                        @endif
                    @endforeach
                </ol>
            </nav>
            @endif

            <!-- Page Content -->
            @yield('content')
        </div>
    </main>

    <!-- Loading Overlay -->
    <div class="spinner-overlay d-none" id="loadingOverlay">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Admin JS -->
    <script src="{{ asset('js/admin.js') }}"></script>
    
    <!-- Common JavaScript Functions -->
    <script>
        // Toggle sidebar on mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.querySelector('.mobile-toggle');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(event.target) && 
                !toggleBtn.contains(event.target) &&
                sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        });

        // Show loading overlay
        function showLoading() {
            document.getElementById('loadingOverlay').classList.remove('d-none');
        }

        // Hide loading overlay
        function hideLoading() {
            document.getElementById('loadingOverlay').classList.add('d-none');
        }

        // Show toast notification
        function showToast(message, type = 'success') {
            const toastContainer = document.getElementById('toastContainer');
            const toastId = 'toast-' + Date.now();
            
            const toastHtml = `
                <div class="toast align-items-center text-bg-${type} border-0" role="alert" id="${toastId}">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-circle' : 'info-circle'}"></i>
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            
            const toast = new bootstrap.Toast(document.getElementById(toastId));
            toast.show();
            
            // Remove toast element after it's hidden
            document.getElementById(toastId).addEventListener('hidden.bs.toast', function() {
                this.remove();
            });
        }

        // CSRF token for AJAX requests
        window.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // Set CSRF token for all AJAX requests
        if (window.axios) {
            window.axios.defaults.headers.common['X-CSRF-TOKEN'] = window.csrfToken;
        }
    </script>
    
    @stack('scripts')
</body>
</html>
