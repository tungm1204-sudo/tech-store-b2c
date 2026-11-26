# Hướng Dẫn Cài Đặt, Quản Lý Mã Nguồn Git & Triển Khai Hệ Thống TechStore B2C (Local / Hosting)

**Phụ trách chính:** Nguyễn Minh Tự (MSSV: 20232059) — Thành viên Nhóm 5 Sinh viên  
**Đề tài:** Development of a B2C E-Commerce Website selling Tech Gadgets (`tech-store`)

---

## 1. Yêu cầu Hệ thống (Server & Localhost)
- **PHP:** `>= 8.0.2` (Khuyến nghị PHP `8.0.28` / `8.1+`) bật các extension: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `gd`, `zip`.
- **Database:** MySQL `5.7+` hoặc MariaDB `10.4+` (XAMPP InnoDB Engine).
- **Web Server:** Apache 2.4 (`mod_rewrite` bật) hoặc Nginx / Laravel Artisan Serve.
- **Công cụ kiểm thử:** Apache JMeter `5.6.3` & OWASP ZAP.

---

## 2. Quy trình Cài đặt trên Localhost (XAMPP / Windows)
1. **Sao chép mã nguồn** vào `E:\IT\XAMPP\htdocs\tech-store`.
2. **Khởi động MySQL** từ XAMPP Control Panel hoặc CLI:
   ```powershell
   E:\IT\XAMPP\mysql\bin\mysqld.exe --defaults-file="E:\IT\XAMPP\mysql\my.ini" --standalone
   ```
3. **Tạo CSDL & Khôi phục dữ liệu mẫu:**
   - Cách A (Từ file backup `.sql` có sẵn):
     ```powershell
     E:\IT\XAMPP\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS tech_store_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
     E:\IT\XAMPP\mysql\bin\mysql.exe -u root tech_store_db < database\tech_store_db_backup.sql
     ```
   - Cách B (Từ Laravel Migrations + Seeder):
     ```powershell
     php artisan migrate:fresh --seed
     ```
4. **Cấu hình tệp `.env`:**
   ```env
   APP_NAME="TechStore B2C"
   APP_ENV=local
   APP_DEBUG=true
   APP_URL=http://127.0.0.1:8000
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=tech_store_db
   DB_USERNAME=root
   DB_PASSWORD=
   ```
5. **Khởi chạy Website:**
   ```powershell
   E:\IT\XAMPP\php\php.exe artisan serve --host=127.0.0.1 --port=8000
   ```
   Truy cập: `http://127.0.0.1:8000` (Hoặc qua Apache VirtualHost `http://localhost/tech-store/public`).

---

## 3. Tài khoản Kiểm thử (Mật khẩu chung: `password123`)
| Vai trò | Email | Quyền hạn |
| :--- | :--- | :--- |
| **Admin (Trưởng kỹ thuật)** | `tu@techstore.vn` | Toàn quyền hệ thống, Analytics, Duyệt bình luận, Phê duyệt nhập kho |
| **Admin (Auth & RBAC)** | `dien@techstore.vn` | Quản lý người dùng & phân quyền RBAC |
| **Staff (Vận hành/Kho)** | `trunganh@techstore.vn` | Duyệt đơn hàng 6 trạng thái, Thẩm định RMA, Lập phiếu nhập kho |
| **Customer (Khách hàng)** | `customer@techstore.vn` | Mua hàng QR/COD, Sổ địa chỉ, Đánh giá 5*, Gửi yêu cầu RMA |

---

## 4. Hướng dẫn Triển khai lên Hosting (cPanel / Shared Hosting / VPS)
1. **Đóng gói mã nguồn:** Nén toàn bộ thư mục `tech-store` thành `.zip` (bao gồm `vendor/` và `public/`).
2. **Import CSDL:** Vào `phpMyAdmin` trên cPanel $\rightarrow$ Tạo Database & User $\rightarrow$ Import file `database/tech_store_db_backup.sql`.
3. **Upload & Giải nén:** Đẩy tệp `.zip` lên thư mục gốc Hosting, trỏ Document Root của tên miền (hoặc `.htaccess` tại root) vào thư mục `public/`.
4. **Cấu hình Production trong `.env`:** Đặt `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://ten-mien-cua-ban.com`, cập nhật thông tin `DB_*`, bật SSL/HTTPS (Let's Encrypt) và phân quyền `chmod -R 775 storage bootstrap/cache`.
5. **Tối ưu hiệu năng Production:**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

---

## 5. Quy trình Quản lý Mã nguồn Git / GitHub (Nhóm 5 Thành viên)
- Nhánh chính ổn định: `main` (chỉ merge sau khi chạy pass `test_techstore_e2e.ps1`).
- Nhánh phát triển theo phân hệ:
  - `feature/dien-auth-rbac` (AuthController, ProfileController, RoleMiddleware, UserAddresses)
  - `feature/tuyen-catalog-compare` (CatalogController, CompareController, Products, Categories, Brands)
  - `feature/phuc-cart-checkout` (CartController, CheckoutController, Coupons, OrderService)
  - `feature/trunganh-orders-rma` (OrderStateService, WarrantyController, StockReceipts)
  - `feature/tu-reviews-analytics-api` (ReviewController, AdminReviewController, AdminAnalyticsController, Charts, CSV Export, REST API v1, JMeter/ZAP)

---

## 6. Kiểm thử Hệ thống & Bảo mật
- **Kiểm thử Tự động E2E (Chức năng, Dữ liệu sai & Chặn phân quyền RBAC):**
  ```powershell
  powershell -ExecutionPolicy Bypass -File .\test_techstore_e2e.ps1
  ```
- **Kiểm thử Chịu tải Apache JMeter 5.6.3 (CLI Non-GUI):**
  ```powershell
  E:\IT\XAMPP\htdocs\apache-jmeter-5.6.3\apache-jmeter-5.6.3\bin\jmeter.bat -n -t tests\jmeter\TechStore_LoadTest.jmx -l tests\jmeter\results.jtl
  ```
- **Kiểm thử Bảo mật OWASP ZAP:** Quét trên `http://127.0.0.1:8000` (Spider + Ajax Spider + Active Scan), xác minh đầy đủ HTTP Security Headers (`X-Frame-Options`, `X-Content-Type-Options`, `Content-Security-Policy`), CSRF token và chống SQL Injection.
