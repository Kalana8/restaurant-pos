-- Create database
CREATE DATABASE IF NOT EXISTS pos_system;
USE pos_system;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'cashier', 'kitchen') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create menu_items table
CREATE TABLE IF NOT EXISTS menu_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(50) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'preparing', 'ready', 'completed', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Create order_items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    notes TEXT,
    status ENUM('pending', 'preparing', 'ready') DEFAULT 'pending',
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
);

-- Insert default admin user
INSERT INTO users (username, password, role) VALUES 
('kalana', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert sample menu items
INSERT INTO menu_items (name, description, price, category, status) VALUES
('Margherita Pizza', 'Classic pizza with tomato sauce and mozzarella', 12.99, 'Pizza', 'active'),
('Pepperoni Pizza', 'Pizza topped with pepperoni and cheese', 14.99, 'Pizza', 'active'),
('Caesar Salad', 'Fresh romaine lettuce with Caesar dressing', 8.99, 'Salads', 'active'),
('Chicken Wings', '8 pieces of crispy chicken wings', 10.99, 'Appetizers', 'active'),
('Pasta Carbonara', 'Creamy pasta with bacon and parmesan', 13.99, 'Pasta', 'active'),
('Chocolate Cake', 'Rich chocolate cake with ganache', 6.99, 'Desserts', 'active'),
('Soft Drinks', 'Choice of Coke, Sprite, or Fanta', 2.99, 'Beverages', 'active'),
('Garlic Bread', 'Toasted bread with garlic butter', 4.99, 'Appetizers', 'active'),
('Greek Salad', 'Mixed greens with feta and olives', 9.99, 'Salads', 'active'),
('Tiramisu', 'Classic Italian dessert', 7.99, 'Desserts', 'active'); 