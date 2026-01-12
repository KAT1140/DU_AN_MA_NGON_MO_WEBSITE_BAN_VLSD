<?php
require 'config.php';

if (!empty($_POST['name'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $conn->query("INSERT INTO categories (name) VALUES ('$name')");
    echo "✅ Đã thêm danh mục: $name";
}
?>

<form method="POST" class="max-w-xl mx-auto mt-10">
  <input type="text" name="name" placeholder="Tên danh mục" required class="p-3 border rounded w-full mb-4">
  <button type="submit" class="px-6 py-3 bg-purple-600 text-white rounded">Thêm danh mục</button>
</form>
