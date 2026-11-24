# Tech Gadgets — Xác thực và phân quyền

Mô-đun PHP cho đăng ký, đăng nhập, đăng xuất bằng session, kiểm soát truy cập theo ba vai trò `customer`, `staff`, `admin`, quản lý hồ sơ/sổ địa chỉ và đặt lại mật khẩu.

## Yêu cầu

- PHP 8.0 trở lên, bật extension `pdo_mysql`
- MySQL hoặc MariaDB (có thể dùng XAMPP)

## Cài đặt với XAMPP

1. Khởi động Apache và MySQL.
2. Mở phpMyAdmin, chọn **Import**, chạy file `database/schema.sql`. Nếu đã tạo CSDL từ phiên bản trước, chạy lại file này để tạo thêm các bảng `user_addresses` và `password_reset_tokens`.
3. Nếu thông tin MySQL không phải mặc định XAMPP, đặt biến môi trường `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` cho Apache/PHP.
4. Mở `http://localhost/PHP/Login/`.
5. Đăng ký tài khoản mới và đăng nhập. Tài khoản đăng ký từ giao diện luôn có vai trò Khách hàng.
6. Để gửi email đặt lại mật khẩu, cấu hình SMTP cho PHP `mail()` trong `php.ini` của XAMPP và khởi động lại Apache. Có thể đặt `APP_BASE_URL` (ví dụ `http://localhost/PHP/Login`) để tạo liên kết chính xác khi dùng virtual host hoặc HTTPS.

## Cấp quyền Nhân viên hoặc Admin để kiểm thử

Đăng ký trước tài khoản qua giao diện, sau đó đổi vai trò trực tiếp trong phpMyAdmin hoặc công cụ quản trị CSDL đáng tin cậy:

```sql
USE tech_gadgets;
UPDATE users SET role = 'staff' WHERE email = 'staff@example.com';
UPDATE users SET role = 'admin' WHERE email = 'admin@example.com';
```

Đăng xuất rồi đăng nhập lại để nạp vai trò mới vào session. Không có trường chọn vai trò trong form đăng ký, tránh tự cấp quyền quản trị.

## Kiểm thử phân quyền thủ công

- Khách chưa đăng nhập mở `staff.php` hoặc `admin.php`: bị chuyển về trang đăng nhập.
- Khách hàng đăng nhập mở `staff.php` hoặc `admin.php`: nhận HTTP 403.
- Nhân viên mở `staff.php`: được phép; mở `admin.php`: nhận HTTP 403.
- Admin mở cả hai trang: được phép.
- Gửi thiếu/sai CSRF token ở form đăng ký, đăng nhập hoặc đăng xuất: yêu cầu bị từ chối.
- Từ trang Hồ sơ, cập nhật tên/email, thêm/sửa/xóa địa chỉ và chọn địa chỉ mặc định; không thể thao tác địa chỉ của tài khoản khác.
- Đổi mật khẩu cần mật khẩu hiện tại; liên kết đặt lại mật khẩu chỉ dùng một lần và hết hạn sau 60 phút.

Mật khẩu được lưu bằng `password_hash()`, truy vấn dùng prepared statements, dữ liệu hiển thị được escape trước khi đưa vào HTML. Token đặt lại chỉ lưu dạng băm trong CSDL; phản hồi quên mật khẩu không tiết lộ email có đăng ký hay không.
