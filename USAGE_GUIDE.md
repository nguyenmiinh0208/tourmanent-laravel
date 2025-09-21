# 🏸 Hướng Dẫn Sử Dụng Hệ Thống Quản Lý Giải Cầu Lông

## 📋 Tổng Quan Quy Trình

Hệ thống quản lý giải cầu lông cho phép bạn tổ chức các vòng đấu với quy trình hoàn chỉnh từ tạo vòng đấu, import người chơi, tạo cặp thi đấu, lên lịch, đến theo dõi kết quả và bảng xếp hạng.

## 🚀 Bắt Đầu Sử Dụng

### Bước 1: Truy Cập Hệ Thống
1. Mở trình duyệt và truy cập: `http://localhost/admin`
2. Bạn sẽ thấy Dashboard tổng quan với các thống kê

---

## 🏆 BƯỚC 1: TẠO VÒNG ĐẤU MỚI

### 1.1. Truy cập trang quản lý vòng đấu
- **URL**: `http://localhost/admin/tournaments`
- **Cách khác**: Từ Dashboard, click vào "Quản Lý Vòng Đấu" trong sidebar

### 1.2. Tạo vòng đấu mới
1. Click nút **"Tạo Vòng Đấu Mới"** (màu xanh, góc trên bên phải)
2. Điền thông tin:
   - **Tên Vòng Đấu**: Ví dụ "Giải Cầu Lông Mùa Hè 2025"
   - **Loại Vòng Đấu**: Chọn một trong ba:
     - `Vòng Loại` - Vòng đấu ban đầu
     - `Bán Kết` - Vòng bán kết
     - `Chung Kết` - Vòng chung kết
   - **Số Trận Mỗi Người Chơi**: Nhập số (ví dụ: 3)
   - **Thời Gian Bắt Đầu**: (Tùy chọn)
   - **Thời Gian Kết Thúc**: (Tùy chọn)

3. Click **"Tạo Vòng Đấu"**
4. Hệ thống sẽ hiển thị thông báo thành công và redirect về trang danh sách

---

## 👥 BƯỚC 2: IMPORT NGƯỜI CHƠI

### 2.1. Tìm vòng đấu vừa tạo
- Trong danh sách tournaments, tìm vòng đấu bạn vừa tạo
- Trạng thái sẽ là "Draft" (Nháp)

### 2.2. Import danh sách người chơi
1. Click nút **"Import Players"** trong hàng của vòng đấu
2. Có 2 cách import:

#### Cách 1: Nhập thủ công
- Click tab "Nhập Thủ Công"
- Điền thông tin từng người chơi:
  - **Họ Tên**: Tên đầy đủ
  - **Giới Tính**: M (Nam) hoặc F (Nữ)
- Click "Thêm Người Chơi" để thêm thêm người
- Cần tối thiểu **4 người chơi**

#### Cách 2: Upload file CSV
- Click tab "Upload CSV"
- File CSV cần có format:
  ```
  Họ Tên,Giới Tính
  Nguyễn Văn A,M
  Trần Thị B,F
  Lê Văn C,M
  Phạm Thị D,F
  ```
- Upload file và click "Import"

3. Click **"Import Người Chơi"** để hoàn tất

### 2.3. Kiểm tra kết quả
- Hệ thống sẽ hiển thị thông báo số người chơi đã import thành công
- Trạng thái vòng đấu vẫn là "Draft"

---

## 🎯 BƯỚC 3: TẠO CẶP THI ĐẤU

