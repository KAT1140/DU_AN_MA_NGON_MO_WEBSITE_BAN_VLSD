<?php require 'config.php'; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Giỏ hàng - VLXD PRO</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
  <!-- Header Navigation -->
  <div class="bg-white shadow-md sticky top-0 z-50">
    <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
      <a href="index.php" class="text-2xl font-bold text-orange-600">VLXD PRO</a>
      <h1 class="text-xl font-bold text-gray-800"><i class="fas fa-shopping-cart"></i> Giỏ hàng</h1>
      <div class="w-20"></div>
    </div>
  </div>

  <div class="max-w-6xl mx-auto px-6 py-8">
    <?php 
    // Lấy các mục trong giỏ (cart -> cart_items -> products)
    $sql = "SELECT ci.id AS ci_id, ci.quantity AS ci_quantity, ci.price AS ci_price, p.id AS product_id, p.NAME AS product_name, p.images AS product_images
      FROM cart c
      JOIN cart_items ci ON ci.cart_id = c.id
      LEFT JOIN products p ON p.id = ci.product_id
      WHERE c.session_id = '" . $conn->real_escape_string($cart_session) . "'
      ORDER BY ci.id DESC";
    $result = $conn->query($sql);
    if (!$result || $result->num_rows == 0): ?>
      <div class="text-center py-16">
        <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
        <p class="text-2xl text-gray-600 font-semibold mb-6">Giỏ hàng trống!</p>
        <a href="index.php" class="bg-orange-600 text-white px-8 py-3 rounded-lg hover:bg-orange-700 transition font-bold">
          <i class="fas fa-shopping-bag"></i> Tiếp tục mua sắm
        </a>
      </div>
    <?php else:
      $total = 0; ?>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Products List -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="bg-gradient-to-r from-orange-600 to-orange-500 px-6 py-4">
              <h2 class="text-white font-bold text-lg"><i class="fas fa-list"></i> Danh sách sản phẩm</h2>
            </div>
            <div class="divide-y">
              <?php while($row = $result->fetch_assoc()):
                $price = isset($row['ci_price']) ? (float)$row['ci_price'] : 0;
                $quantity = isset($row['ci_quantity']) ? (int)$row['ci_quantity'] : 0;
                $name = isset($row['product_name']) ? $row['product_name'] : 'Sản phẩm';
                $ci_id = isset($row['ci_id']) ? (int)$row['ci_id'] : 0;
                $product_id = isset($row['product_id']) ? (int)$row['product_id'] : 0;
                
                // Xử lý hình ảnh từ JSON
                $image = 'https://via.placeholder.com/100';
                if (isset($row['product_images']) && !empty($row['product_images'])) {
                  try {
                    $images_json = json_decode($row['product_images'], true);
                    if (is_array($images_json) && count($images_json) > 0) {
                      $image = $images_json[0];
                    }
                  } catch (Exception $e) {
                    // Nếu JSON không hợp lệ, dùng placeholder
                  }
                }
                
                $thanhtien = $price * $quantity;
                $total += $thanhtien;
              ?>
              <div class="p-6 hover:bg-gray-50 transition" data-ci-id="<?= $ci_id ?>" data-price="<?= $price ?>" data-qty="<?= $quantity ?>">
                <div class="flex gap-4">
                  <!-- Product Image -->
                  <div class="flex-shrink-0">
                    <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($name) ?>" class="w-24 h-24 object-cover rounded-lg border border-gray-200">
                  </div>
                  
                  <!-- Product Details -->
                  <div class="flex-1">
                    <div class="flex justify-between items-start mb-2">
                      <h3 class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($name) ?></h3>
                      <button class="remove-item text-red-500 hover:text-red-700 transition" data-ci-id="<?= $ci_id ?>" title="Xóa sản phẩm">
                        <i class="fas fa-trash-alt text-lg"></i>
                      </button>
                    </div>
                    
                    <div class="flex justify-between items-center">
                      <div class="flex items-center gap-3">
                        <span class="text-xl font-bold text-orange-600"><?= number_format($price) ?>đ</span>
                        <span class="text-gray-500 text-sm">/ sản phẩm</span>
                      </div>
                      
                      <!-- Quantity Control -->
                      <div class="flex items-center gap-2 bg-gray-100 rounded-lg px-2 py-1">
                        <button class="qty-decrease px-3 py-1 text-gray-600 hover:text-orange-600 transition font-bold" data-ci-id="<?= $ci_id ?>">
                          <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" class="qty-input w-12 text-center border-0 bg-gray-100 font-bold" value="<?= $quantity ?>" min="1" max="999" data-ci-id="<?= $ci_id ?>" readonly>
                        <button class="qty-increase px-3 py-1 text-gray-600 hover:text-orange-600 transition font-bold" data-ci-id="<?= $ci_id ?>">
                          <i class="fas fa-plus"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Total for this item -->
                <div class="mt-4 pt-4 border-t flex justify-between items-center">
                  <span class="text-gray-600">Thành tiền:</span>
                  <span class="text-2xl font-black text-orange-600 item-total"><?= number_format($thanhtien) ?>đ</span>
                </div>
              </div>
              <?php endwhile; ?>
            </div>
          </div>
        </div>

        <!-- Summary Sidebar -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-xl shadow-md sticky top-24 overflow-hidden">
            <div class="bg-gradient-to-r from-orange-600 to-orange-500 px-6 py-4">
              <h2 class="text-white font-bold text-lg"><i class="fas fa-calculator"></i> Tóm tắt đơn hàng</h2>
            </div>
            
            <div class="p-6 space-y-4">
              <!-- Subtotal -->
              <div class="flex justify-between items-center">
                <span class="text-gray-600">Tạm tính:</span>
                <span class="font-bold text-lg total-price"><?= number_format($total) ?>đ</span>
              </div>
              
              <!-- Shipping -->
              <div class="flex justify-between items-center pb-4 border-b">
                <span class="text-gray-600">Phí vận chuyển:</span>
                <span class="font-bold">Miễn phí</span>
              </div>
              
              <!-- Total -->
              <div class="flex justify-between items-center py-4 bg-orange-50 px-4 rounded-lg">
                <span class="font-bold text-lg text-gray-800">Tổng tiền:</span>
                <span class="text-3xl font-black text-orange-600 final-total"><?= number_format($total) ?>đ</span>
              </div>

              <!-- Action Buttons -->
              <div class="space-y-3 pt-4">
                <button class="checkout-btn w-full bg-gradient-to-r from-orange-600 to-orange-500 text-white py-3 rounded-lg font-bold hover:shadow-lg transition">
                  <i class="fas fa-lock"></i> THANH TOÁN
                </button>
                <a href="index.php" class="block text-center bg-gray-200 text-gray-800 py-3 rounded-lg font-bold hover:bg-gray-300 transition">
                  <i class="fas fa-arrow-left"></i> Tiếp tục mua hàng
                </a>
              </div>

              <!-- Additional Info -->
              <div class="text-xs text-gray-500 space-y-2 pt-4 border-t">
                <p><i class="fas fa-check-circle text-green-500"></i> Hàng chính hãng 100%</p>
                <p><i class="fas fa-headset text-blue-500"></i> Hỗ trợ 24/7</p>
                <p><i class="fas fa-undo text-purple-500"></i> Đổi trả miễn phí</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
  
  <style>
    .toast {
      position: fixed;
      top: 100px;
      right: 20px;
      padding: 14px 20px;
      background: white;
      border-radius: 8px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.15);
      z-index: 9999;
      border-left: 4px solid #10b981;
      color: #065f46;
      font-weight: 500;
      animation: slideIn 0.3s ease-out;
    }
    .toast.error { border-left-color: #ef4444; color: #7f1d1d; }
    
    @keyframes slideIn {
      from {
        transform: translateX(400px);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }
  </style>

  <script>
    function showToast(message, isSuccess = true) {
      const t = document.createElement('div');
      t.className = 'toast' + (isSuccess ? '' : ' error');
      t.textContent = message;
      document.body.appendChild(t);
      setTimeout(() => { 
        t.style.opacity = '0';
        t.style.transform = 'translateX(400px)';
        setTimeout(() => t.remove(), 300);
      }, 2500);
    }

    function updateTotal() {
      let total = 0;
      document.querySelectorAll('[data-ci-id]').forEach(row => {
        const priceAttr = row.getAttribute('data-price');
        const price = priceAttr ? parseFloat(priceAttr) : 0;
        const qtyInput = row.querySelector('.qty-input');
        const qty = qtyInput ? (parseInt(qtyInput.value) || 0) : 0;
        const itemTotal = price * qty;
        const itemTotalEl = row.querySelector('.item-total');
        if (itemTotalEl) {
          itemTotalEl.textContent = new Intl.NumberFormat('vi-VN').format(itemTotal) + 'đ';
        }
        total += itemTotal;
      });
      
      const totalEl = document.querySelector('.total-price');
      const finalEl = document.querySelector('.final-total');
      const formattedTotal = new Intl.NumberFormat('vi-VN').format(total);
      if (totalEl) totalEl.textContent = formattedTotal + 'đ';
      if (finalEl) finalEl.textContent = formattedTotal + 'đ';
    }

    // Decrease quantity
    document.querySelectorAll('.qty-decrease').forEach(btn => {
      btn.addEventListener('click', async (e) => {
        e.preventDefault();
        const ciId = btn.getAttribute('data-ci-id');
        const input = document.querySelector(`.qty-input[data-ci-id="${ciId}"]`);
        if (!input || !ciId) return;
        let qty = parseInt(input.value) || 1;
        if (qty > 1) {
          qty--;
          await updateQuantity(ciId, qty);
        }
      });
    });

    // Increase quantity
    document.querySelectorAll('.qty-increase').forEach(btn => {
      btn.addEventListener('click', async (e) => {
        e.preventDefault();
        const ciId = btn.getAttribute('data-ci-id');
        const input = document.querySelector(`.qty-input[data-ci-id="${ciId}"]`);
        if (!input || !ciId) return;
        let qty = parseInt(input.value) || 1;
        if (qty < 999) {
          qty++;
          await updateQuantity(ciId, qty);
        }
      });
    });

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
          if (input) {
            input.value = newQty;
          }
          updateTotal();
          showToast('✅ ' + (data.message || 'Cập nhật giỏ hàng thành công'), true);
        } else {
          showToast('❌ ' + (data.message || 'Lỗi cập nhật'), false);
        }
      } catch (err) {
        showToast('❌ Lỗi mạng: ' + err.message, false);
      }
    }

    // Remove item
    document.querySelectorAll('.remove-item').forEach(btn => {
      btn.addEventListener('click', async (e) => {
        const ciId = btn.getAttribute('data-ci-id');
        if (!ciId) return;
        if (!confirm('Xóa sản phẩm khỏi giỏ hàng?')) return;

        const fd = new FormData();
        fd.append('cart_item_id', ciId);

        try {
          const res = await fetch('remove_from_cart.php', { method: 'POST', body: fd });
          const data = await res.json();
          if (data.success) {
            const row = document.querySelector(`[data-ci-id="${ciId}"]`).closest('[data-ci-id]');
            row.style.opacity = '0';
            row.style.transform = 'slideUp';
            setTimeout(() => {
              row.remove();
              updateTotal();
              if (!document.querySelector('[data-ci-id]')) {
                location.reload();
              }
            }, 300);
            showToast('Xóa sản phẩm thành công', true);
          } else {
            showToast(data.message || 'Lỗi khi xóa', false);
          }
        } catch (err) {
          showToast('Lỗi mạng: ' + err.message, false);
        }
      });
    });

    // Checkout button
    document.querySelector('.checkout-btn')?.addEventListener('click', () => {
      alert('Tính năng thanh toán sẽ được cập nhật sớm!');
    });
  </script>
</body>
</html>