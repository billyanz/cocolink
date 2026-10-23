CREATE DATABASE IF NOT EXISTS cocolink_db;
USE cocolink_db;

-- 1. Tabel Pengguna (Multi-Aktor)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('parent_coop', 'child_coop', 'buyer', 'driver') NOT NULL,
    parent_id INT NULL,
    phone VARCHAR(20),
    address TEXT,
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE SET NULL
);

-- 2. Kategori Limbah Kelapa
CREATE TABLE IF NOT EXISTS waste_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    unit VARCHAR(20) DEFAULT 'Ton'
);

-- 3. Pesanan dari Pengepul / Buyer (PT. X, Y, Z)
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    buyer_id INT NOT NULL,
    waste_category_id INT NOT NULL,
    total_quantity DECIMAL(10,2) NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    total_price DECIMAL(15,2) NOT NULL,
    status ENUM('pending', 'allocated', 'in_processing', 'in_transit', 'completed', 'cancelled') DEFAULT 'pending',
    delivery_address TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES users(id),
    FOREIGN KEY (waste_category_id) REFERENCES waste_categories(id)
);

-- 4. Pembagian Kuota Pesanan ke Koperasi Anak (A1, A2, dst)
CREATE TABLE IF NOT EXISTS order_allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    child_coop_id INT NOT NULL,
    allocated_quantity DECIMAL(10,2) NOT NULL,
    status ENUM('assigned', 'preparing', 'ready_for_pickup', 'completed') DEFAULT 'assigned',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (child_coop_id) REFERENCES users(id)
);

-- 5. Pengiriman oleh Driver Logistik
CREATE TABLE IF NOT EXISTS shipments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tracking_number VARCHAR(50) UNIQUE NOT NULL,
    allocation_id INT NOT NULL,
    driver_id INT NULL,
    status ENUM('waiting_driver', 'picked_up', 'delivering', 'delivered') DEFAULT 'waiting_driver',
    picked_up_at DATETIME NULL,
    delivered_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (allocation_id) REFERENCES order_allocations(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES users(id)
);

-- Seed Data Awal Kategori Limbah
INSERT INTO waste_categories (id, name, unit) VALUES 
(1, 'Arang Batok Kelapa', 'Ton'),
(2, 'Sabut Kelapa', 'Ton'),
(3, 'Minyak/Air Kelapa', 'Ton')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Seed Data Dummy Pengguna
INSERT INTO users (id, name, email, password, role, parent_id, phone, address) VALUES
(1, 'PT. X Industri', 'buyer@ptx.com', '$2y$10$abcdefghijklmnopqrstuuu', 'buyer', NULL, '081234567890', 'Kawasan Industri Jakarta'),
(2, 'Koperasi Induk CoCoLink', 'induk@cocolink.org', '$2y$10$abcdefghijklmnopqrstuuu', 'parent_coop', NULL, '081299998888', 'Pusat Operasi CoCoLink'),
(3, 'Koperasi Anak A1', 'a1@cocolink.org', '$2y$10$abcdefghijklmnopqrstuuu', 'child_coop', 2, '081211112222', 'Bogor Barat'),
(4, 'Koperasi Anak A2', 'a2@cocolink.org', '$2y$10$abcdefghijklmnopqrstuuu', 'child_coop', 2, '081233334444', 'Cisarua Bogor'),
(5, 'Budi Logistik (Driver)', 'driver@cocolink.org', '$2y$10$abcdefghijklmnopqrstuuu', 'driver', NULL, '081255556666', 'Armada Jakarta')
ON DUPLICATE KEY UPDATE name=VALUES(name);