### 3.1. Tạo cặp tự động
1. Trong danh sách tournaments, click nút **"Tạo Cặp"** 
2. Hệ thống sẽ hiển thị modal xác nhận
3. Xem thông tin:
   - Số người chơi hiện tại
   - Số trận mỗi người sẽ chơi
   - Thuật toán tạo cặp theo thứ tự ưu tiên:
     - **XD** (Mixed Doubles) - Đôi nam nữ
     - **MD** (Men's Doubles) - Đôi nam
     - **WD** (Women's Doubles) - Đôi nữ

4. Click **"Tạo Cặp Thi Đấu"** để xác nhận

### 3.2. Kiểm tra kết quả
- Hệ thống sẽ hiển thị số cặp đã tạo thành công
- Trạng thái có thể chuyển thành "Scheduled" nếu đủ điều kiện

---

## 📅 BƯỚC 4: LÊN LỊCH THI ĐẤU

### 4.1. Lên lịch tự động
1. Click nút **"Lên Lịch"** trong hàng của vòng đấu
2. Hệ thống tự động phân bổ các trận đấu:
   - **3 sân thi đấu**: Sân 1, Sân 2, Sân 3
   - **Thời gian**: 8:00 AM - 12:00 PM
   - **Tránh xung đột**: Một người không thể ở 2 trận cùng lúc

3. Click **"Lên Lịch Thi Đấu"** để xác nhận

### 4.2. Xem lịch thi đấu
- Sau khi lên lịch, trạng thái chuyển thành "Scheduled"
- Có thể xem chi tiết lịch thi đấu trong phần quản lý trận đấu

---

## ⚡ BƯỚC 5: NHẬP KẾT QUẢ TRẬN ĐẤU

### 5.1. Truy cập quản lý trận đấu
- **URL**: `http://localhost/admin/matches`
- **Cách khác**: Click "Quản Lý Trận Đấu" trong sidebar

### 5.2. Nhập kết quả
1. Tìm trận đấu cần nhập kết quả
2. Click **"Nhập Kết Quả"**
3. Điền thông tin:
   - **Tỷ số Cặp A**: Ví dụ "21-15, 21-18"
   - **Tỷ số Cặp B**: Ví dụ "15-21, 18-21"
   - **Người thắng**: Chọn cặp thắng cuộc

4. Click **"Lưu Kết Quả"**

### 5.3. Tự động tính điểm
- Người thắng: **+1 điểm tích lũy**
- Người thua: **+0 điểm**
- Điểm được cập nhật tự động vào bảng xếp hạng

---

## 🏅 BƯỚC 6: XEM BẢNG XẾP HẠNG

### 6.1. Truy cập bảng xếp hạng
- **URL**: `http://localhost/admin/leaderboard`
- **Cách khác**: Click "Bảng Xếp Hạng" trong sidebar

### 6.2. Tính năng bảng xếp hạng
- **Sắp xếp theo**:
  1. Điểm tích lũy (cao nhất trước)
  2. Hiệu số trận đấu (nếu điểm bằng nhau)
  
- **Lọc theo giới tính**:
  - Tất cả
  - Nam (M)
  - Nữ (F)

- **Thông tin hiển thị**:
  - Họ tên
  - Giới tính  
  - Số trận tham gia
  - Điểm tích lũy
  - Hiệu số trận

### 6.3. Export dữ liệu
- Click **"Export CSV"** để tải xuống file Excel
- File chứa toàn bộ dữ liệu bảng xếp hạng

---

## 🔄 QUY TRÌNH HOÀN CHỈNH - TÓM TẮT

```
1. Tạo Vòng Đấu → 2. Import Người Chơi → 3. Tạo Cặp → 4. Lên Lịch → 5. Nhập Kết Quả → 6. Xem BXH
   [Draft]           [Draft]              [Draft]    [Scheduled]   [Playing]       [Completed]
```

## 📊 DASHBOARD VÀ THỐNG KÊ

### Dashboard chính (`/admin`)
- **Tổng số vòng đấu**
- **Tổng số người chơi**
- **Tổng số trận đấu**
- **Điểm trung bình**
- **Hoạt động gần đây**
- **Thông báo hệ thống**

---

## ⚙️ CÀI ĐẶT VÀ TÍNH NĂNG BỔ SUNG

### Quản lý trạng thái vòng đấu
- **Draft**: Nháp, có thể chỉnh sửa
- **Scheduled**: Đã lên lịch
- **Playing**: Đang thi đấu
- **Completed**: Hoàn thành
- **Archived**: Lưu trữ

### API tích hợp
Hệ thống cung cấp các API endpoints để tích hợp với hệ thống bên ngoài:
- `POST /api/admin/tournaments` - Tạo tournament
- `POST /api/admin/tournaments/{id}/players/import` - Import players
- `POST /api/admin/tournaments/{id}/generate-pairs` - Tạo cặp
- `POST /api/admin/tournaments/{id}/schedule` - Lên lịch
- `PUT /api/admin/matches/{id}/result` - Cập nhật kết quả

---

## 🆘 XỬ LÝ LỖI THƯỜNG GẶP

### 1. Không tạo được cặp
- **Nguyên nhân**: Chưa đủ người chơi (tối thiểu 4)
- **Giải pháp**: Import thêm người chơi

### 2. Không lên được lịch
- **Nguyên nhân**: Chưa có cặp thi đấu
- **Giải pháp**: Tạo cặp trước khi lên lịch

### 3. Lỗi khi nhập kết quả
- **Nguyên nhân**: Trận đấu chưa được lên lịch
- **Giải pháp**: Kiểm tra trạng thái vòng đấu

---

## 📞 HỖ TRỢ

Nếu gặp vấn đề, hãy kiểm tra:
1. **Console browser** (F12) để xem lỗi JavaScript
2. **Laravel logs** tại `storage/logs/laravel.log`
3. **Database connection** đảm bảo PostgreSQL đang chạy

**Chúc bạn tổ chức giải đấu thành công! 🏸🏆**
