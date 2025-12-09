document.addEventListener('DOMContentLoaded', function() {
    // 1. Hàm cập nhật tổng tiền giao diện (Tạm tính & Tổng cộng)
    function updateTotal() {
        let total = 0;
        document.querySelectorAll('[data-ci-id]').forEach(row => {
            const priceAttr = row.getAttribute('data-price');
            const price = priceAttr ? parseFloat(priceAttr) : 0;
            
            const qtyInput = row.querySelector('.qty-input');
            const qty = qtyInput ? (parseInt(qtyInput.value) || 0) : 0;
            
            const itemTotal = price * qty;
            
            // Cập nhật thành tiền từng dòng
            const itemTotalEl = row.querySelector('.item-total');
            if (itemTotalEl) {
                itemTotalEl.textContent = new Intl.NumberFormat('vi-VN').format(itemTotal) + 'đ';
            }
            total += itemTotal;
        });
        
        // Cập nhật tổng giỏ hàng
        const formattedTotal = new Intl.NumberFormat('vi-VN').format(total);
        const totalEl = document.querySelector('.total-price');
        const finalEl = document.querySelector('.final-total');
        
        if (totalEl) totalEl.textContent = formattedTotal + 'đ';
        if (finalEl) finalEl.textContent = formattedTotal + 'đ';
    }

    // 2. Hàm gọi Ajax cập nhật số lượng
    async function updateQuantity(ciId, newQty) {
        if (!ciId || newQty < 1) return;
        
        const fd = new FormData();
        fd.append('cart_item_id', ciId);
        fd.append('quantity', newQty);

        try {
            const res = await fetch('update_cart.php', { method: 'POST', body: fd });
            const data = await res.json();
            
            if (data.success) {
                const input = document.querySelector(`.qty-input[data-ci-id="${ciId}"]`);
                if (input) input.value = newQty;
                updateTotal();
                // Tùy chọn: showToast('Đã cập nhật số lượng', true);
            } else {
                showToast(data.message || 'Lỗi cập nhật', false);
            }
        } catch (err) {
            showToast('Lỗi mạng: ' + err.message, false);
        }
    }

    // 3. Sự kiện nút Giảm (-)
    document.querySelectorAll('.qty-decrease').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const ciId = btn.getAttribute('data-ci-id');
            const input = document.querySelector(`.qty-input[data-ci-id="${ciId}"]`);
            if (!input) return;
            
            let qty = parseInt(input.value) || 1;
            if (qty > 1) {
                await updateQuantity(ciId, qty - 1);
            }
        });
    });

    // 4. Sự kiện nút Tăng (+)
    document.querySelectorAll('.qty-increase').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const ciId = btn.getAttribute('data-ci-id');
            const input = document.querySelector(`.qty-input[data-ci-id="${ciId}"]`);
            if (!input) return;
            
            let qty = parseInt(input.value) || 1;
            if (qty < 999) {
                await updateQuantity(ciId, qty + 1);
            }
        });
    });

    // 5. Sự kiện nút Xóa (Thùng rác)
    document.querySelectorAll('.remove-item').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            const ciId = btn.getAttribute('data-ci-id');
            if (!ciId) return;
            if (!confirm('Bạn chắc chắn muốn xóa sản phẩm này?')) return;

            const fd = new FormData();
            fd.append('cart_item_id', ciId);

            try {
                const res = await fetch('remove_from_cart.php', { method: 'POST', body: fd });
                const data = await res.json();
                
                if (data.success) {
                    const row = btn.closest('[data-ci-id]'); // Tìm phần tử cha chứa dòng sp
                    if (row) {
                        row.style.opacity = '0';
                        setTimeout(() => {
                            row.remove();
                            updateTotal();
                            // Nếu xóa hết thì reload để hiện giỏ trống
                            if (!document.querySelector('.qty-input')) location.reload();
                        }, 300);
                    }
                    showToast('Đã xóa sản phẩm', true);
                } else {
                    showToast(data.message || 'Lỗi khi xóa', false);
                }
            } catch (err) {
                showToast('Lỗi mạng: ' + err.message, false);
            }
        });
    });

    // 6. Nút Thanh toán
    document.querySelector('.checkout-btn')?.addEventListener('click', (e) => {
        e.preventDefault();
        const finalEl = document.querySelector('.final-total');
        // Lấy số tiền, loại bỏ dấu phẩy và ký tự đ
        const total = parseFloat(finalEl?.textContent.replace(/[^0-9]/g, '') || 0);

        if (total > 0) {
            window.location.href = 'checkout.php'; // Chỉnh lại link nếu cần
        } else {
            showToast('Giỏ hàng trống! Vui lòng thêm sản phẩm.', false);
        }
    });
});