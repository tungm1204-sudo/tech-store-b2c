<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\StockReceipt;
use App\Models\StockReceiptItem;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\WarrantyRequest;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        WarrantyRequest::truncate();
        Review::truncate();
        StockReceiptItem::truncate();
        StockReceipt::truncate();
        OrderItem::truncate();
        Order::truncate();
        Coupon::truncate();
        ProductVariant::truncate();
        ProductImage::truncate();
        Product::truncate();
        Brand::truncate();
        Category::truncate();
        UserAddress::truncate();
        User::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $pwd = Hash::make('password123');

        // 1. Tài khoản 5 thành viên trong nhóm + Tài khoản demo chuẩn
        $tu = User::create([
            'name' => 'Nguyễn Minh Tự (Nhóm trưởng Kỹ thuật - 20232059)',
            'email' => 'tu@techstore.vn',
            'password' => $pwd,
            'phone' => '0912345678',
            'address' => 'Số 10 Phố Trịnh Văn Bô, Nam Từ Liêm, Hà Nội',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $dien = User::create([
            'name' => 'Điền (Admin - Auth & RBAC)',
            'email' => 'dien@techstore.vn',
            'password' => $pwd,
            'phone' => '0911111111',
            'address' => 'Hà Nội',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $tuyen = User::create([
            'name' => 'Tuyến (Staff - Danh mục & Sản phẩm)',
            'email' => 'tuyen@techstore.vn',
            'password' => $pwd,
            'phone' => '0922222222',
            'address' => 'Hà Nội',
            'role' => 'staff',
            'is_active' => true,
        ]);

        $phuc = User::create([
            'name' => 'Phúc (Staff - Giỏ hàng & Thanh toán)',
            'email' => 'phuc@techstore.vn',
            'password' => $pwd,
            'phone' => '0933333333',
            'address' => 'Hà Nội',
            'role' => 'staff',
            'is_active' => true,
        ]);

        $trunganh = User::create([
            'name' => 'Trung Anh (Staff - Đơn hàng & Bảo hành)',
            'email' => 'trunganh@techstore.vn',
            'password' => $pwd,
            'phone' => '0944444444',
            'address' => 'Hà Nội',
            'role' => 'staff',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Admin Demo TechStore',
            'email' => 'admin@techstore.vn',
            'password' => $pwd,
            'phone' => '0900000001',
            'address' => 'Tầng 5 TechTower, Cầu Giấy, Hà Nội',
            'role' => 'admin',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Staff Demo Vận hành',
            'email' => 'staff@techstore.vn',
            'password' => $pwd,
            'phone' => '0900000002',
            'address' => 'Kho trung tâm Bắc Từ Liêm, Hà Nội',
            'role' => 'staff',
            'is_active' => true,
        ]);

        $cust1 = User::create([
            'name' => 'Nguyễn Văn Khách',
            'email' => 'customer@techstore.vn',
            'password' => $pwd,
            'phone' => '0988123456',
            'address' => 'Số 25 Lê Văn Lương, Thanh Xuân, Hà Nội',
            'role' => 'customer',
            'is_active' => true,
        ]);

        $cust2 = User::create([
            'name' => 'Trần Minh Anh',
            'email' => 'minhanh@gmail.com',
            'password' => $pwd,
            'phone' => '0977654321',
            'address' => '120 Nguyễn Trãi, Quận 1, TP. HCM',
            'role' => 'customer',
            'is_active' => true,
        ]);

        $cust3 = User::create([
            'name' => 'Lê Hoàng Nam',
            'email' => 'hoangnam@gmail.com',
            'password' => $pwd,
            'phone' => '0966554433',
            'address' => '45 Điện Biên Phủ, Thanh Khê, Đà Nẵng',
            'role' => 'customer',
            'is_active' => true,
        ]);

        // Sổ địa chỉ cá nhân (Điền)
        UserAddress::create([
            'user_id' => $cust1->id,
            'recipient_name' => 'Nguyễn Văn Khách',
            'recipient_phone' => '0988123456',
            'province' => 'Hà Nội',
            'district' => 'Thanh Xuân',
            'ward' => 'Nhân Chính',
            'street_address' => 'Số 25 Lê Văn Lương',
            'is_default' => true,
        ]);
        UserAddress::create([
            'user_id' => $cust1->id,
            'recipient_name' => 'Nguyễn Văn Khách (Công ty)',
            'recipient_phone' => '0988123456',
            'province' => 'Hà Nội',
            'district' => 'Cầu Giấy',
            'ward' => 'Dịch Vọng',
            'street_address' => 'Tòa nhà FPT Duy Tân',
            'is_default' => false,
        ]);

        // 2. Danh mục & Thương hiệu (Tuyến)
        $catLaptop = Category::create(['name' => 'Laptop', 'slug' => 'laptop', 'icon' => 'bi-laptop', 'description' => 'Laptop Gaming, Đồ họa và Ultrabook cao cấp']);
        $catPhone  = Category::create(['name' => 'Điện thoại', 'slug' => 'dien-thoai', 'icon' => 'bi-phone', 'description' => 'Smartphone Flagship 5G chính hãng']);
        $catTablet = Category::create(['name' => 'Tablet', 'slug' => 'tablet', 'icon' => 'bi-tablet', 'description' => 'Máy tính bảng phục vụ học tập và sáng tạo']);
        $catGear   = Category::create(['name' => 'Phụ kiện', 'slug' => 'phu-kien', 'icon' => 'bi-headphones', 'description' => 'Tai nghe ANC, Chuột Gaming, Bàn phím cơ']);

        $bApple    = Brand::create(['name' => 'Apple', 'slug' => 'apple', 'country' => 'Mỹ']);
        $bAsus     = Brand::create(['name' => 'ASUS', 'slug' => 'asus', 'country' => 'Đài Loan']);
        $bSamsung  = Brand::create(['name' => 'Samsung', 'slug' => 'samsung', 'country' => 'Hàn Quốc']);
        $bDell     = Brand::create(['name' => 'Dell', 'slug' => 'dell', 'country' => 'Mỹ']);
        $bSony     = Brand::create(['name' => 'Sony', 'slug' => 'sony', 'country' => 'Nhật Bản']);
        $bLenovo   = Brand::create(['name' => 'Lenovo', 'slug' => 'lenovo', 'country' => 'Trung Quốc']);
        $bLogitech = Brand::create(['name' => 'Logitech', 'slug' => 'logitech', 'country' => 'Thụy Sĩ']);
        $bXiaomi   = Brand::create(['name' => 'Xiaomi', 'slug' => 'xiaomi', 'country' => 'Trung Quốc']);

        // 3. 16 Sản phẩm Tech Gadgets chi tiết (Tuyến)
        $catalogData = [
            [
                'category_id' => $catLaptop->id, 'brand_id' => $bApple->id, 'sku' => 'LAP-MBP14-M3',
                'name' => 'MacBook Pro 14 inch M3 Pro 18GB/512GB', 'slug' => 'macbook-pro-14-m3-pro',
                'short_description' => 'Chip Apple M3 Pro 11-core, màn hình Liquid Retina XDR 120Hz, pin tới 18 giờ.',
                'description' => 'MacBook Pro 14 M3 Pro là cỗ máy di động tối thượng cho lập trình viên, designer và dựng phim 4K với hiệu năng GPU tích hợp vượt trội và màn hình XDR siêu sáng.',
                'price' => 49990000, 'sale_price' => 46490000, 'stock_quantity' => 14, 'sold_count' => 28, 'warranty_months' => 12,
                'cpu' => 'Apple M3 Pro 11-core', 'ram' => '18GB', 'rom' => '512GB SSD', 'gpu' => 'Apple 14-core GPU',
                'screen' => '14.2" Liquid Retina XDR 120Hz', 'battery' => '72.4Wh (18 giờ)', 'usage_type' => 'do-hoa',
                'thumbnail' => '/images/products/macbook-pro-14-m3.svg', 'is_featured' => true,
                'variants' => [['18GB / 512GB Space Black', 'BLK-512', 0, 8], ['36GB / 1TB Silver', 'SLV-1TB', 9000000, 6]],
            ],
            [
                'category_id' => $catLaptop->id, 'brand_id' => $bAsus->id, 'sku' => 'LAP-ROG-G16',
                'name' => 'ASUS ROG Strix G16 i7-13650HX RTX 4060', 'slug' => 'asus-rog-strix-g16-rtx4060',
                'short_description' => 'Laptop Gaming hiệu năng đỉnh cao với RTX 4060 8GB, màn 165Hz chuẩn 100% sRGB.',
                'description' => 'Trang bị tản nhiệt 3 quạt ROG Intelligent Cooling kết hợp keo kim loại lỏng, chiến mượt mọi tựa game AAA và render 3D nặng.',
                'price' => 38990000, 'sale_price' => 34990000, 'stock_quantity' => 11, 'sold_count' => 35, 'warranty_months' => 24,
                'cpu' => 'Intel Core i7-13650HX', 'ram' => '16GB', 'rom' => '512GB NVMe', 'gpu' => 'NVIDIA RTX 4060 8GB',
                'screen' => '16.0" FHD+ IPS 165Hz', 'battery' => '90Wh', 'usage_type' => 'gaming',
                'thumbnail' => '/images/products/asus-rog-strix-g16.svg', 'is_featured' => true,
                'variants' => [['16GB DDR5 / 512GB', '16-512', 0, 7], ['32GB DDR5 / 1TB', '32-1TB', 3500000, 4]],
            ],
            [
                'category_id' => $catLaptop->id, 'brand_id' => $bDell->id, 'sku' => 'LAP-XPS13-PLUS',
                'name' => 'Dell XPS 13 Plus 9320 Core i7 OLED 3.5K', 'slug' => 'dell-xps-13-plus-oled',
                'short_description' => 'Thiết kế tương lai mỏng nhẹ 1.24kg, màn hình OLED 3.5K cảm ứng siêu sắc nét.',
                'description' => 'Dell XPS 13 Plus mang đến vẻ đẹp tối giản với bàn phím tràn viền, touchpad vô hình ForcePad và màn hình OLED 100% DCI-P3.',
                'price' => 42990000, 'sale_price' => 39490000, 'stock_quantity' => 3, 'sold_count' => 16, 'warranty_months' => 12,
                'cpu' => 'Intel Core i7-1360P', 'ram' => '16GB', 'rom' => '512GB SSD', 'gpu' => 'Intel Iris Xe Graphics',
                'screen' => '13.4" OLED 3.5K Touch', 'battery' => '55Wh (12 giờ)', 'usage_type' => 'van-phong',
                'thumbnail' => '/images/products/dell-xps-13-plus.svg', 'is_featured' => true,
                'variants' => [['16GB / 512GB Platinum', 'PLT', 0, 3]],
            ],
            [
                'category_id' => $catLaptop->id, 'brand_id' => $bLenovo->id, 'sku' => 'LAP-X1C-G11',
                'name' => 'Lenovo ThinkPad X1 Carbon Gen 11 32GB/1TB', 'slug' => 'lenovo-thinkpad-x1-carbon-g11',
                'short_description' => 'Ultrabook doanh nhân chuẩn quân đội MIL-STD-810H, nhẹ chỉ 1.12kg, RAM 32GB.',
                'description' => 'Bàn phím ThinkPad trứ danh kết hợp khung sợi carbon siêu bền, bảo mật vân tay & ThinkShield toàn diện.',
                'price' => 44500000, 'sale_price' => null, 'stock_quantity' => 8, 'sold_count' => 19, 'warranty_months' => 36,
                'cpu' => 'Intel Core i7-1365U vPro', 'ram' => '32GB', 'rom' => '1TB Gen4 SSD', 'gpu' => 'Intel Iris Xe',
                'screen' => '14.0" 2.8K OLED Anti-glare', 'battery' => '57Wh', 'usage_type' => 'van-phong',
                'thumbnail' => '/images/products/thinkpad-x1-carbon.svg', 'is_featured' => false,
                'variants' => [['32GB / 1TB Matte Black', '32-1TB', 0, 8]],
            ],
            [
                'category_id' => $catLaptop->id, 'brand_id' => $bAsus->id, 'sku' => 'LAP-VIVO-15',
                'name' => 'ASUS Vivobook 15 OLED Ryzen 7 7730U', 'slug' => 'asus-vivobook-15-oled',
                'short_description' => 'Lựa chọn tối ưu cho sinh viên IT & văn phòng: màn OLED 15.6" sắc nét, giá cực tốt.',
                'description' => 'Cân bằng hoàn hảo giữa hiệu năng đa nhân Ryzen 7, dung lượng RAM 16GB và màn hình OLED bảo vệ mắt.',
                'price' => 18990000, 'sale_price' => 16490000, 'stock_quantity' => 24, 'sold_count' => 52, 'warranty_months' => 24,
                'cpu' => 'AMD Ryzen 7 7730U', 'ram' => '16GB', 'rom' => '512GB SSD', 'gpu' => 'AMD Radeon Graphics',
                'screen' => '15.6" FHD OLED 600 nits', 'battery' => '50Wh', 'usage_type' => 'van-phong',
                'thumbnail' => '/images/products/asus-vivobook-15.svg', 'is_featured' => false,
                'variants' => [['16GB / 512GB Indie Black', '16-512', 0, 24]],
            ],
            [
                'category_id' => $catPhone->id, 'brand_id' => $bApple->id, 'sku' => 'PHN-IP16PM-256',
                'name' => 'iPhone 16 Pro Max 256GB Titan Sa Mạc VN/A', 'slug' => 'iphone-16-pro-max-256gb',
                'short_description' => 'Chip A18 Pro 3nm siêu mạnh, nút Camera Control mới, quay 4K 120fps Dolby Vision.',
                'description' => 'Flagship đỉnh cao nhất từ Apple với màn hình Super Retina XDR 6.9 inch viền siêu mỏng và thời lượng pin kỷ lục.',
                'price' => 34990000, 'sale_price' => 32990000, 'stock_quantity' => 18, 'sold_count' => 64, 'warranty_months' => 12,
                'cpu' => 'Apple A18 Pro 6-core', 'ram' => '8GB', 'rom' => '256GB', 'gpu' => 'Apple 6-core GPU',
                'screen' => '6.9" LTPO OLED 120Hz', 'battery' => '4685 mAh', 'usage_type' => 'do-hoa',
                'thumbnail' => '/images/products/iphone-16-pro-max.svg', 'is_featured' => true,
                'variants' => [['256GB Titan Sa Mạc', '256-DES', 0, 10], ['512GB Titan Đen', '512-BLK', 5500000, 8]],
            ],
            [
                'category_id' => $catPhone->id, 'brand_id' => $bSamsung->id, 'sku' => 'PHN-S24U-256',
                'name' => 'Samsung Galaxy S24 Ultra 12GB/256GB', 'slug' => 'samsung-galaxy-s24-ultra',
                'short_description' => 'Quyền năng Galaxy AI tích hợp, bút S-Pen, khung Titan siêu bền & Zoom 100x.',
                'description' => 'Snapdragon 8 Gen 3 for Galaxy kết hợp kính chống phản xạ Corning Gorilla Armor và camera 200MP.',
                'price' => 31990000, 'sale_price' => 27490000, 'stock_quantity' => 15, 'sold_count' => 41, 'warranty_months' => 12,
                'cpu' => 'Snapdragon 8 Gen 3', 'ram' => '12GB', 'rom' => '256GB', 'gpu' => 'Adreno 750',
                'screen' => '6.8" Dynamic AMOLED 2X QHD+', 'battery' => '5000 mAh', 'usage_type' => 'gaming',
                'thumbnail' => '/images/products/galaxy-s24-ultra.svg', 'is_featured' => true,
                'variants' => [['12GB / 256GB Titan Xám', '256-GRY', 0, 9], ['12GB / 512GB Titan Đen', '512-BLK', 3500000, 6]],
            ],
            [
                'category_id' => $catPhone->id, 'brand_id' => $bXiaomi->id, 'sku' => 'PHN-XM14U-512',
                'name' => 'Xiaomi 14 Ultra 16GB/512GB Ống kính Leica', 'slug' => 'xiaomi-14-ultra-leica',
                'short_description' => 'Cảm biến Sony LYT-900 1 inch kết hợp thấu kính Leica Summilux, sạc nhanh 90W.',
                'description' => 'Kiệt tác nhiếp ảnh di động với khẩu độ biến thiên vô cấp và màn hình AMOLED LTPO 3000 nits.',
                'price' => 29990000, 'sale_price' => 26990000, 'stock_quantity' => 4, 'sold_count' => 15, 'warranty_months' => 18,
                'cpu' => 'Snapdragon 8 Gen 3', 'ram' => '16GB', 'rom' => '512GB', 'gpu' => 'Adreno 750',
                'screen' => '6.73" AMOLED LTPO 120Hz', 'battery' => '5000 mAh', 'usage_type' => 'do-hoa',
                'thumbnail' => '/images/products/xiaomi-14-ultra.svg', 'is_featured' => false,
                'variants' => [['16GB / 512GB Đen Da', '512-BLK', 0, 4]],
            ],
            [
                'category_id' => $catPhone->id, 'brand_id' => $bApple->id, 'sku' => 'PHN-IP15-128',
                'name' => 'iPhone 15 128GB Chính Hãng VN/A', 'slug' => 'iphone-15-128gb',
                'short_description' => 'Dynamic Island thông minh, cổng USB-C tiện lợi, camera chính 48MP sắc nét.',
                'description' => 'Sự lựa chọn cân bằng tuyệt vời với chip A16 Bionic và thiết kế mặt lưng kính pha màu sang trọng.',
                'price' => 21990000, 'sale_price' => 19490000, 'stock_quantity' => 22, 'sold_count' => 47, 'warranty_months' => 12,
                'cpu' => 'Apple A16 Bionic', 'ram' => '6GB', 'rom' => '128GB', 'gpu' => 'Apple 5-core GPU',
                'screen' => '6.1" Super Retina XDR', 'battery' => '3349 mAh', 'usage_type' => 'van-phong',
                'thumbnail' => '/images/products/iphone-15.svg', 'is_featured' => false,
                'variants' => [['128GB Hồng', '128-PNK', 0, 12], ['128GB Xanh', '128-BLU', 0, 10]],
            ],
            [
                'category_id' => $catTablet->id, 'brand_id' => $bApple->id, 'sku' => 'TAB-IPADP-M4',
                'name' => 'iPad Pro M4 11 inch Wi-Fi 256GB Ultra Retina XDR', 'slug' => 'ipad-pro-m4-11',
                'short_description' => 'Mỏng kỷ lục 5.3mm, màn hình Tandem OLED hai lớp rực rỡ và chip Apple M4.',
                'description' => 'Thiết bị chuyên dụng cho họa sĩ số, kiến trúc sư và xử lý video ProRes ngay trên máy tính bảng.',
                'price' => 28990000, 'sale_price' => 27490000, 'stock_quantity' => 9, 'sold_count' => 21, 'warranty_months' => 12,
                'cpu' => 'Apple M4 9-core', 'ram' => '8GB', 'rom' => '256GB', 'gpu' => 'Apple 10-core GPU',
                'screen' => '11.0" Tandem OLED 120Hz', 'battery' => '31.29Wh', 'usage_type' => 'do-hoa',
                'thumbnail' => '/images/products/ipad-pro-m4.svg', 'is_featured' => true,
                'variants' => [['256GB Space Black', '256-BLK', 0, 9]],
            ],
            [
                'category_id' => $catTablet->id, 'brand_id' => $bSamsung->id, 'sku' => 'TAB-S9-5G',
                'name' => 'Samsung Galaxy Tab S9 8GB/128GB Kèm S-Pen', 'slug' => 'samsung-galaxy-tab-s9',
                'short_description' => 'Chuẩn kháng nước IP68 đầu tiên trên dòng Tab S, màn Dynamic AMOLED 2X.',
                'description' => 'Trải nghiệm Samsung DeX biến tablet thành trạm làm việc di động kết hợp bút S-Pen độ trễ siêu thấp.',
                'price' => 19990000, 'sale_price' => 16990000, 'stock_quantity' => 12, 'sold_count' => 24, 'warranty_months' => 12,
                'cpu' => 'Snapdragon 8 Gen 2', 'ram' => '8GB', 'rom' => '128GB', 'gpu' => 'Adreno 740',
                'screen' => '11.0" Dynamic AMOLED 2X 120Hz', 'battery' => '8400 mAh', 'usage_type' => 'van-phong',
                'thumbnail' => '/images/products/galaxy-tab-s9.svg', 'is_featured' => false,
                'variants' => [['8GB / 128GB Đen Graphite', '128-BLK', 0, 12]],
            ],
            [
                'category_id' => $catTablet->id, 'brand_id' => $bXiaomi->id, 'sku' => 'TAB-XMPAD6',
                'name' => 'Xiaomi Pad 6 8GB/256GB Màn hình 144Hz', 'slug' => 'xiaomi-pad-6-256gb',
                'short_description' => 'Màn hình WQHD+ 144Hz mượt mà, khung nhôm nguyên khối, 4 loa Dolby Atmos.',
                'description' => 'Chiếc tablet tầm trung mạnh mẽ vượt tầm giá cho giải trí, ghi chú bài giảng và chơi game FPS cao.',
                'price' => 9490000, 'sale_price' => 8490000, 'stock_quantity' => 19, 'sold_count' => 38, 'warranty_months' => 18,
                'cpu' => 'Snapdragon 870', 'ram' => '8GB', 'rom' => '256GB', 'gpu' => 'Adreno 650',
                'screen' => '11.0" WQHD+ IPS 144Hz', 'battery' => '8840 mAh', 'usage_type' => 'gaming',
                'thumbnail' => '/images/products/xiaomi-pad-6.svg', 'is_featured' => false,
                'variants' => [['8GB / 256GB Xám Gravity', '256-GRY', 0, 19]],
            ],
            [
                'category_id' => $catGear->id, 'brand_id' => $bSony->id, 'sku' => 'ACC-SONY-XM5',
                'name' => 'Tai nghe Sony WH-1000XM5 Chống Ồn Chủ Động', 'slug' => 'sony-wh-1000xm5',
                'short_description' => 'Chống ồn hàng đầu thế giới với 8 micro & 2 chip xử lý, pin 30 giờ.',
                'description' => 'Chất âm Hi-Res Audio LDAC tinh tế, thiết kế siêu nhẹ ôm trọn tai, lọc ồn đàm thoại AI chuẩn xác.',
                'price' => 8490000, 'sale_price' => 7290000, 'stock_quantity' => 2, 'sold_count' => 45, 'warranty_months' => 12,
                'cpu' => 'Integrated Processor V1 + QN1', 'ram' => 'N/A', 'rom' => 'LDAC / Bluetooth 5.2', 'gpu' => '30mm Driver',
                'screen' => 'Touch Sensor Earcup', 'battery' => '30 giờ (Sạc nhanh PD)', 'usage_type' => 'van-phong',
                'thumbnail' => '/images/products/sony-wh1000xm5.svg', 'is_featured' => true,
                'variants' => [['Đen Matte Black', 'BLK', 0, 2]],
            ],
            [
                'category_id' => $catGear->id, 'brand_id' => $bLogitech->id, 'sku' => 'ACC-LOGI-GPX2',
                'name' => 'Chuột Gaming Logitech G Pro X Superlight 2', 'slug' => 'logitech-g-pro-x-superlight-2',
                'short_description' => 'Trọng lượng siêu nhẹ 60g, cảm biến HERO 2 32.000 DPI, Switch Lightforce.',
                'description' => 'Vũ khí tối thượng của tuyển thủ Esports với tần số phản hồi không dây Lightspeed 4000Hz.',
                'price' => 3690000, 'sale_price' => 3190000, 'stock_quantity' => 26, 'sold_count' => 58, 'warranty_months' => 24,
                'cpu' => 'HERO 2 Sensor (32K DPI)', 'ram' => 'Onboard Memory', 'rom' => '4KHz Lightspeed', 'gpu' => 'Lightforce Hybrid Switch',
                'screen' => 'Zero-additive PTFE Feet', 'battery' => '95 giờ liên tục', 'usage_type' => 'gaming',
                'thumbnail' => '/images/products/logitech-gpro-x2.svg', 'is_featured' => false,
                'variants' => [['Đen', 'BLK', 0, 14], ['Trắng', 'WHT', 0, 12]],
            ],
            [
                'category_id' => $catGear->id, 'brand_id' => $bApple->id, 'sku' => 'ACC-APP2-USBC',
                'name' => 'Apple AirPods Pro Gen 2 (USB-C MagSafe)', 'slug' => 'airpods-pro-2-usbc',
                'short_description' => 'Chip H2 chống ồn gấp đôi, Âm thanh thích ứng, hộp sạc USB-C kháng bụi IP54.',
                'description' => 'Chuyển đổi mượt mà giữa các thiết bị Apple, hỗ trợ Lossless Audio độ trễ cực thấp.',
                'price' => 6190000, 'sale_price' => 5490000, 'stock_quantity' => 30, 'sold_count' => 72, 'warranty_months' => 12,
                'cpu' => 'Apple H2 Audio Chip', 'ram' => 'N/A', 'rom' => 'Bluetooth 5.3 + U1', 'gpu' => 'Adaptive Transparency',
                'screen' => 'Force Touch Stem', 'battery' => '6 giờ (30 giờ kèm hộp)', 'usage_type' => 'van-phong',
                'thumbnail' => '/images/products/airpods-pro-2.svg', 'is_featured' => false,
                'variants' => [['Trắng MagSafe USB-C', 'WHT', 0, 30]],
            ],
            [
                'category_id' => $catGear->id, 'brand_id' => $bLogitech->id, 'sku' => 'ACC-KEY-Q1PRO',
                'name' => 'Bàn phím cơ Custom Keychron Q1 Pro QMK/VIA Aluminum', 'slug' => 'keychron-q1-pro-wireless',
                'short_description' => 'Khung nhôm CNC nguyên khối Double-Gasket, kết nối Bluetooth 5.1 & Hotswap 5-pin.',
                'description' => 'Tùy biến keymap QMK/VIA không giới hạn cho lập trình viên và game thủ yêu thích cảm giác gõ cao cấp.',
                'price' => 4590000, 'sale_price' => 4190000, 'stock_quantity' => 10, 'sold_count' => 23, 'warranty_months' => 12,
                'cpu' => 'ARM Cortex-M4 QMK MCU', 'ram' => 'K Pro Banana Switch', 'rom' => 'Bluetooth 5.1 / Type-C', 'gpu' => 'Double-Gasket Mount',
                'screen' => 'CNC Rotary Knob', 'battery' => '4000 mAh (100 giờ)', 'usage_type' => 'do-hoa',
                'thumbnail' => '/images/products/keychron-q1-pro.svg', 'is_featured' => false,
                'variants' => [['Carbon Black - Banana Switch', 'BLK', 0, 10]],
            ],
        ];

        $products = [];
        foreach ($catalogData as $pData) {
            $variants = $pData['variants'];
            unset($pData['variants']);
            $pData['specs_json'] = [
                'CPU / Chip' => $pData['cpu'],
                'RAM' => $pData['ram'],
                'Lưu trữ / Kết nối' => $pData['rom'],
                'GPU / Driver' => $pData['gpu'],
                'Màn hình / Điều khiển' => $pData['screen'],
                'Pin / Nguồn' => $pData['battery'],
                'Bảo hành' => $pData['warranty_months'] . ' tháng chính hãng',
            ];
            $p = Product::create($pData);
            ProductImage::create(['product_id' => $p->id, 'image_path' => $pData['thumbnail'], 'is_primary' => true]);
            foreach ($variants as $v) {
                ProductVariant::create([
                    'product_id' => $p->id,
                    'variant_name' => $v[0],
                    'sku_suffix' => $v[1],
                    'price_adjustment' => $v[2],
                    'stock_quantity' => $v[3],
                ]);
            }
            $products[] = $p;
        }

        // 4. Mã giảm giá (Phúc)
        $couponTech10 = Coupon::create([
            'code' => 'TECH10',
            'discount_type' => 'percent',
            'discount_value' => 10,
            'min_order_amount' => 2000000,
            'max_uses' => 200,
            'used_count' => 8,
            'expires_at' => Carbon::now()->addMonths(6),
            'is_active' => true,
        ]);
        Coupon::create([
            'code' => 'GAMING500K',
            'discount_type' => 'fixed',
            'discount_value' => 500000,
            'min_order_amount' => 10000000,
            'max_uses' => 100,
            'used_count' => 3,
            'expires_at' => Carbon::now()->addMonths(4),
            'is_active' => true,
        ]);
        Coupon::create([
            'code' => 'FREESHIP',
            'discount_type' => 'fixed',
            'discount_value' => 30000,
            'min_order_amount' => 500000,
            'max_uses' => 500,
            'used_count' => 12,
            'expires_at' => Carbon::now()->addYear(),
            'is_active' => true,
        ]);

        // 5. Đơn hàng mẫu trải dài nhiều Ngày / Tháng / Năm phục vụ Thống kê Doanh thu & Biểu đồ của Tự
        $orderSeeds = [
            // Năm 2024 & 2025 để biểu đồ Theo Năm có số liệu đối chiếu
            ['2024-10-12 10:00:00', $cust2, 0, 1, 'delivered', 'qr_transfer', 'paid'],
            ['2024-12-05 15:20:00', $cust3, 5, 1, 'delivered', 'cod', 'paid'],
            ['2025-03-18 09:30:00', $cust1, 1, 1, 'delivered', 'qr_transfer', 'paid'],
            ['2025-07-22 14:15:00', $cust2, 6, 1, 'delivered', 'qr_transfer', 'paid'],
            ['2025-11-10 11:45:00', $cust3, 9, 1, 'delivered', 'cod', 'paid'],
            // Các tháng năm 2026
            ['2026-02-14 10:15:00', $cust1, 4, 1, 'delivered', 'cod', 'paid'],
            ['2026-04-08 16:00:00', $cust2, 12, 2, 'delivered', 'qr_transfer', 'paid'],
            ['2026-06-20 11:20:00', $cust3, 10, 1, 'delivered', 'qr_transfer', 'paid'],
            ['2026-08-19 13:40:00', $cust1, 6, 1, 'delivered', 'cod', 'paid'],
            ['2026-09-25 17:10:00', $cust2, 1, 1, 'delivered', 'qr_transfer', 'paid'],
            ['2026-10-15 10:30:00', $cust3, 5, 1, 'delivered', 'qr_transfer', 'paid'],
            // 14 ngày gần nhất của Tháng 11/2026 (cho biểu đồ Theo Ngày & vận hành đơn)
            [Carbon::now()->subDays(12)->format('Y-m-d 09:30:00'), $cust1, 0, 1, 'delivered', 'qr_transfer', 'paid'],
            [Carbon::now()->subDays(10)->format('Y-m-d 14:20:00'), $cust1, 12, 1, 'delivered', 'cod', 'paid'],
            [Carbon::now()->subDays(7)->format('Y-m-d 16:45:00'), $cust2, 13, 2, 'delivered', 'qr_transfer', 'paid'],
            [Carbon::now()->subDays(4)->format('Y-m-d 11:10:00'), $cust1, 14, 1, 'shipping', 'cod', 'unpaid'],
            [Carbon::now()->subDays(2)->format('Y-m-d 15:00:00'), $cust3, 11, 1, 'confirmed', 'qr_transfer', 'paid'],
            [Carbon::now()->subDays(1)->format('Y-m-d 10:25:00'), $cust1, 4, 1, 'pending', 'cod', 'unpaid'],
            [Carbon::now()->subDays(1)->format('Y-m-d 17:40:00'), $cust2, 15, 1, 'cancelled', 'cod', 'refunded'],
        ];

        $deliveredItemsForRma = [];
        foreach ($orderSeeds as $idx => $oSeed) {
            [$dt, $buyer, $pIdx, $qty, $status, $payMethod, $payStatus] = $oSeed;
            $prod = $products[$pIdx];
            $unitPrice = $prod->effective_price;
            $sub = $unitPrice * $qty;
            $disc = ($idx % 3 === 0 && $sub >= 2000000) ? round($sub * 0.1) : 0;
            $ship = ($sub - $disc >= 5000000) ? 0 : 30000;
            $tot = max(0, $sub - $disc + $ship);

            $order = Order::create([
                'order_code' => 'ORD-' . Carbon::parse($dt)->format('Ymd') . '-' . str_pad((string) ($idx + 1), 3, '0', STR_PAD_LEFT),
                'user_id' => $buyer->id,
                'coupon_id' => $disc > 0 ? $couponTech10->id : null,
                'customer_name' => $buyer->name,
                'customer_phone' => $buyer->phone,
                'customer_email' => $buyer->email,
                'shipping_address' => $buyer->address,
                'note' => 'Giao giờ hành chính, kiểm tra seal nguyên vẹn',
                'subtotal' => $sub,
                'discount_amount' => $disc,
                'shipping_fee' => $ship,
                'total_amount' => $tot,
                'payment_method' => $payMethod,
                'payment_status' => $payStatus,
                'status' => $status,
                'delivered_at' => $status === 'delivered' ? Carbon::parse($dt)->addDays(2) : null,
                'updated_by' => $trunganh->id,
                'created_at' => Carbon::parse($dt),
                'updated_at' => Carbon::parse($dt),
            ]);

            $oi = OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $prod->id,
                'variant_id' => $prod->variants()->first()?->id,
                'product_name' => $prod->name,
                'variant_info' => $prod->variants()->first()?->variant_name ?? 'Bản tiêu chuẩn',
                'unit_price' => $unitPrice,
                'quantity' => $qty,
                'line_total' => $sub,
            ]);

            if ($status === 'delivered') {
                $deliveredItemsForRma[] = ['order' => $order, 'item' => $oi, 'buyer' => $buyer, 'product' => $prod];
            }
        }

        // 6. Phiếu Bảo hành / Đổi trả RMA mẫu (Trung Anh)
        if (count($deliveredItemsForRma) >= 3) {
            WarrantyRequest::create([
                'rma_code' => 'RMA-20261110-001',
                'order_id' => $deliveredItemsForRma[0]['order']->id,
                'order_item_id' => $deliveredItemsForRma[0]['item']->id,
                'user_id' => $deliveredItemsForRma[0]['buyer']->id,
                'serial_number' => 'SN-MBP14M3-998812',
                'request_type' => 'warranty',
                'issue_description' => 'Cổng Thunderbolt bên trái nhận sạc chập chờn sau 2 tuần sử dụng.',
                'evidence_url' => 'https://example.com/evidence/macbook-port-video.mp4',
                'status' => 'inspecting',
                'resolution_note' => 'Kỹ thuật viên Trung Anh đã tiếp nhận kiểm tra bo mạch I/O.',
                'handled_by' => $trunganh->id,
            ]);
            WarrantyRequest::create([
                'rma_code' => 'RMA-20261112-002',
                'order_id' => $deliveredItemsForRma[1]['order']->id,
                'order_item_id' => $deliveredItemsForRma[1]['item']->id,
                'user_id' => $deliveredItemsForRma[1]['buyer']->id,
                'serial_number' => 'IMEI-35988109234111',
                'request_type' => 'return',
                'issue_description' => 'Loa ngoài có tiếng rè nhẹ ở mức âm lượng tối đa.',
                'evidence_url' => 'https://example.com/evidence/speaker-check.jpg',
                'status' => 'completed',
                'resolution_note' => 'Đã đổi mới nguyên seal thiết bị khác cho khách hàng.',
                'handled_by' => $trunganh->id,
            ]);
            WarrantyRequest::create([
                'rma_code' => 'RMA-20261115-003',
                'order_id' => $deliveredItemsForRma[2]['order']->id,
                'order_item_id' => $deliveredItemsForRma[2]['item']->id,
                'user_id' => $deliveredItemsForRma[2]['buyer']->id,
                'serial_number' => 'SN-XM5-7712390',
                'request_type' => 'warranty',
                'issue_description' => 'Chế độ chống ồn ANC tự ngắt sau 15 phút.',
                'status' => 'submitted',
            ]);
        }

        // 7. Phiếu Nhập kho kèm lô IMEI/Serial (Trung Anh & Tự)
        $rc1 = StockReceipt::create([
            'receipt_code' => 'STK-20261101-001',
            'supplier_name' => 'Synnex FPT Distribution',
            'imei_batch_note' => 'Lô ASUS ROG Strix G16 (Serial: SN-ROG-001..005) & iPhone 16 Pro Max (IMEI 35988109...)',
            'created_by' => $trunganh->id,
            'approved_by' => $tu->id,
            'total_cost' => 145000000,
            'status' => 'approved',
            'approved_at' => Carbon::now()->subDays(14),
        ]);
        StockReceiptItem::create(['stock_receipt_id' => $rc1->id, 'product_id' => $products[1]->id, 'quantity' => 5, 'unit_cost' => 29000000]);

        $rc2 = StockReceipt::create([
            'receipt_code' => 'STK-20261116-002',
            'supplier_name' => 'Digiworld Chính hãng',
            'imei_batch_note' => 'Lô bổ sung cảnh báo sắp hết hàng: Dell XPS 13 (SN-XPS-101..106) & Sony XM5 (SN-XM5-201..210)',
            'created_by' => $trunganh->id,
            'total_cost' => 255000000,
            'status' => 'pending',
        ]);
        StockReceiptItem::create(['stock_receipt_id' => $rc2->id, 'product_id' => $products[2]->id, 'quantity' => 6, 'unit_cost' => 33000000]);
        StockReceiptItem::create(['stock_receipt_id' => $rc2->id, 'product_id' => $products[12]->id, 'quantity' => 10, 'unit_cost' => 5700000]);

        // 8. Đánh giá & Bình luận phong phú (Mô-đun chuyên sâu của Nguyễn Minh Tự - 20232059)
        $reviewsSeed = [
            [$products[0]->id, $cust1->id, $deliveredItemsForRma[count($deliveredItemsForRma)-2]['order']->id ?? null, 5,
             'Quá xứng đáng cho công việc lập trình & dựng phim', 'Hiệu năng M3 Pro cực mát, pin trâu cả ngày, màn XDR quá đẹp', 'Giá hơi cao so với bản thường',
             'Mình chuyển từ máy Windows sang MacBook Pro M3 Pro và thực sự ấn tượng ở thời lượng pin và độ êm ái khi build Docker + Android Studio.', true, true,
             'TechStore cảm ơn bạn đã tin tưởng lựa chọn MacBook Pro M3 Pro! Chúc bạn làm việc hiệu quả cùng máy mới.'],
            [$products[0]->id, $cust2->id, null, 5,
             'Thiết kế Space Black sang trọng', 'Màu đen nhám ít bám vân tay hơn đời trước, loa hay', 'Nặng hơn MacBook Air chút',
             'Giao hàng nhanh, máy nguyên seal Apple VN chính hãng, kiểm tra bảo hành điện tử đủ 12 tháng.', false, true, null],
            [$products[1]->id, $cust2->id, null, 5,
             'Chiến Cyberpunk 2077 Max Setting mát rượi', 'RTX 4060 cực khỏe, màn hình 165Hz mượt, tản nhiệt tốt', 'Sạc đi kèm hơi nặng khi mang đi học',
             'Đã test render Blender và chơi CS2 ổn định 250+ FPS. Điểm cộng lớn cho đội ngũ nhân viên tư vấn cấu hình.', true, true,
             'Cảm ơn đánh giá chi tiết của bạn! Với dòng ROG Strix, shop tặng kèm balo chống sốc chuyên dụng để tiện di chuyển ạ.'],
            [$products[1]->id, $cust3->id, null, 4,
             'Hiệu năng/Giá thành rất ổn trong tầm 35 triệu', 'Chạy khỏe, bàn phím LED RGB per-key rất đẹp', 'Quạt hơi ồn ở chế độ Turbo',
             'Tổng thể rất hài lòng, bật chế độ Performance là vừa êm vừa mạnh.', true, true, null],
            [$products[5]->id, $cust3->id, null, 5,
             'Camera 4K 120fps quay vlog siêu nét', 'Titan Sa Mạc ngoài đời cực đẹp, pin trụ 2 ngày nhẹ nhàng', 'Phải làm quen nút Camera Control mới',
             'Đóng gói cẩn thận, nhận hàng xong check IMEI kích hoạt bảo hành Apple đầy đủ.', true, true,
             'TechStore cảm ơn anh Hoàng Nam! Shop luôn sẵn hàng phụ kiện ốp MagSafe chính hãng cho 16 Pro Max ạ.'],
            [$products[6]->id, $cust1->id, null, 5,
             'Galaxy AI khoanh vùng tìm kiếm quá tiện', 'Màn hình chống chói ra nắng nhìn rõ nét, bút S-Pen mượt', 'Máy hơi vuông vức cầm 1 tay lâu hơi cấn',
             'Mình dùng để ghi chú cuộc họp và chỉnh ảnh trực tiếp rất nhanh.', true, true, null],
            [$products[12]->id, $cust1->id, null, 5,
             'Chống ồn đỉnh cao khi ngồi quán cafe làm việc', 'Chống ồn ANC lọc sạch tiếng ồn, đeo 4 tiếng không đau tai', 'Không gập gọn vào hộp như đời XM4',
             'Chất âm bass chắc gọn, pin 30 tiếng đúng như quảng cáo.', true, true,
             'Cảm ơn bạn! Dòng WH-1000XM5 đang là tai nghe bán chạy top 1 tại TechStore quý này ạ.'],
            [$products[12]->id, $cust2->id, null, 4,
             'Âm thanh chi tiết, đàm thoại rõ ràng', 'Mic lọc gió tốt khi gọi Zoom', 'Giá hơi cao nếu chưa có voucher',
             'Áp mã TECH10 giảm 10% nên mua được giá rất tốt.', false, true, null],
            [$products[13]->id, $cust2->id, null, 5,
             'Chuột 60g vẩy tâm siêu chuẩn', 'Siêu nhẹ, pin 95 giờ gần 1 tháng mới sạc 1 lần', 'Vẫn hơi mắc so với người mới chơi',
             'Đổi từ dòng G502 sang con này cảm nhận khác biệt rõ rệt khi bắn Valorant.', true, true, null],
            [$products[4]->id, $cust1->id, null, 4,
             'Laptop sinh viên IT ngon bổ rẻ', 'Màn OLED màu chuẩn, SSD NVMe khởi động 5 giây', 'Vỏ nhựa mặt đáy hơi dễ xước nhẹ',
             'Rất hợp túi tiền dưới 17 triệu, đủ chạy VS Code, SQL Server và Laravel mượt mà.', false, true,
             'Cảm ơn phản hồi từ bạn! Vivobook 15 OLED luôn là mẫu quốc dân cho các bạn sinh viên CNTT.'],
            [$products[9]->id, $cust3->id, null, 5,
             'Màn Tandem OLED đẹp không tì vết', 'Siêu mỏng nhẹ, chip M4 xử lý Procreate mượt tuyệt đối', 'Chưa kèm Apple Pencil Pro trong hộp',
             'Mua cùng Voucher giảm giá nên tiết kiệm được kha khá.', true, true, null],
            [$products[14]->id, $cust3->id, null, 3,
             'Chống ồn tốt nhưng pin tai nghe hộp hơi trầy nhẹ khi mở', 'Âm thanh hay, kết nối iPhone nhanh', 'Vỏ nhựa bóng rất dễ xước dăm nếu không đeo ốp ngay',
             'Shop hỗ trợ đổi case bọc bảo vệ miễn phí rất nhiệt tình.', true, true,
             'TechStore ghi nhận góp ý về vỏ bóng của AirPods Pro 2 và đã gửi tặng bạn ốp silicone kèm theo đơn sau ạ!'],
            [$products[2]->id, $cust2->id, null, 2,
             'Touchbar cảm ứng cần thời gian làm quen', 'Thiết kế siêu mỏng đẹp, màn 3.5K xuất sắc', 'Cổng kết nối chỉ có Thunderbolt, cần mua thêm hub chuyển',
             'Bạn nào cần nhiều cổng USB-A truyền thống nên cân nhắc ThinkPad X1 thay vì XPS.', false, true,
             'Cảm ơn góp ý khách quan của bạn! Khách mua Dell XPS tại shop được ưu đãi giảm 30% Hub Type-C chính hãng nhé ạ.'],
            [$products[15]->id, $cust1->id, null, 5,
             'Bàn phím cơ custom cho dân code quá sướng', 'Khung nhôm đầm chắc, âm gõ trầm ấm cực kỳ đã tai', 'Trọng lượng hơn 1.7kg chỉ hợp để cố định trên bàn',
             'Hotswap tiện lợi, kết nối Bluetooth chuyển qua lại giữa MacBook và PC chỉ 1 phím bấm.', false, true, null],
        ];

        foreach ($reviewsSeed as $r) {
            Review::create([
                'product_id' => $r[0],
                'user_id' => $r[1],
                'order_id' => $r[2],
                'rating' => $r[3],
                'title' => $r[4],
                'pros' => $r[5],
                'cons' => $r[6],
                'comment' => $r[7],
                'is_verified_purchase' => $r[8],
                'is_approved' => $r[9],
                'admin_reply' => $r[10],
                'replied_at' => $r[10] ? Carbon::now()->subDays(1) : null,
                'replied_by' => $r[10] ? $tu->id : null,
            ]);
        }
    }
}
