import { Facebook, Instagram, Youtube, MapPin, Phone, Mail } from "lucide-react";

export default function Footer() {
  return (
    <footer className="bg-gray-900 text-gray-300">
      <div className="container mx-auto px-4 py-12">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-8">
          {/* Company Info */}
          <div>
            <div className="flex items-center gap-2 mb-4">
              <div className="w-10 h-10 bg-orange-600 rounded flex items-center justify-center">
                <span className="text-white">VL</span>
              </div>
              <div>
                <h3 className="text-white">VẬT LIỆU XÂY DỰNG</h3>
              </div>
            </div>
            <p className="text-sm mb-4">
              Đơn vị cung cấp vật liệu xây dựng uy tín, chất lượng hàng đầu Việt Nam với hơn 15 năm kinh nghiệm.
            </p>
            <div className="flex gap-3">
              <a href="#" className="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-orange-600 transition-colors">
                <Facebook className="w-4 h-4" />
              </a>
              <a href="#" className="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-orange-600 transition-colors">
                <Instagram className="w-4 h-4" />
              </a>
              <a href="#" className="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center hover:bg-orange-600 transition-colors">
                <Youtube className="w-4 h-4" />
              </a>
            </div>
          </div>

          {/* Quick Links */}
          <div>
            <h3 className="text-white mb-4">Liên kết nhanh</h3>
            <ul className="space-y-2 text-sm">
              <li><a href="#" className="hover:text-orange-600 transition-colors">Giới thiệu</a></li>
              <li><a href="#" className="hover:text-orange-600 transition-colors">Sản phẩm</a></li>
              <li><a href="#" className="hover:text-orange-600 transition-colors">Tin tức</a></li>
              <li><a href="#" className="hover:text-orange-600 transition-colors">Chính sách bảo hành</a></li>
              <li><a href="#" className="hover:text-orange-600 transition-colors">Điều khoản sử dụng</a></li>
            </ul>
          </div>

          {/* Contact Info */}
          <div>
            <h3 className="text-white mb-4">Thông tin liên hệ</h3>
            <ul className="space-y-3 text-sm">
              <li className="flex items-start gap-2">
                <MapPin className="w-5 h-5 text-orange-600 flex-shrink-0 mt-0.5" />
                <span>123 Đường ABC, Quận 1, TP. Hồ Chí Minh</span>
              </li>
              <li className="flex items-center gap-2">
                <Phone className="w-5 h-5 text-orange-600 flex-shrink-0" />
                <span>1900-xxxx</span>
              </li>
              <li className="flex items-center gap-2">
                <Mail className="w-5 h-5 text-orange-600 flex-shrink-0" />
                <span>contact@vlxd.vn</span>
              </li>
            </ul>
          </div>

          {/* Newsletter */}
          <div>
            <h3 className="text-white mb-4">Đăng ký nhận tin</h3>
            <p className="text-sm mb-4">
              Nhận thông tin khuyến mãi và sản phẩm mới nhất
            </p>
            <div className="flex gap-2">
              <input
                type="email"
                placeholder="Email của bạn"
                className="flex-1 px-3 py-2 bg-gray-800 border border-gray-700 text-white placeholder:text-gray-500 rounded focus:outline-none focus:border-orange-600"
              />
              <button className="bg-orange-600 hover:bg-orange-700 px-4 py-2 rounded text-white font-medium transition-colors flex-shrink-0">
                Gửi
              </button>
            </div>
          </div>
        </div>

        {/* Bottom Bar */}
        <div className="pt-8 border-t border-gray-800 text-center text-sm">
          <p>© 2024 Vật Liệu Xây Dựng. Bản quyền thuộc về công ty.</p>
        </div>
      </div>
    </footer>
  );
}