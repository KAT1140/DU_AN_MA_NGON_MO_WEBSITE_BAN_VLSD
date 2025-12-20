-- Fix category_id cho các sản phẩm bị sai
-- Categories: 1=Xi măng, 2=Gạch, 3=Thép, 4=Sơn

-- Update sản phẩm Gạch (id 1-4, 6, 7) từ category_id=1 sang category_id=2
UPDATE products SET category_id = 2 WHERE id IN (1, 2, 3, 4, 6, 7);

-- Update sản phẩm Sơn (id 8-12) từ category_id=2 sang category_id=4
UPDATE products SET category_id = 4 WHERE id IN (8, 9, 10, 11, 12);

-- Kiểm tra kết quả
SELECT id, NAME, category_id FROM products ORDER BY category_id, id;
