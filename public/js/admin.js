/**
 * Tournament Admin Dashboard JavaScript
 * Handles API interactions and UI components
 */

// Global configuration
window.AdminApp = {
    apiBase: '/api',
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
    
    // API endpoints
    endpoints: {
        tournaments: '/api/admin/tournaments',
        matches: '/api/matches',
        leaderboard: '/api/leaderboard',
        players: '/api/admin/players'
    },
    
    // Configuration
    config: {
        autoRefreshInterval: 30000, // 30 seconds
        toastDuration: 5000,
        maxRetries: 3
    }
};

/**
 * API Helper Class
 */
class ApiClient {
    constructor(baseUrl = '/api') {
        this.baseUrl = baseUrl;
        this.defaultHeaders = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };
        
        // Add CSRF token if available
        if (window.AdminApp.csrfToken) {
            this.defaultHeaders['X-CSRF-TOKEN'] = window.AdminApp.csrfToken;
        }
    }
    
    async request(endpoint, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;
        const config = {
            headers: { ...this.defaultHeaders, ...options.headers },
            ...options
        };
        
        try {
            const response = await fetch(url, config);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('API request failed:', error);
            throw error;
        }
    }
    
    async get(endpoint, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `${endpoint}?${queryString}` : endpoint;
        return this.request(url, { method: 'GET' });
    }
    
    async post(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }
    
    async put(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }
    
    async delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    }
}

// Initialize API client
window.api = new ApiClient();

/**
 * Tournament API Service
 */
class TournamentService {
    static async getAll() {
        return window.api.get('/admin/tournaments');
    }
    
    static async getById(id) {
        return window.api.get(`/admin/tournaments/${id}`);
    }
    
    static async create(data) {
        return window.api.post('/admin/tournaments', data);
    }
    
    static async update(id, data) {
        return window.api.put(`/admin/tournaments/${id}`, data);
    }
    
    static async delete(id) {
        return window.api.delete(`/admin/tournaments/${id}`);
    }
    
    static async importPlayers(id, players) {
        return window.api.post(`/admin/tournaments/${id}/import-players`, { players });
    }
    
    static async generatePairs(id, matchesPerPlayer) {
        return window.api.post(`/admin/tournaments/${id}/generate-pairs`, { 
            matches_per_player: matchesPerPlayer 
        });
    }
    
    static async scheduleMatches(id) {
        return window.api.post(`/admin/tournaments/${id}/schedule-matches`);
    }
}

/**
 * Match API Service
 */
class MatchService {
    static async getAll(params = {}) {
        return window.api.get('/matches', params);
    }
    
    static async getById(id) {
        return window.api.get(`/matches/${id}`);
    }
    
    static async updateResult(id, data) {
        return window.api.put(`/matches/${id}/result`, data);
    }
    
    static async getTodayMatches() {
        return window.api.get('/matches/today');
    }
}

/**
 * Leaderboard API Service
 */
class LeaderboardService {
    static async get(params = {}) {
        return window.api.get('/leaderboard', params);
    }
}

/**
 * UI Helper Class
 */
