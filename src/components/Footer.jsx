import { Facebook, Instagram, Youtube, MapPin, Phone, Mail } from "lucide-react";

export default function Footer() {
  return (
    <footer className="bg-orange-600 text-white">
      <div className="container mx-auto px-4 py-12">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-8">

          {/* Company Info */}
          <div>
            <div className="flex items-center gap-3 mb-6">
              <div className="w-12 h-12 bg-white rounded flex items-center justify-center flex-shrink-0">
                <span className="text-orange-600 text-xl font-bold">VL</span>
              </div>
              <h3 className="text-white text-2xl font-bold">VẬT LIỆU XÂY DỰNG</h3>
            </div>
            <p className="text-white/90 text-sm leading-relaxed mb-6">
              Đơn vị cung cấp vật liệu xây dựng uy tín, chất lượng hàng đầu Việt Nam với nhiều năm kinh nghiệm.
            </p>
            <div className="flex gap-4">
              <a href="#" className="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center hover:bg-white hover:text-orange-600 transition-colors">
                <Facebook className="w-5 h-5" />
              </a>
              <a href="#" className="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center hover:bg-white hover:text-orange-600 transition-colors">
                <Instagram className="w-5 h-5" />
              </a>
              <a href="#" className="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center hover:bg-white hover:text-orange-600 transition-colors">
                <Youtube className="w-5 h-5" />
              </a>
            </div>
          </div>

          {/* Quick Links */}
          <div>
            <h3 className="text-white text-lg font-semibold mb-5">Liên kết nhanh</h3>
            <ul className="space-y-3 text-sm">
              <li><a href="#" className="hover:text-white/70 transition-colors">Giới thiệu</a></li>
              <li><a href="#" className="hover:text-white/70 transition-colors">Sản phẩm</a></li>
              <li><a href="#" className="hover:text-white/70 transition-colors">Chính sách bảo hành</a></li>
              <li><a href="#" className="hover:text-white/70 transition-colors">Điều khoản sử dụng</a></li>
            </ul>
          </div>

          {/* Contact Info */}
          <div>
            <h3 className="text-white text-lg font-semibold mb-5">Thông tin liên hệ</h3>
            <ul className="space-y-4 text-sm">
              <li className="flex items-start gap-3">
                <MapPin className="w-5 h-5 text-white flex-shrink-0 mt-0.5" />
                <span>123 Đường ABC, Quận 1, TP. Hồ Chí Minh</span>
              </li>
              <li className="flex items-center gap-3">
                <Phone className="w-5 h-5 text-white flex-shrink-0" />
                <span>1900-xxxx</span>
              </li>
              <li className="flex items-center gap-3">
                <Mail className="w-5 h-5 text-white flex-shrink-0" />
                <span>contact@vlxd.vn</span>
              </li>
            </ul>
          </div>

          {/* Newsletter */}
          <div>
            <h3 className="text-white text-lg font-semibold mb-5">Đăng ký nhận tin</h3>
            <p className="text-white/90 text-sm mb-5">
              Nhận thông tin khuyến mãi và sản phẩm mới nhất
            </p>
            <form className="flex flex-col sm:flex-row gap-3 max-w-sm">
              <input
                type="email"
                placeholder="Email của bạn"
                className="px-4 py-3 bg-white/20 border border-white/30 rounded text-white placeholder:text-white/70 focus:outline-none focus:border-white transition-colors"
              />
              <button type="submit" className="px-6 py-3 bg-orange-700 hover:bg-orange-800 text-white font-medium rounded transition-colors whitespace-nowrap">
                Gửi
              </button>
            </form>
          </div>
        </div>

        <div className="pt-8 border-t border-white/20 text-center text-sm">
          <p className="text-white/80">© 2024 Vật Liệu Xây Dựng. Bản quyền thuộc về công ty.</p>
        </div>
      </div>
    </footer>
  );
}