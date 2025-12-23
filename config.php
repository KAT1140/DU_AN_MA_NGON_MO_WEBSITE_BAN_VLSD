<?php
// Google OAuth configuration (so pages can use the same Client ID)
$CLIENT_ID = "163478021174-5gi5hpjvpim2ktrg3oindgtvqtt1o7hq.apps.googleusercontent.com";
$REDIRECT_URI = "http://localhost:8000/callback.php";

// Development helper: set this to an email to auto-promote that account to admin on startup.
// Example: $DEV_ADMIN_EMAIL = 'admin@example.com';
$DEV_ADMIN_EMAIL = 'namvokat@gmail.com';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = new mysqli("localhost", "root", "", "vlxd_store1");

if ($conn->connect_error) die("Kết nối thất bại!");

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Auto create tables if not exist
if (!isset($_SESSION['tables_created'])) {
    // Only create tables if they don't exist
    $tables_check = $conn->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = 'vlxd_store1'");
    $result = $tables_check->fetch_assoc();
    
    if ($result['count'] < 11) {  // If less than 11 tables exist
        // Create users table
        $conn->query("CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        email VARCHAR(255) UNIQUE NOT NULL,
        PASSWORD VARCHAR(255),
        full_name VARCHAR(255),
        phone VARCHAR(20),
        address TEXT,
        role VARCHAR(50) DEFAULT 'user',
        google_id VARCHAR(255),
        avatar_url VARCHAR(500),
        is_active TINYINT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Create categories table
    $conn->query("CREATE TABLE IF NOT EXISTS categories (
        id INT PRIMARY KEY AUTO_INCREMENT,
        NAME VARCHAR(100),
        description TEXT,
        parent_id INT,
        image VARCHAR(255),
        STATUS TINYINT(1),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Create products table
    $conn->query("CREATE TABLE IF NOT EXISTS products (
        id INT PRIMARY KEY AUTO_INCREMENT,
        NAME VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10, 2) NOT NULL,
        quantity INT DEFAULT 0,
        category_id INT,
        image_url VARCHAR(500),
        is_featured INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Create cart table
    $conn->query("CREATE TABLE IF NOT EXISTS cart (
        id INT(11) PRIMARY KEY AUTO_INCREMENT,
        user_id INT(11),
        session_id VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        quantity INT(11) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Create cart_items table
    $conn->query("CREATE TABLE IF NOT EXISTS cart_items (
        id INT(11) PRIMARY KEY AUTO_INCREMENT,
        cart_id INT(11),
        product_id INT(11),
        quantity INT(11),
        price DECIMAL(15,2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (cart_id) REFERENCES cart(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Create orders table
    $conn->query("CREATE TABLE IF NOT EXISTS orders (
        id INT PRIMARY KEY AUTO_INCREMENT,
        order_code VARCHAR(50) UNIQUE,
        user_id INT,
        customer_name VARCHAR(255),
        customer_email VARCHAR(255),
        customer_phone VARCHAR(20),
        customer_address TEXT,
        province VARCHAR(100),
        shipping_address TEXT,
        note TEXT,
        subtotal DECIMAL(10, 2) DEFAULT 0,
        shipping_fee DECIMAL(10, 2) DEFAULT 0,
        tax DECIMAL(10, 2) DEFAULT 0,
        discount DECIMAL(10, 2) DEFAULT 0,
        total_amount DECIMAL(10, 2) DEFAULT 0,
        payment_method VARCHAR(50),
        payment_status VARCHAR(50) DEFAULT 'pending',
        order_status VARCHAR(50) DEFAULT 'pending',
        shipping_method VARCHAR(50),
        tracking_number VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Create order_items table
    $conn->query("CREATE TABLE IF NOT EXISTS order_items (
        id INT PRIMARY KEY AUTO_INCREMENT,
        order_id INT,
        product_id INT,
        product_name VARCHAR(255),
        product_price DECIMAL(10, 2),
        quantity INT,
        total_price DECIMAL(10, 2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Create inventory table
    $conn->query("CREATE TABLE IF NOT EXISTS inventory (
        id INT(11) PRIMARY KEY AUTO_INCREMENT,
        product_id INT(11),
        quantity_change INT(11),
        current_quantity INT(11),
        TYPE ENUM('import','export','adjustment','sold','return'),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $_SESSION['tables_created'] = true;
    }
}

// Cache column checks in session to avoid repeated queries
if (!isset($_SESSION['columns_checked'])) {
    // Ensure `role` column exists on users table (for role-based access)
    if ($conn->query("SELECT 1 FROM users LIMIT 1") !== false) {
        $colCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
        if ($colCheck && $colCheck->num_rows === 0) {
            $conn->query("ALTER TABLE users ADD COLUMN role VARCHAR(50) DEFAULT 'user'");
        }
        
        // Ensure `google_id` column exists (for Google OAuth)
        $colCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'google_id'");
        if ($colCheck && $colCheck->num_rows === 0) {
            $conn->query("ALTER TABLE users ADD COLUMN google_id VARCHAR(255)");
        }
        
        // Ensure `avatar_url` column exists
        $colCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'avatar_url'");
        if ($colCheck && $colCheck->num_rows === 0) {
            $conn->query("ALTER TABLE users ADD COLUMN avatar_url VARCHAR(500)");
        }
        
        $_SESSION['columns_checked'] = true;
    }
}

// If a dev admin email is configured, ensure that user exists and set role=admin
if (!empty($DEV_ADMIN_EMAIL)) {
    // If user exists, promote; if not, create a placeholder account with admin role
    $emailEsc = $conn->real_escape_string($DEV_ADMIN_EMAIL);
    $res = $conn->query("SELECT id FROM users WHERE email = '$emailEsc' LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $conn->query("UPDATE users SET role = 'admin' WHERE email = '$emailEsc'");
    } else {
        // create a minimal admin account (no password) — you can change details later
        $conn->query("INSERT INTO users (email, full_name, role, created_at) VALUES ('$emailEsc', 'Dev Admin', 'admin', NOW())");
    }
}

// Create saved_addresses table for storing delivery addresses
$conn->query("CREATE TABLE IF NOT EXISTS saved_addresses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    address_name VARCHAR(100) DEFAULT 'Địa chỉ giao hàng',
    recipient_name VARCHAR(255) NOT NULL,
    recipient_phone VARCHAR(20) NOT NULL,
    province VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Create reviews table for product reviews
$conn->query("CREATE TABLE IF NOT EXISTS reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_order_product_review (order_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

if(!isset($_SESSION['cart_id'])) {
    $_SESSION['cart_id'] = session_id();
}
$cart_session = $_SESSION['cart_id'];
?>