class UIHelpers {
    static showLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.classList.remove('d-none');
        }
    }
    
    static hideLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.classList.add('d-none');
        }
    }
    
    static showToast(message, type = 'success', duration = 5000) {
        const toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) return;
        
        const toastId = 'toast-' + Date.now();
        const iconMap = {
            success: 'check-circle',
            danger: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        
        const toastHtml = `
            <div class="toast align-items-center text-bg-${type} border-0" role="alert" id="${toastId}">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-${iconMap[type] || 'info-circle'} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, { delay: duration });
        toast.show();
        
        // Remove toast element after it's hidden
        toastElement.addEventListener('hidden.bs.toast', function() {
            this.remove();
        });
    }
    
    static confirm(message, title = 'Xác nhận') {
        return new Promise((resolve) => {
            if (confirm(`${title}\n\n${message}`)) {
                resolve(true);
            } else {
                resolve(false);
            }
        });
    }
    
    static formatDate(dateString, options = {}) {
        if (!dateString) return 'Chưa định';
        
        const defaultOptions = {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        };
        
        return new Date(dateString).toLocaleDateString('vi-VN', { ...defaultOptions, ...options });
    }
    
    static formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(amount);
    }
    
    static debounce(func, wait) {
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
    
    static throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
}

/**
 * Data Table Component
 */
class DataTable {
    constructor(tableId, options = {}) {
        this.table = document.getElementById(tableId);
        this.tbody = this.table.querySelector('tbody');
        this.options = {
            searchable: true,
            sortable: true,
            pagination: true,
            pageSize: 10,
            ...options
        };
        this.data = [];
        this.filteredData = [];
        this.currentPage = 1;
        this.sortColumn = null;
        this.sortDirection = 'asc';
        
        this.init();
    }
    
    init() {
        if (this.options.searchable) {
            this.createSearchInput();
        }
        
        if (this.options.sortable) {
            this.enableSorting();
        }
        
        if (this.options.pagination) {
            this.createPagination();
        }
    }
    
    setData(data) {
        this.data = data;
        this.filteredData = [...data];
        this.render();
    }
    
    render() {
        if (!this.options.renderRow) {
            console.error('DataTable: renderRow function is required');
            return;
        }
        
        const startIndex = (this.currentPage - 1) * this.options.pageSize;
        const endIndex = startIndex + this.options.pageSize;
        const pageData = this.filteredData.slice(startIndex, endIndex);
        
        this.tbody.innerHTML = pageData.map(this.options.renderRow).join('');
        
        if (this.options.pagination) {
            this.updatePagination();
        }
    }
    
    search(query) {
        if (!this.options.searchFields) {
            console.error('DataTable: searchFields is required for search functionality');
            return;
        }
        
        this.filteredData = this.data.filter(item => {
            return this.options.searchFields.some(field => {
                const value = this.getNestedValue(item, field);
                return String(value).toLowerCase().includes(query.toLowerCase());
            });
        });
        
        this.currentPage = 1;
        this.render();
    }
    
    sort(column) {
        if (this.sortColumn === column) {
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortColumn = column;
            this.sortDirection = 'asc';
        }
        
        this.filteredData.sort((a, b) => {
            const aVal = this.getNestedValue(a, column);
            const bVal = this.getNestedValue(b, column);
            
            if (aVal < bVal) return this.sortDirection === 'asc' ? -1 : 1;
            if (aVal > bVal) return this.sortDirection === 'asc' ? 1 : -1;
            return 0;
        });
        
        this.render();
    }
    
    getNestedValue(obj, path) {
        return path.split('.').reduce((current, key) => current?.[key], obj);
    }
    
    createSearchInput() {
        // Implementation for search input creation
    }
    
    enableSorting() {
        // Implementation for sortable headers
    }
    
    createPagination() {
        // Implementation for pagination controls
    }
    
    updatePagination() {
        // Implementation for updating pagination state
    }
}

/**
 * Modal Component
 */
class ModalComponent {
    constructor(modalId) {
        this.modalElement = document.getElementById(modalId);
        this.modal = new bootstrap.Modal(this.modalElement);
    }
    
    show(data = {}) {
        if (this.beforeShow) {
            this.beforeShow(data);
        }
        this.modal.show();
    }
    
    hide() {
        this.modal.hide();
    }
    
    setTitle(title) {
        const titleElement = this.modalElement.querySelector('.modal-title');
        if (titleElement) {
            titleElement.textContent = title;
        }
    }
    
    setBody(content) {
        const bodyElement = this.modalElement.querySelector('.modal-body');
        if (bodyElement) {
            bodyElement.innerHTML = content;
        }
    }
}

/**
 * Form Validator
 */
class FormValidator {
    constructor(formId, rules = {}) {
        this.form = document.getElementById(formId);
        this.rules = rules;
        this.errors = {};
        
        this.init();
    }
    
    init() {
        this.form.addEventListener('submit', (e) => {
            if (!this.validate()) {
                e.preventDefault();
                this.showErrors();
            }
        });
        
        // Real-time validation
        this.form.addEventListener('input', (e) => {
            this.validateField(e.target);
        });
    }
    
    validate() {
        this.errors = {};
        let isValid = true;
        
        Object.keys(this.rules).forEach(fieldName => {
            const field = this.form.querySelector(`[name="${fieldName}"]`);
            if (field && !this.validateField(field)) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    validateField(field) {
        const fieldName = field.name;
        const rules = this.rules[fieldName];
        const value = field.value.trim();
        
        if (!rules) return true;
        
        // Required validation
        if (rules.required && !value) {
            this.setError(fieldName, 'Trường này là bắt buộc');
            return false;
        }
        
        // Min length validation
        if (rules.minLength && value.length < rules.minLength) {
            this.setError(fieldName, `Tối thiểu ${rules.minLength} ký tự`);
            return false;
        }
        
        // Max length validation
        if (rules.maxLength && value.length > rules.maxLength) {
            this.setError(fieldName, `Tối đa ${rules.maxLength} ký tự`);
            return false;
        }
        
        // Email validation
        if (rules.email && value && !this.isValidEmail(value)) {
            this.setError(fieldName, 'Email không hợp lệ');
            return false;
        }
        
        // Custom validation
        if (rules.custom && typeof rules.custom === 'function') {
            const customResult = rules.custom(value, field);
            if (customResult !== true) {
                this.setError(fieldName, customResult);
                return false;
            }
        }
        
        this.clearError(fieldName);
        return true;
    }
    
    setError(fieldName, message) {
        this.errors[fieldName] = message;
        const field = this.form.querySelector(`[name="${fieldName}"]`);
        if (field) {
            field.classList.add('is-invalid');
            field.classList.remove('is-valid');
            
            // Show error message
            let errorDiv = field.parentNode.querySelector('.invalid-feedback');
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback';
                field.parentNode.appendChild(errorDiv);
            }
            errorDiv.textContent = message;
        }
    }
    
    clearError(fieldName) {
        delete this.errors[fieldName];
        const field = this.form.querySelector(`[name="${fieldName}"]`);
        if (field) {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
            
            const errorDiv = field.parentNode.querySelector('.invalid-feedback');
            if (errorDiv) {
                errorDiv.remove();
            }
        }
    }
    
    showErrors() {
        Object.keys(this.errors).forEach(fieldName => {
            this.setError(fieldName, this.errors[fieldName]);
        });
        
        if (Object.keys(this.errors).length > 0) {
            UIHelpers.showToast('Vui lòng kiểm tra lại thông tin', 'warning');
        }
    }
    
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
}

/**
 * Auto Refresh Manager
 */
class AutoRefresh {
    constructor() {
        this.intervals = new Map();
        this.isVisible = true;
        
        // Pause auto-refresh when tab is not visible
        document.addEventListener('visibilitychange', () => {
            this.isVisible = !document.hidden;
            if (this.isVisible) {
                this.resumeAll();
            } else {
                this.pauseAll();
            }
        });
    }
    
    add(name, callback, interval = 30000) {
        this.remove(name); // Remove existing if any
        
        const intervalId = setInterval(() => {
            if (this.isVisible) {
                callback();
            }
        }, interval);
        
        this.intervals.set(name, intervalId);
    }
    
    remove(name) {
        if (this.intervals.has(name)) {
            clearInterval(this.intervals.get(name));
            this.intervals.delete(name);
        }
    }
    
    pauseAll() {
        this.intervals.forEach((intervalId) => {
            clearInterval(intervalId);
        });
    }
    
    resumeAll() {
        // This would need to store the original callbacks and intervals
        // For simplicity, we'll just clear all intervals
        this.intervals.clear();
    }
}

// Global instances
window.TournamentService = TournamentService;
window.MatchService = MatchService;
window.LeaderboardService = LeaderboardService;
window.UIHelpers = UIHelpers;
window.DataTable = DataTable;
window.ModalComponent = ModalComponent;
window.FormValidator = FormValidator;
window.autoRefresh = new AutoRefresh();

// Global helper functions for backward compatibility
window.showLoading = UIHelpers.showLoading;
window.hideLoading = UIHelpers.hideLoading;
window.showToast = UIHelpers.showToast;

// Initialize common functionality
document.addEventListener('DOMContentLoaded', function() {
    // Setup CSRF token for all fetch requests
    if (window.AdminApp.csrfToken) {
        // Override fetch to include CSRF token
        const originalFetch = window.fetch;
        window.fetch = function(url, options = {}) {
            if (options.method && ['POST', 'PUT', 'DELETE', 'PATCH'].includes(options.method.toUpperCase())) {
                options.headers = {
                    ...options.headers,
                    'X-CSRF-TOKEN': window.AdminApp.csrfToken
                };
            }
            return originalFetch(url, options);
        };
    }
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    console.log('Tournament Admin Dashboard initialized');
});
