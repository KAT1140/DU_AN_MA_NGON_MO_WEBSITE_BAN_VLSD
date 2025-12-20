-- Thêm cột province vào bảng orders
-- Chạy script này nếu bảng orders đã tồn tại

ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS province VARCHAR(100) AFTER customer_address;

-- Cập nhật province từ shipping_address (nếu có data cũ)
UPDATE orders 
SET province = SUBSTRING_INDEX(shipping_address, ', ', -1)
WHERE province IS NULL OR province = '';
