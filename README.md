# 🏢 VLXD KAT - Website Bán Vật Liệu Xây Dựng

Website thương mại điện tử chuyên cung cấp vật liệu xây dựng được xây dựng với PHP, MySQL, Tailwind CSS và Font Awesome.

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-3.x-06B6D4?style=for-the-badge&logo=tailwind-css&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![Google OAuth](https://img.shields.io/badge/Google_OAuth-2.0-4285F4?style=for-the-badge&logo=google&logoColor=white)

## 🚀 Công nghệ sử dụng

| Công nghệ | Phiên bản | Mục đích |
|-----------|-----------|----------|
| ![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat&logo=php&logoColor=white) | 8.0+ | Backend server-side |
| ![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=flat&logo=mysql&logoColor=white) | 5.7+ / MariaDB | Cơ sở dữ liệu |
| ![TailwindCSS](https://img.shields.io/badge/TailwindCSS-06B6D4?style=flat&logo=tailwind-css&logoColor=white) | 3.x | Frontend styling (CDN) |
| ![Font Awesome](https://img.shields.io/badge/Font_Awesome-339AF0?style=flat&logo=fontawesome&logoColor=white) | 6.4 | Icon library |
| ![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=flat&logo=javascript&logoColor=black) | ES6+ | Tương tác giao diện |
| ![Google](https://img.shields.io/badge/Google_OAuth-4285F4?style=flat&logo=google&logoColor=white) | 2.0 | Đăng nhập với Google |
| ![XAMPP](https://img.shields.io/badge/XAMPP-FB7A24?style=flat&logo=xampp&logoColor=white) | Latest | Môi trường phát triển |

## 📦 Cài đặt

### Yêu cầu hệ thống
- XAMPP (Apache + MySQL + PHP 8.0+)
- Git
- Trình duyệt hiện đại (Chrome, Firefox, Edge)
- Google Cloud Console (cho OAuth - tùy chọn)

### Các bước cài đặt

1. **Clone repository:**
```bash
git clone https://github.com/KAT1140/DU_AN_MA_NGON_MO_WEBSITE_BAN_VLSD.git
cd DU_AN_MA_NGON_MO_WEBSITE_BAN_VLSD
```

2. **Tạo cơ sở dữ liệu:**
   - Mở phpMyAdmin: `http://localhost/phpmyadmin`
   - Tạo database mới: `vlxd_store1` (CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci)
   - Import file `vlxd_storemoi.sql`

3. **Cấu hình:**
   - Kiểm tra file `config.php` - database name là `vlxd_store1`
   - Cập nhật thông tin Google OAuth (nếu dùng):
     - `$CLIENT_ID` - Google Client ID
     - `$REDIRECT_URI` - Callback URL
     - `$DEV_ADMIN_EMAIL` - Email admin mặc định

4. **Chạy ứng dụng:**
   - Khởi động XAMPP (Apache + MySQL)
   - Truy cập: `http://localhost/vlxd`

## 📄 Cấu trúc file chính

### Frontend Pages
- `index.php` - Trang chủ, hiển thị sản phẩm nổi bật
- `products.php` - Trang danh sách sản phẩm với bộ lọc danh mục
- `product_detail.php` - Chi tiết sản phẩm với đánh giá
- `cart.php` - Giỏ hàng
- `checkout.php` - Trang thanh toán với địa chỉ đã lưu
- `order_success.php` - Trang thành công sau khi đặt hàng
- `my_orders.php` - Lịch sử đơn hàng, theo dõi, hủy đơn, đánh giá
- `addresses.php` - Quản lý địa chỉ đã lưu
- `profile.php` - Trang hồ sơ người dùng
- `login.php` - Trang đăng nhập (email/password + Google OAuth)
- `dangki.php` - Trang đăng ký tài khoản
- `forgot_password.php` - Quên mật khẩu
- `reset_password.php` - Đặt lại mật khẩu

### Payment Pages
- `banking_payment.php` - Thanh toán chuyển khoản ngân hàng (MB Bank)
- `momo_qr_display.php` - Thanh toán MoMo (QR code cá nhân)
- `momo_callback.php` - Xử lý callback từ MoMo
- `momo_return.php` - Trang quay lại sau thanh toán MoMo
- `payment_status.php` - Kiểm tra trạng thái thanh toán

### Backend Processing
- `config.php` - Cấu hình database & tự động tạo bảng
- `check.php` - Xử lý đăng nhập với prepared statements
- `callback.php` - Callback Google OAuth
- `logout.php` - Xử lý đăng xuất
- `add_to_cart.php` - Thêm sản phẩm vào giỏ
- `update_cart.php` - Cập nhật số lượng trong giỏ
- `remove_from_cart.php` - Xóa sản phẩm khỏi giỏ
- `process_order.php` - Xử lý đơn hàng & routing thanh toán
- `cancel_order_handler.php` - Xử lý hủy đơn hàng
- `get_order_details.php` - API lấy chi tiết đơn hàng
- `get_order_items_for_review.php` - Lấy sản phẩm để đánh giá
- `submit_review.php` - Xử lý đánh giá sản phẩm
- `MoMoPaymentHandler.php` - Xử lý thanh toán MoMo
- `momo_config.php` - Cấu hình MoMo
- `momo_personal_config.php` - Cấu hình MoMo cá nhân
- `email_config.php` - Cấu hình email

### Inventory Management
- `inventory_functions.php` - Các hàm quản lý tồn kho
- `inventory_management.php` - Trang quản lý tồn kho
- `inventory_report.php` - Báo cáo tồn kho
- `inventory_widget.php` - Widget hiển thị tồn kho
- `product_inventory_detail.php` - Chi tiết tồn kho sản phẩm

### Admin Pages
- `admin.php` - Dashboard quản trị & quản lý người dùng
- `admin_products.php` - Quản lý sản phẩm (CRUD)
- `admin_orders.php` - Quản lý đơn hàng, cập nhật trạng thái
- `admin_suppliers.php` - Quản lý nhà phân phối
- `admin_reviews.php` - Quản lý đánh giá sản phẩm
- `add_product.php` - Thêm sản phẩm mới
- `add_category.php` - Thêm danh mục

### Database & Assets
- `setup_saved_addresses.php` - Script tạo bảng địa chỉ đã lưu
- `uploads/` - Thư mục chứa hình ảnh sản phẩm
- `assets/css/` - File CSS tùy chỉnh
- `assets/js/` - JavaScript files (cart-page.js, main.js)

## 📁 Cấu trúc thư mục

```
vlxd/
├── index.php                  # Trang chủ
├── products.php               # Danh sách sản phẩm
├── cart.php                   # Giỏ hàng
├── checkout.php               # Thanh toán
├── payment_qr.php             # Thanh toán QR code
├── confirm_payment.php        # Xác nhận thanh toán
├── thanhtoan.php              # Xác nhận thanh toán (legacy)
├── order_success.php          # Thành công
├── process_order.php          # Xử lý đơn hàng
├── my_orders.php              # Lịch sử đơn hàng
├── addresses.php              # Quản lý địa chỉ
├── login.php                  # Đăng nhập
├── dangki.php                 # Đăng ký
├── check.php                  # Xử lý auth
├── callback.php               # Google OAuth callback
├── logout.php                 # Đăng xuất
├── profile.php                # Hồ sơ
├── admin.php                  # Quản lý users
├── admin_products.php         # Quản lý products
├── admin_orders.php           # Quản lý orders
├── add_product.php            # Thêm sản phẩm
├── add_category.php           # Thêm danh mục
├── add_to_cart.php            # Thêm vào giỏ
├── update_cart.php            # Cập nhật giỏ
├── remove_from_cart.php       # Xóa khỏi giỏ
├── get_order_details.php      # API order details
├── get_order_items_for_review.php  # API review items
├── submit_review.php          # Xử lý review
├── config.php                 # Cấu hình DB
├── vlxd_store1.sql            # Database backup
├── setup_saved_addresses.php  # Script setup địa chỉ
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       ├── main.js
│       └── cart-page.js
├── uploads/                   # Hình ảnh sản phẩm
│   ├── logo.png
│   ├── gach ceramic.jpg
│   └── ...
└── README.md
```

## 🎨 Chức năng chính

### Khách hàng
- 🏠 Trang chủ với sản phẩm nổi bật
- 🛍️ Duyệt sản phẩm theo danh mục 
- 🔍 Lọc sản phẩm theo category
- 🛒 Giỏ hàng với AJAX (không reload trang)
- � Lưu và chọn địa chỉ giao hàng
- 💳 Thanh toán: COD, Banking, MoMo
- 📱 Thanh toán QR code (VietQR API - MoMo & MB Bank cá nhân)
- 📦 Lịch sử đơn hàng với trạng thái real-time
- ❌ Hủy đơn hàng với lý do (khi chưa xử lý)
- ⭐ Đánh giá sản phẩm sau khi nhận hàng
- 👤 Quản lý hồ sơ cá nhân

### Xác thực & Bảo mật
- 🔐 Đăng nhập email/password (bcrypt hash - VARCHAR(255))
- 🌐 Đăng nhập Google OAuth 2.0
- 🔒 Session-based authentication
- 👥 Phân quyền User/Admin
- 🛡️ SQL injection prevention (prepared statements)
- 🚪 Auto-redirect khi chưa đăng nhập
- ✅ Password hashing chuẩn bcrypt ($2y$12$)

### Quản trị viên (Admin)
- 👥 Quản lý người dùng (Active/Inactive)
- 📦 Quản lý sản phẩm (CRUD operations)
- 🏷️ Quản lý danh mục
- 🖼️ Upload hình ảnh sản phẩm (JSON format)
- 📋 Quản lý đơn hàng & cập nhật trạng thái
- 📊 Xem thống kê tổng quan
- 🏭 Quản lý nhà cung cấp (click để xem chi tiết)
- 📦 Hệ thống quản lý kho (inventory tracking)
- ⭐ Duyệt và quản lý đánh giá sản phẩm
- 📈 Dashboard thống kê nâng cao

### Giao diện
- 📱 Responsive design (Mobile-first)
- 🎨 Tailwind CSS 3.x
- ⚡ AJAX real-time updates
- 🌈 Modern UI/UX
- 🔔 Toast notifications
- 💫 Smooth animations
- 🎯 Intuitive navigation

## 📝 Ghi chú

- Giỏ hàng sử dụng Session để lưu trữ dữ liệu tạm thời
- Database: `vlxd_store1` (auto-create tables on first run)
- Password field: **VARCHAR(255)** (critical for bcrypt hashes)
- Chạy trên localhost với XAMPP hoặc PHP built-in server
- Giao diện sử dụng Tailwind CSS 3.x (CDN)
- Session được kiểm tra để tránh lỗi "session already started"
- Payment QR sử dụng VietQR API (img.vietqr.io)
- **Thanh toán cá nhân:** MoMo 0379648264, MB Bank 0379648264 (Võ Nhật Duy Nam)
- Hình ảnh lưu dạng JSON trong database, file thực tế trong thư mục uploads/
- Hệ thống tự động thêm prefix 'uploads/' nếu thiếu khi hiển thị ảnh

## �️ Cấu trúc Database

Database tự động được tạo khi chạy lần đầu (xem `config.php`)

### Các bảng chính:

- **users** - Thông tin người dùng
  - id, email, password, full_name, phone, address, role, google_id, avatar_url, is_active
  
- **categories** - Danh mục sản phẩm
  - id, NAME, description, parent_id, image, STATUS
  - Dữ liệu: Xi măng (1), Gạch (2), Thép (3), Sơn (4)
  
- **products** - Sản phẩm
  - id, NAME, description, short_description, sku, category_id, supplier_id
  - price, sale_price, cost_price, quantity, min_quantity, max_quantity
  - weight, unit, images (JSON), specifications (JSON), STATUS
  
- **cart** - Giỏ hàng
  - id, user_id, session_id, created_at, updated_at
  
- **cart_items** - Chi tiết giỏ hàng
  - id, cart_id, product_id, quantity, price
  
- **orders** - Đơn hàng
  - id, user_id, order_code, total_amount, STATUS, payment_method, payment_status
  - order_status (pending, processing, shipping, delivered, cancelled, awaiting_payment)
  - cancel_reason (TEXT) - Lý do hủy đơn
  - customer_name, customer_phone, customer_email, shipping_address
  
- **order_items** - Chi tiết đơn hàng
  - id, order_id, product_id, quantity, price, subtotal

- **saved_addresses** - Địa chỉ đã lưu
  - id, user_id, recipient_name, phone, province, district, ward, address_detail
  - is_default, created_at, updated_at
  
- **reviews** - Đánh giá sản phẩm
  - id, user_id, product_id, order_id, rating (1-5), comment
  - created_at
  
- **suppliers** - Nhà cung cấp
  - id, NAME, contact_person, phone, email, address, STATUS

- **inventory** - Kho hàng
  - id, product_id, quantity_change, current_quantity, TYPE, reference_id

- **promotions** - Khuyến mãi
  - id, NAME, description, discount_type, discount_value, CODE, usage_limit

- **momo_transactions** - Giao dịch MoMo
  - id, order_id, transaction_id, request_id, amount, message
  - qr_code_url, pay_url, deeplink (TEXT)
  - status (pending, success, failed), created_at, updated_at

- **order_status_logs** - Lịch sử trạng thái đơn hàng
  - id, order_id, old_status, new_status, changed_by, changed_at, note

## 🔑 Cấu hình Google OAuth (Tùy chọn)

Để sử dụng tính năng đăng nhập Google:

### 1. Tạo Google Cloud Project
1. Truy cập [Google Cloud Console](https://console.cloud.google.com/)
2. Tạo project mới hoặc chọn project có sẵn
3. Enable **Google+ API**

### 2. Tạo OAuth Credentials
1. Vào **API & Services** → **Credentials**
2. Click **Create Credentials** → **OAuth client ID**
3. Chọn **Web application**
4. Cấu hình:
   - **Authorized JavaScript origins:**
     - `http://localhost`
     - `http://localhost/vlxd`
   - **Authorized redirect URIs:**
     - `http://localhost/vlxd/callback.php`

### 3. Cấu hình OAuth Consent Screen
1. Vào **OAuth consent screen**
2. Chọn **External** hoặc **Internal**
3. Điền thông tin ứng dụng
4. Thêm **Test users** (email của bạn) để test

### 4. Cập nhật config.php
```php
$CLIENT_ID = "YOUR_GOOGLE_CLIENT_ID";
$REDIRECT_URI = "http://localhost/vlxd/callback.php";
$DEV_ADMIN_EMAIL = "your-admin@gmail.com";
```

### 5. Testing
- Truy cập `http://localhost/vlxd/login.php`
- Click nút **Sign in with Google**
- Đăng nhập bằng test user

### ⚠️ Lưu ý bảo mật
- ❌ KHÔNG commit Client Secret vào Git
- ✅ Sử dụng environment variables cho production
- ✅ Chỉ thêm trusted domain vào redirect URIs
- ✅ Enable HTTPS khi deploy production

## � Ghi chú kỹ thuật

### Session Management
- Giỏ hàng sử dụng Session để lưu trữ
- Session ID được hash để bảo mật
- Tự động tạo cart cho cả guest user

### Database
- Auto-create tables khi chạy lần đầu
- Charset: UTF-8 (utf8mb4)
- Collation: utf8mb4_unicode_ci
- Prepared statements để chống SQL injection

### Security Features
- Password hashing với `password_hash()` (bcrypt, $2y$12$)
- **Critical:** PASSWORD column must be VARCHAR(255) for bcrypt
- Session-based authentication
- Input validation & sanitization
- XSS prevention với `htmlspecialchars()`
- Prepared statements prevent SQL injection
- PHP 8.4 compatible (null coalescing operators)

### Performance
- Lazy loading images
- AJAX cart operations (no page reload)
- Optimized queries với indexing
- JSON storage cho images & specifications

## 🚀 Deployment

### Localhost (XAMPP)
```bash
# 1. Copy vào htdocs
cp -r vlxd D:/xampp/htdocs/

# 2. Import database
mysql -u root < vlxd_storemoi.sql

# 3. Truy cập
http://localhost/vlxd
```

### Production Server
1. Upload files qua FTP/SFTP
2. Tạo database trên hosting
3. Import SQL file qua phpMyAdmin
4. Cập nhật `config.php` với thông tin database production
5. Đảm bảo folder `uploads/` có quyền write (755)
6. Enable HTTPS (SSL certificate)
7. Update Google OAuth redirect URI

## 🐛 Troubleshooting

### Lỗi "mysqli not found"
```bash
# Trong php.ini, uncomment:
extension=mysqli
```

### Lỗi session
```php
// Đã xử lý trong config.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
```

### Lỗi upload hình
- Kiểm tra quyền folder `uploads/` (755 hoặc 777)
- Kiểm tra `upload_max_filesize` trong php.ini
- Kiểm tra `post_max_size` trong php.ini

### Database connection failed
- Kiểm tra MySQL đang chạy
- Verify username/password trong `config.php`
- Đảm bảo database `vlxd_store1` đã được tạo

### Lỗi đăng nhập sau khi đăng ký
- **Root Cause:** PASSWORD column VARCHAR(50) truncates bcrypt hashes (60 chars)
- **Solution:** 
  ```sql
  ALTER TABLE users MODIFY COLUMN PASSWORD VARCHAR(255);
  ```
- Users with old passwords need to re-register

## 🔄 Changelog

### v2.2.0 (2026-01-12)
- ✅ Chuyển sang thanh toán cá nhân (MoMo: 0379648264, MB Bank: 0379648264)
- ✅ Tích hợp VietQR API cho QR code thanh toán
- ✅ Thêm tính năng hủy đơn hàng với lý do
- ✅ Cải thiện giao diện quản lý nhà cung cấp (click để xem chi tiết)
- ✅ Fix hiển thị hình ảnh sản phẩm (logic prefix uploads/)
- ✅ Cập nhật 17 sản phẩm với đường dẫn hình ảnh đúng
- ✅ Thêm bảng order_status_logs để theo dõi lịch sử đơn hàng
- ✅ Thêm các cột qr_code_url, pay_url, deeplink vào momo_transactions
- ✅ Hệ thống inventory management nâng cao

### v2.1.0 (2025-12-23)
- ✅ Thêm thanh toán QR code (Banking & MoMo)
- ✅ Hệ thống lưu địa chỉ giao hàng
- ✅ Chọn địa chỉ đã lưu khi checkout
- ✅ Hệ thống đánh giá sản phẩm
- ✅ Fix lỗi đăng nhập (PASSWORD VARCHAR(255))
- ✅ Fix admin panel PHP 8.4 deprecation
- ✅ Cải thiện checkout flow & validation
- ✅ Thêm trường fullname vào form đăng ký
- ✅ API endpoints cho order details & reviews

### v2.0.0 (2025-12-20)
- ✅ Fix category_id cho sản phẩm Gạch và Sơn
- ✅ Cập nhật database structure
- ✅ Thêm file fix_categories.sql
- ✅ Xóa file vlxd_store.sql cũ
- ✅ Cập nhật README.md

### v1.0.0 (2025-11-25)
- 🎉 Initial release
- ✅ Basic CRUD operations
- ✅ Google OAuth integration
- ✅ Shopping cart functionality
- ✅ Admin panel

## 🤝 Đóng góp

Mọi đóng góp đều được hoan nghênh!

1. Fork repository
2. Tạo branch mới (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Mở Pull Request

## 👨‍💻 Tác giả

- **KAT1140** - [GitHub Profile](https://github.com/KAT1140)
- Email: namvokat@gmail.com

## 📄 License

MIT License - Tự do sử dụng cho mục đích cá nhân và thương mại

Copyright (c) 2025 KAT1140

## 🙏 Acknowledgments

- [Tailwind CSS](https://tailwindcss.com/) - CSS Framework
- [Font Awesome](https://fontawesome.com/) - Icon Library
- [Google OAuth](https://developers.google.com/identity) - Authentication
- [XAMPP](https://www.apachefriends.org/) - Development Environments