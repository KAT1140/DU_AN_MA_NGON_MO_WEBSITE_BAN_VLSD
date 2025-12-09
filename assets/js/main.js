// Hàm hiển thị thông báo
function showToast(message, isSuccess = true) {
    // Xóa toast cũ nếu quá nhiều
    const existingToasts = document.querySelectorAll('.toast');
    if (existingToasts.length > 3) existingToasts[0].remove();

    const t = document.createElement('div');
    t.className = 'toast' + (isSuccess ? '' : ' error');
    t.innerHTML = (isSuccess ? '<i class="fas fa-check-circle"></i> ' : '<i class="fas fa-exclamation-circle"></i> ') + message;
    document.body.appendChild(t);

    setTimeout(() => { 
        t.style.animation = 'slideOut 0.3s ease-out forwards';
        setTimeout(() => t.remove(), 300);
    }, 2500);
}

// Logic Thêm vào giỏ hàng (Chạy khi trang đã tải xong)
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('.add-to-cart-form');

    forms.forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const btn = form.querySelector('button');
            const originalHTML = btn.innerHTML;
            
            // Hiệu ứng loading
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; 
            btn.disabled = true;
            btn.classList.add('opacity-70', 'cursor-not-allowed');
            
            const formData = new FormData(form);
            
            try {
                const response = await fetch('add_to_cart.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                // Hiển thị thông báo
                showToast(data.message, data.success);

                // Cập nhật số lượng trên icon giỏ hàng
                if (data.success && data.new_cart_count !== undefined) {
                    const countEl = document.getElementById('cart-count');
                    if (countEl) {
                        countEl.innerText = data.new_cart_count;
                        countEl.classList.remove('hidden');
                        countEl.classList.add('bounce');
                        setTimeout(() => countEl.classList.remove('bounce'), 300);
                    }
                }
            } catch (error) {
                console.error(error);
                showToast('Lỗi kết nối server', false);
            } finally {
                // Trả lại nút bấm như cũ
                btn.innerHTML = originalHTML;
                btn.disabled = false;
                btn.classList.remove('opacity-70', 'cursor-not-allowed');
            }
        });
    });
});