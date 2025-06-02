<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'pos_system');

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if (mysqli_query($conn, $sql)) {
    mysqli_select_db($conn, DB_NAME);
} else {
    die("Error creating database: " . mysqli_error($conn));
}

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'cashier', 'kitchen') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!mysqli_query($conn, $sql)) {
    die("Error creating users table: " . mysqli_error($conn));
}

// Create menu_items table
$sql = "CREATE TABLE IF NOT EXISTS menu_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(50) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!mysqli_query($conn, $sql)) {
    die("Error creating menu_items table: " . mysqli_error($conn));
}

// Create orders table
$sql = "CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'preparing', 'ready', 'completed', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
)";

if (!mysqli_query($conn, $sql)) {
    die("Error creating orders table: " . mysqli_error($conn));
}

// Create order_items table
$sql = "CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    notes TEXT,
    status ENUM('pending', 'preparing', 'ready') DEFAULT 'pending',
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
)";

if (!mysqli_query($conn, $sql)) {
    die("Error creating order_items table: " . mysqli_error($conn));
}

// Insert default admin user if not exists
$admin_username = 'kalana';
$admin_password = password_hash('kalan123', PASSWORD_DEFAULT);

$sql = "INSERT IGNORE INTO users (username, password, role) VALUES (?, ?, 'admin')";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ss", $admin_username, $admin_password);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
?> 