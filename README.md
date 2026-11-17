# TechStore B2C - E-Commerce Website Selling Tech Gadgets 💻📱🎧

> **Môn học**: Phát triển ứng dụng Web / Đồ án chuyên ngành CNTT  
> **Đề tài**: Development of a B2C E-Commerce Website selling Tech Gadgets (Hệ thống thương mại điện tử B2C chuyên kinh doanh thiết bị công nghệ & phụ kiện cao cấp)  
> **Nhóm thực hiện**: Nhóm 5  
> **Thành viên phụ trách Git & CSDL**: Nguyễn Minh Tự (MSSV: 20232059)  
> **Repository GitHub**: [https://github.com/tungm1204-sudo/tech-store-b2c](https://github.com/tungm1204-sudo/tech-store-b2c)

---

## 👥 Danh Sách Thành Viên & Phân Công Trách Nhiệm

| STT | Thành Viên | Vai Trò | Nhánh Git | Module Phụ Trách Chính |
|:---:|:---|:---|:---|:---|
| **1** | **Nguyễn Minh Tự** | CSDL, Kiến trúc, Review & Analytics | `feature/tu-review-analytics` | Khởi tạo CSDL 16 bảng 3NF, Base Skeleton, Đánh giá sản phẩm (Verified Purchase), Dashboard Admin Doanh thu (Chart.js + CSV Export), AI Gadget Advisor. |
| **2** | **Điền** | Thành viên Backend/Auth | `feature/dien-auth-rbac` | Xác thực người dùng (Auth), Phân quyền RBAC 3 vai trò (`admin`, `staff`, `customer`), Quản lý tài khoản & Sổ địa chỉ giao hàng (`user_addresses`). |
| **3** | **Tuyến** | Thành viên Backend/Catalog | `feature/tuyen-catalog` | Danh mục thiết bị (`categories`), Thương hiệu (`brands`), Sản phẩm & Biến thể RAM/SSD/Màu sắc (`product_variants`), Bộ lọc & Tìm kiếm kỹ thuật. |
| **4** | **Phúc** | Thành viên Backend/Checkout | `feature/phuc-cart-checkout` | Giỏ hàng phiên/database (`carts`, `cart_items`), Áp mã giảm giá (`coupons`), Thanh toán QR Động tự động (VietQR Banking API), Khóa bi quan kho hàng (`lockForUpdate`). |
| **5** | **Trung Anh** | Thành viên Backend/Order | `feature/trunganh-order-rma` | Máy trạng thái đơn hàng 6 bước (`pending` $\rightarrow$ `processing` $\rightarrow$ `shipping` $\rightarrow$ `completed`), Quản lý Đổi trả / Bảo hành (`warranty_requests`), Nhập kho (`stock_receipts`). |

---

## 🛠️ Công Nghệ Sử Dụng

- **Backend**: PHP 8.2+ / Laravel 10 (Eloquent ORM, Migration, Seeder, State Machine, Transaction Lock)
- **Database**: MySQL 8.0 (16 bảng thiết kế chuẩn hóa 3NF, Foreign Keys, Indexes, Cascades)
- **Frontend**: Blade Template Engine, Bootstrap 5.3, Bootstrap Icons, Chart.js, SweetAlert2
- **Thanh toán & Tích hợp**: VietQR PayOS QR động, AI Gadget Advisor (Gemini heuristic recommendation)
- **Kiểm thử & Đảm bảo chất lượng**:
  - **E2E Automation**: PowerShell automated HTTP E2E suite (`test_techstore_e2e.ps1` - 27/27 test cases passed)
  - **Tải & Hiệu năng**: Apache JMeter 5.6.3 (`tests/jmeter/TechStore_LoadTest.jmx`)
  - **Bảo mật**: OWASP ZAP (Anti-CSRF, XSS filter, SQL Injection immunity, Security Headers `X-Frame-Options`, `X-Content-Type-Options`, `CSP`)

---

## 🚀 Hướng Dẫn Cài Đặt Dành Cho Thành Viên (Quickstart)

### 1. Yêu Cầu Môi Trường
- Máy tính đã cài đặt **XAMPP** (Apache + MySQL) hoặc PHP >= 8.1, Composer >= 2.0.
- Khởi động **Apache** và **MySQL** trong XAMPP Control Panel.

### 2. Clone Dự Án Về Máy
Mở PowerShell hoặc Git Bash tại thư mục làm việc (ví dụ `E:\IT\XAMPP\htdocs` hoặc thư mục dự án của bạn):

```bash
git clone https://github.com/tungm1204-sudo/tech-store-b2c.git
cd tech-store-b2c
```

### 3. Cài Đặt Thư Viện PHP & Cấu Hình Môi Trường
```bash
# Cài đặt dependency qua Composer
composer install

# Tạo file môi trường .env từ bản mẫu
copy .env.example .env

# Sinh APP_KEY
php artisan key:generate
```

### 4. Cấu Hình Cơ Sở Dữ Liệu
Mở file `.env` và kiểm tra cấu hình database:
```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tech_store_db
DB_USERNAME=root
DB_PASSWORD=
```
Tạo database rỗng tên `tech_store_db` trong phpMyAdmin (`http://localhost/phpmyadmin`) hoặc qua MySQL CLI:
```sql
CREATE DATABASE IF NOT EXISTS `tech_store_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5. Chạy Migration & Nạp Dữ Liệu Mẫu (Seeders)
```bash
php artisan migrate:fresh --seed
```
*(Lệnh này sẽ tự động khởi tạo 16 bảng chuẩn hóa và nạp sẵn 10 tài khoản, 16 sản phẩm công nghệ hot, 18 đơn hàng thực tế và 14 đánh giá)*

### 6. Khởi Chạy Local Server
```bash
php artisan serve
```
Truy cập hệ thống tại: **`http://127.0.0.1:8000`**

---

## 🔑 Tài Khoản Đăng Nhập Mẫu

| Vai Trò | Email | Mật Khẩu | Quyền Hạn |
|:---|:---|:---|:---|
| **Quản trị viên (Admin)** | `admin@techstore.vn` | `password` | Toàn quyền Quản trị, Xem Báo cáo Analytics, Duyệt bảo hành |
| **Nhân viên (Staff)** | `staff@techstore.vn` | `password` | Quản lý Đơn hàng, Nhập kho, Đổi trạng thái giao hàng |
| **Khách hàng 1** | `customer@techstore.vn` | `password` | Mua sắm, Đặt hàng, VietQR, Đánh giá sản phẩm đã mua |
| **Khách hàng 2 (Tự)** | `tu.nm@techstore.vn` | `password` | Mua sắm, Lịch sử đơn hàng, Gửi yêu cầu RMA bảo hành |

---

## 🌿 Quy Trình Làm Việc Với Git (Git Workflow)

Mỗi thành viên làm việc độc lập trên nhánh (branch) riêng được phân công:

```bash
# 1. Chuyển sang nhánh của mình (Ví dụ bạn Điền):
git checkout feature/dien-auth-rbac

# 2. Kéo code mới nhất từ main về:
git pull origin main

# 3. Tiến hành viết code, sửa giao diện hoặc logic...

# 4. Lưu và commit công việc:
git add .
git commit -m "feat(auth): hoan thien trang dang ky va validator sdt viet nam"

# 5. Đẩy nhánh cá nhân lên GitHub:
git push origin feature/dien-auth-rbac

# 6. Lên GitHub tạo Pull Request (PR) hợp nhất vào nhánh `main` để Người quản trị Repo (Tự) kiểm duyệt (Code Review & Merge).
```

---

## 📂 Cấu Trúc Thư Mục Dự Án Chính

```text
tech-store/
├── app/
│   ├── Http/Controllers/
│   │   ├── AuthController.php          # Điền: Đăng nhập/Đăng ký/RBAC
│   │   ├── CatalogController.php       # Tuyến: Danh mục/Sản phẩm/Bộ lọc
│   │   ├── CartController.php          # Phúc: Giỏ hàng/Mã giảm giá
│   │   ├── CheckoutController.php      # Phúc: Thanh toán VietQR & Kho hàng
│   │   ├── OrderController.php         # Trung Anh: Trạng thái Đơn hàng
│   │   ├── RMAController.php           # Trung Anh: Bảo hành / Đổi trả
│   │   ├── ReviewController.php        # Tự: Đánh giá & Verified Purchase
│   │   ├── Admin/AnalyticsController.php # Tự: Dashboard Doanh thu & CSV
│   │   └── Api/V1/...                  # RESTful API cho ứng dụng di động
│   └── Models/                         # 16 Eloquent Models (User, Order, Product...)
├── database/
│   ├── migrations/                     # 16 Files Migration tạo bảng chuẩn hóa 3NF
│   ├── seeders/DatabaseSeeder.php      # Nạp dữ liệu giả lập thực tế
│   └── tech_store_db_backup.sql        # Bản sao lưu SQL đầy đủ (130 KB)
├── resources/views/                    # Giao diện Blade Templates hiện đại
│   ├── layouts/app.blade.php           # Layout khách hàng (Responsive Navbar, Cart)
│   ├── layouts/admin.blade.php         # Layout quản trị (Sidebar, Stats)
│   ├── catalog/                        # Giao diện sản phẩm & chi tiết
│   ├── checkout/                       # Giao diện thanh toán & VietQR
│   ├── orders/                         # Quản lý & tra cứu đơn hàng
│   └── admin/                          # Báo cáo biểu đồ doanh thu & sản phẩm
├── tests/                              # Bộ kiểm thử tự động
│   ├── jmeter/                         # Kịch bản Apache JMeter (.jmx & benchmark)
│   └── zap/                            # Báo cáo quét lỗ hổng OWASP ZAP
├── HUONG_DAN_NHOM_CHI_TIET.md          # Sổ tay cầm tay chỉ việc cho từng thành viên
└── DEPLOYMENT_GUIDE.md                 # Hướng dẫn triển khai Production
```

---

## 📜 Tài Liệu Báo Cáo Đi Kèm

Các tài liệu chính thức theo quy chuẩn môn học được đính kèm tại thư mục gốc:
1. **`Bao_Cao_BTL_TechStore_B2C_Nhom5_NguyenMinhTu.docx`**: Bản báo cáo đầy đủ 6 chương (5.05 MB) gồm đầy đủ sơ đồ CSDL, Use Case, Sequence, Kiến trúc và kiểm thử JMeter/ZAP.
2. **`Huong_Dan_Cam_Tay_Chi_Viec_Cho_Ca_Nhom_TechStore_B2C.docx`**: Hướng dẫn hỏi đáp bảo vệ đồ án và giải thích code cho từng người.
3. **`Huong_Dan_Bat_Dau_Trien_Khai_Tu_Con_So_0_10_Ngay_TechStore_B2C.docx`**: Lịch trình 10 ngày hành động chi tiết từng giờ.

---
*© 2026 Nhóm 5 - Mọi đóng góp xin liên hệ Nguyễn Minh Tự (nguyenminhtu1242005@gmail.com).*
