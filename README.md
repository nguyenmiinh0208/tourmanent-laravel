# Hệ Thống Quản Lý Giải Đấu Cầu Lông

Một hệ thống quản lý giải đấu cầu lông được xây dựng với Laravel 12, hỗ trợ import người chơi, tạo cặp thi đấu ngẫu nhiên, lên lịch thi đấu và quản lý bảng xếp hạng.

## Tính Năng Chính

### 🏆 Quản Lý Vòng Đấu
- **3 vòng thi đấu cố định**: Vòng loại, Bán kết, Chung kết
- Tạo và quản lý các vòng đấu với thông tin chi tiết
- Theo dõi trạng thái vòng đấu (Draft, Scheduled, Playing, Completed, Archived)

### 👥 Import & Quản Lý Người Chơi
- Import danh sách người chơi từ dữ liệu (họ tên + giới tính)
- Hỗ trợ định dạng giới tính: M (Nam), F (Nữ)
- Quản lý thông tin người chơi và lịch sử thi đấu

### 🎯 Thuật Toán Ghép Cặp Thông Minh
- **Ưu tiên XD (Mixed Doubles)**: Cặp nam nữ được ưu tiên tạo trước
- **MD (Men's Doubles)**: Cặp đôi nam
- **WD (Women's Doubles)**: Cặp đôi nữ
- Đảm bảo mỗi người chơi tham gia đúng N trận đấu theo yêu cầu
- Xử lý người chơi dư thừa bằng cách tạo cặp cùng giới tính

### 🏟️ Lên Lịch Thi Đấu
- **3 sân thi đấu**: Sân 1, Sân 2, Sân 3
- **Khung giờ**: 8:00 - 12:00 hàng ngày
- Phân bổ trận đấu tự động tránh xung đột
- Đảm bảo 1 người không thi đấu 2 trận cùng lúc

### 📊 Bảng Xếp Hạng & Thống Kê
- Theo dõi điểm tích lũy cá nhân
- Filter theo giới tính (Nam/Nữ/Tất cả)
- Sắp xếp theo: Điểm tích lũy → Hiệu số trận đấu → Tên
- Thống kê chi tiết: Số trận, Thắng/Thua/Hòa, Tỷ lệ thắng

### 🔌 API Tích Hợp Bên Ngoài
- Nhận kết quả trận đấu từ hệ thống bên ngoài
- Tự động cập nhật điểm số và thống kê
- RESTful API endpoints cho tích hợp dễ dàng

## Cấu Trúc Dự Án

```
tourmanent-laravel/
├── app/
│   ├── Http/Controllers/          # API Controllers
│   │   ├── TournamentController.php
│   │   ├── MatchController.php
│   │   └── LeaderboardController.php
│   ├── Models/                    # Eloquent Models
│   │   ├── User.php
│   │   ├── Phase.php
│   │   ├── Court.php
│   │   ├── TimeSlot.php
│   │   ├── Pair.php
│   │   ├── BadmintonMatch.php
│   │   └── BadmintonMatchParticipant.php
│   └── Services/                  # Business Logic
│       ├── TournamentService.php
│       ├── PairingAlgorithmService.php
│       ├── SchedulingService.php
│       └── LeaderboardService.php
├── database/
│   ├── migrations/                # Database Schema
│   └── seeders/                   # Sample Data
├── routes/
│   ├── api.php                    # API Routes
│   └── web.php                    # Web Routes
└── docker/                        # Docker Configuration
```

## Cài Đặt & Chạy

### Yêu Cầu Hệ Thống
- PHP 8.2+
- Laravel 12
- PostgreSQL/MySQL
- Docker & Docker Compose

### Bước 1: Clone Repository
```bash
git clone <repository-url>
cd tourmanent-laravel
```

### Bước 2: Cài Đặt Dependencies
```bash
composer install
npm install
```

### Bước 3: Cấu Hình Environment
```bash
cp .env.example .env
php artisan key:generate
```

### Bước 4: Chạy với Docker
```bash
docker-compose up -d
```

### Bước 5: Chạy Migrations & Seeders
```bash
docker exec php_app php artisan migrate:fresh --seed
```

## API Documentation

### 🔧 Admin APIs

#### Quản Lý Vòng Đấu
```http
GET    /api/admin/tournaments              # Danh sách vòng đấu
POST   /api/admin/tournaments              # Tạo vòng đấu mới
GET    /api/admin/tournaments/{id}         # Chi tiết vòng đấu
PATCH  /api/admin/tournaments/{id}/status  # Cập nhật trạng thái
DELETE /api/admin/tournaments/{id}         # Xóa vòng đấu

# Import & Tạo cặp
POST   /api/admin/tournaments/{id}/import-players    # Import người chơi
POST   /api/admin/tournaments/{id}/generate-pairs    # Tạo cặp thi đấu
POST   /api/admin/tournaments/{id}/schedule-matches  # Lên lịch thi đấu
```

#### Quản Lý Trận Đấu
```http
GET    /api/admin/matches              # Danh sách trận đấu
GET    /api/admin/matches/{id}         # Chi tiết trận đấu
POST   /api/admin/matches/{id}/start   # Bắt đầu trận đấu
POST   /api/admin/matches/{id}/cancel  # Hủy trận đấu
POST   /api/admin/matches/{id}/result  # Cập nhật kết quả
```

#### Bảng Xếp Hạng
```http
GET    /api/admin/leaderboard                    # Bảng xếp hạng
GET    /api/admin/leaderboard/players/{id}/stats # Thống kê cá nhân
GET    /api/admin/leaderboard/top-performers     # Top performers
GET    /api/admin/leaderboard/summary            # Tổng quan giải đấu
GET    /api/admin/leaderboard/export             # Export dữ liệu
```

### 🌐 External APIs (Cho Hệ Thống Bên Ngoài)

```http
POST   /api/external/matches/{id}/result        # Cập nhật kết quả trận đấu
GET    /api/external/matches/{id}               # Thông tin trận đấu
GET    /api/external/matches/schedule/today     # Lịch thi đấu hôm nay
```

### 📱 Public APIs

```http
GET    /api/leaderboard           # Bảng xếp hạng công khai
GET    /api/matches/today         # Lịch thi đấu hôm nay
GET    /api/tournaments/summary   # Tổng quan giải đấu
```

## Ví Dụ Sử Dụng

### 1. Tạo Vòng Đấu Mới
```bash
curl -X POST http://localhost/api/admin/tournaments \
  -H "Content-Type: application/json" \
  -d '{
    "type": "vong_loai",
    "name": "Vòng Loại Mùa Xuân 2025",
    "start_at": "2025-03-01 08:00:00",
    "end_at": "2025-03-01 12:00:00",
    "matches_per_player": 3
  }'
```

### 2. Import Người Chơi
```bash
curl -X POST http://localhost/api/admin/tournaments/1/import-players \
  -H "Content-Type: application/json" \
  -d '{
    "players": [
      {"name": "Nguyễn Văn A", "gender": "M"},
      {"name": "Trần Thị B", "gender": "F"},
      {"name": "Lê Văn C", "gender": "M"},
      {"name": "Phạm Thị D", "gender": "F"}
    ]
  }'
```

### 3. Tạo Cặp Thi Đấu
```bash
curl -X POST http://localhost/api/admin/tournaments/1/generate-pairs \
  -H "Content-Type: application/json" \
  -d '{
    "matches_per_player": 3
  }'
```

### 4. Lên Lịch Thi Đấu
```bash
curl -X POST http://localhost/api/admin/tournaments/1/schedule-matches
```

### 5. Cập Nhật Kết Quả (Từ Hệ Thống Bên Ngoài)
```bash
curl -X POST http://localhost/api/external/matches/1/result \
  -H "Content-Type: application/json" \
  -d '{
    "score_team_a": 21,
    "score_team_b": 15,
    "participants": [
      {"user_id": 1},
      {"user_id": 2},
      {"user_id": 3},
      {"user_id": 4}
    ]
  }'
```

### 6. Xem Bảng Xếp Hạng
```bash
# Tất cả người chơi
curl http://localhost/api/leaderboard

# Filter theo giới tính
curl "http://localhost/api/leaderboard?gender=M"
curl "http://localhost/api/leaderboard?gender=F"
```

## Quy Tắc Nghiệp Vụ

### Thuật Toán Ghép Cặp
1. **Bước 1**: Tạo cặp XD (Mixed Doubles) - ưu tiên cao nhất
2. **Bước 2**: Tạo cặp MD (Men's Doubles) từ nam còn lại
3. **Bước 3**: Tạo cặp WD (Women's Doubles) từ nữ còn lại
4. **Bước 4**: Xử lý người dư bằng cặp cùng giới tính
5. **Bước 5**: Đảm bảo mỗi người đúng N trận đấu

### Lên Lịch Thi Đấu
1. Tạo time slots 1 giờ/slot từ 8h-12h cho 3 sân
2. Phân bổ trận đấu tránh xung đột người chơi
3. Tối ưu hóa việc sử dụng sân
4. Kiểm tra và báo cáo xung đột lịch

### Tính Điểm
- **Thắng**: +1 điểm
- **Thua**: +0 điểm  
- **Hòa**: +0 điểm
- Sắp xếp: Điểm cao → Hiệu số trận → Tên A-Z

## Troubleshooting

### Lỗi Thường Gặp

1. **Migration lỗi**: 
   ```bash
   docker exec php_app php artisan migrate:fresh --seed
   ```

2. **API trả về HTML thay vì JSON**:
   - Kiểm tra header `Accept: application/json`
   - Đảm bảo route được định nghĩa đúng

3. **Lỗi database connection**:
   - Kiểm tra `.env` database config
   - Đảm bảo database container đang chạy

4. **Xung đột lịch thi đấu**:
   ```bash
   curl http://localhost/api/admin/tournaments/{id}/validate-schedule
   ```

### Logs & Debug
```bash
# Xem logs Laravel
docker exec php_app tail -f storage/logs/laravel.log

# Xem logs container
docker logs php_app
docker logs nginx_server
```

## Đóng Góp

1. Fork repository
2. Tạo feature branch: `git checkout -b feature/amazing-feature`
3. Commit changes: `git commit -m 'Add amazing feature'`
4. Push to branch: `git push origin feature/amazing-feature`
5. Tạo Pull Request

## License

Dự án được phát hành dưới [MIT License](LICENSE).

## Liên Hệ

- **Tác giả**: Senior Developer Team
- **Email**: support@tournament.dev
- **Website**: https://tournament.dev

---

**Lưu ý**: Đây là hệ thống demo cho mục đích học tập và phát triển. Vui lòng test kỹ trước khi sử dụng trong môi trường production.