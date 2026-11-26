# Module Giỏ hàng & Thanh toán — Phúc (`feature/phuc-cart-checkout`)

Tài liệu bàn giao nhanh cho các thành viên trong nhóm (đặc biệt lúc hợp nhất nhánh).

## 1. Phạm vi phụ trách

| Chức năng | Chi tiết |
|---|---|
| Giỏ hàng | Thêm / sửa số lượng / xóa; khách vãng lai lưu bằng `session_id`, gộp vào giỏ tài khoản khi đăng nhập. |
| Mã giảm giá | Áp dụng / bỏ mã; kiểm tra hoạt động, hạn, lượt dùng, đơn tối thiểu; CRUD admin (`admin.coupons.*`). |
| Thanh toán | COD hoặc chuyển khoản VietQR động (Quick Link theo số tiền + mã đơn). |
| Đặt hàng | Tạo `orders` + `order_items` trong một transaction, khóa tồn kho (`lockForUpdate`), trừ kho và `sold_count`. |
| Giao diện | `cart.index`, `checkout.index`, `checkout.payment`, `checkout.result`. |

## 2. File chính

```
app/Http/Controllers/CartController.php
app/Http/Controllers/CheckoutController.php
app/Http/Controllers/Admin/CouponController.php
app/Services/CartService.php          # giá, giỏ, coupon, phí ship, kiểm tra tồn
app/Services/VietQrService.php        # sinh URL QR
app/Services/OrderInventoryService.php# hoàn kho khi hủy đơn (dùng chung với Trung Anh)
app/Models/{Cart,CartItem,Coupon}.php
resources/views/cart/**, resources/views/checkout/**, resources/views/admin/coupons/**
tests/Feature/CartCheckoutTest.php     # 23 test, SQLite in-memory
```

## 3. Routes (đều nằm trong vùng "3. PHÚC" của `routes/web.php`)

```
GET    /cart                         cart.index
POST   /cart/items                   cart.add
PATCH  /cart/items/{item}            cart.update
DELETE /cart/items/{item}            cart.remove
POST   /cart/coupon                  cart.coupon.apply
DELETE /cart/coupon                  cart.coupon.remove
GET    /checkout                     checkout.index
POST   /checkout                     checkout.store   (throttle 10/phút)
GET    /checkout/orders/{order}/payment  checkout.payment
GET    /checkout/orders/{order}/result   checkout.result
POST   /checkout/orders/{order}/mock-paid (chỉ APP_ENV=local)
GET|POST|... /admin/coupons/*        admin.coupons.*  (role:admin)
```

## 4. Luồng & quy tắc nghiệp vụ

1. `POST /cart/items`: kiểm tra sản phẩm active, biến thể thuộc sản phẩm, tổng số lượng trong giỏ **không vượt quá tổng tồn kho** (`products.stock_quantity`) và không vượt tồn biến thể.
2. `POST /cart/coupon`: coupon hợp lệ mới lưu mã vào session; `CartService::summary()` tính lại discount/phí ship nhiều lần trong quá trình xem giỏ.
3. `POST /checkout`:
   - `DB::transaction` + `lockForUpdate` trên giỏ, sản phẩm, biến thể, coupon.
   - Kiểm tra lại giá, tồn, coupon (kể cả mã trong session đã hết hạn → bỏ qua thay vì chặn đơn).
   - Đơn mới: `status=pending`, `payment_status=unpaid`, `stock_deducted_at=now()`.
   - Trừ `stock_quantity` của product + variant, cộng `sold_count`, tăng `used_count` coupon, xóa item khỏi giỏ.
   - Freeship khi **giá sau giảm ≥ 5.000.000₫**, nếu không thì phí 30.000₫ (một nguồn định nghĩa tại `CartService::shippingFee()`).
4. Hủy đơn (Trung Anh): `OrderObserver` gọi `OrderInventoryService` → hoàn kho, trừ lại `sold_count` và `used_count` **đúng một lần** nhờ `stock_restored_at`.

## 5. Điểm tích hợp với nhóm

- **Tuyến**: form thêm giỏ cần gửi `product_id`, `variant_id` (bắt buộc nếu product có biến thể), `quantity`. Giá = `effective_price + price_adjustment`.
- **Điền**: checkout chỉ cho `role=customer`, `is_active=true`; lấy địa chỉ từ `user->addresses`.
- **Trung Anh**: dùng chung `Order`/`OrderItem`; không tự trừ kho ở module order để tránh trừ đôi.
- **Tự**: layout dùng `CartService::getCount()` qua View Composer; result page tự ẩn link "Lịch sử đơn hàng" nếu route `orders.index` chưa tồn tại.

## 6. Kiểm thử

```bash
php artisan test --filter=CartCheckoutTest
```

Test dùng SQLite in-memory (xem `refreshApplication()` trong test) nên **không xóa MySQL thật**. các test khác trong dự án (`ModelsAuditTest`, `ExampleTest`) cần database MySQL `tech_store_test` đã migrate + seed.

## 7. Giới hạn đã biết

VietQR hiện là Quick Link động: mã QR chứa đúng số tiền và nội dung `TECHSTORE <order_code>`. Việc **đối soát tiền về tài khoản** chưa tự động; nút "Mô phỏng xác nhận" chỉ có trong `local`. Khi lên production cần webhook/API ngân hàng hoặc đơn vị thanh toán trung gian.
