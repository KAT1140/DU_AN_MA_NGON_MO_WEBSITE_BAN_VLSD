-- Thêm cột image vào bảng products
ALTER TABLE products ADD COLUMN image VARCHAR(255) DEFAULT NULL AFTER unit;
