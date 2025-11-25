# 🏢 VLXD PRO - Website Bán Vật Liệu Xây Dựng

Website thương mại điện tử chuyên cung cấp vật liệu xây dựng được xây dựng với PHP, MySQL, Tailwind CSS và Font Awesome.

## 🚀 Công nghệ sử dụng

- **PHP 8.2+** - Backend server-side
- **MySQL 5.7+** - Cơ sở dữ liệu
- **Tailwind CSS** - Frontend styling
- **Font Awesome 6.4** - Icon library
- **JavaScript ES6+** - Tương tác giao diện
- **XAMPP** - Môi trường phát triển

## 📦 Cài đặt

### Yêu cầu
- XAMPP (Apache + MySQL + PHP 8.2+)
- Git
- Browser hiện đại

### Các bước cài đặt

1. **Clone repository:**
```bash
git clone https://github.com/KAT1140/DU_AN_MA_NGON_MO_WEBSITE_BAN_VLSD.git
cd vlxd
```

2. **Tạo cơ sở dữ liệu:**
   - Mở phpMyAdmin: `http://localhost/phpmyadmin`
   - Tạo database mới tên `vlxd_store`
   - Import file `vlxd_store.sql`

3. **Cấu hình:**
   - Sửa file `config.php` với thông tin kết nối MySQL

4. **Chạy ứng dụng:**
   - Khởi động XAMPP (Apache + MySQL)
   - Truy cập: `http://localhost/vlxd`

## 📄 Các file chính

- `index.php` - Trang chính, hiển thị danh sách sản phẩm
- `login.php` - Trang đăng nhập với Google Sign In
- `dangki.php` - Trang đăng ký người dùng mới
- `check.php` - Xử lý logic đăng nhập, validation
- `logout.php` - Đăng xuất người dùng
- `add_product.php` - Thêm sản phẩm mới
- `add_category.php` - Thêm danh mục
- `cart.php` - Giỏ hàng
- `add_to_cart.php` - Xử lý thêm vào giỏ hàng
- `callback.php` - Callback xử lý thanh toán Google
- `config.php` - Cấu hình kết nối database (tự động tạo bảng)

## 📁 Cấu trúc thư mục

```
vlxd/
├── index.php              # Trang chính
├── login.php              # Trang đăng nhập
├── dangki.php             # Trang đăng ký
├── check.php              # Xử lý logic đăng nhập
├── logout.php             # Đăng xuất
├── profile.php            # Hồ sơ cá nhân
├── cart.php               # Quản lý giỏ hàng
├── add_to_cart.php        # Xử lý thêm vào giỏ
├── update_cart.php        # Cập nhật số lượng
├── remove_from_cart.php   # Xóa khỏi giỏ
├── admin.php              # Quản lý người dùng
├── admin_products.php     # Quản lý sản phẩm
├── config.php             # Cấu hình database
├── vlxd_store.sql         # SQL backup database
├── uploads/               # Thư mục upload ảnh
└── README.md              # File này
```

## 🎨 Chức năng

- 🛍️ Hiển thị danh sách sản phẩm vật liệu xây dựng
- 🏷️ Quản lý danh mục sản phẩm
- 🛒 Giỏ hàng (lưu qua Session)
- 🔐 **Hệ thống xác thực:**
  - Đăng nhập bằng email/mật khẩu
  - Đăng nhập bằng Google (OAuth)
  - Đăng ký tài khoản mới
  - Hash mật khẩu an toàn
- 💳 Xử lý thanh toán callback
- 📱 Giao diện responsive với Tailwind CSS
- 👥 Admin panel quản lý người dùng & sản phẩm
- 📊 Thống kê tổng hợp

## 📝 Ghi chú

- Giỏ hàng sử dụng Session để lưu trữ dữ liệu tạm thời
- Database: `vlxd_store` (tự động tạo bảng khi chạy lần đầu)
- Chạy trên localhost với XAMPP
- Giao diện sử dụng Tailwind CSS
- Session được kiểm tra để tránh lỗi "session already started"

## 🔒 Bảng Database được tạo tự động:

- **users** - Lưu thông tin người dùng
- **categories** - Danh mục sản phẩm
- **products** - Danh sách sản phẩm
- **cart** - Giỏ hàng theo session
- **orders** - Đơn hàng
- **order_items** - Chi tiết đơn hàng

## 🔑 Google OAuth (Sign-In)

Nếu bạn muốn cho người dùng đăng nhập bằng Google, làm theo các bước sau trên Google Cloud Console:

1. Mở Google Cloud Console → API & Services → OAuth consent screen
    - Nếu app đang ở chế độ "Testing", chỉ những email được thêm vào mục "Test users" mới đăng nhập được. Thêm email của bạn để test.
    - Nếu muốn công khai, chuyển sang trạng thái "Production" và làm theo yêu cầu verify của Google nếu dùng các scope nhạy cảm.

2. Mở API & Services → Credentials → OAuth 2.0 Client IDs → chọn Client ID
    - Thêm vào **Authorized JavaScript origins**:
       - `http://localhost`
       - `http://localhost/vlxd`
    - Thêm vào **Authorized redirect URIs**:
       - `http://localhost/vlxd/callback.php`

3. Cập nhật `config.php` trong project với `CLIENT_ID` và `REDIRECT_URI` (mình đã thêm sẵn biến `$CLIENT_ID` và `$REDIRECT_URI` trong `config.php`).

4. Nếu Google chặn (unverified), thêm tài khoản Google của bạn vào danh sách **Test users** trên trang OAuth consent screen để bỏ chặn trong giai đoạn phát triển.

5. Kiểm tra bằng cách mở `http://localhost/vlxd/login.php` và dùng nút "Sign in with Google".

Ghi chú bảo mật:
- Không commit Client Secret vào repo công khai.
- Khi public app, tuân thủ yêu cầu verifications của Google nếu dùng các scope nhạy cảm.

## 📄 License

MIT License - Tự do sử dụng cho mục đích cá nhân và thương mại

## 👥 Đóng góp

Mọi đóng góp đều được chào đón! Hãy tạo issue hoặc pull request.



