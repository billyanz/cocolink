-- CoCoLink - Database Schema
CREATE DATABASE IF NOT EXISTS `cocolink_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `cocolink_db`;

-- 1. Cooperatives Table
CREATE TABLE IF NOT EXISTS `cooperatives` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(20) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `type` ENUM('induk', 'anak') NOT NULL DEFAULT 'anak',
    `region` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `cooperative_id` INT NULL,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('induk', 'anak', 'buyer', 'driver') NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`cooperative_id`) REFERENCES `cooperatives`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 3. Materials Catalog
CREATE TABLE IF NOT EXISTS `materials` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(30) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `unit` VARCHAR(20) NOT NULL DEFAULT 'Ton',
    `price_per_unit` DECIMAL(12, 2) NOT NULL,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 4. Regional Inventory
CREATE TABLE IF NOT EXISTS `inventory` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `cooperative_id` INT NOT NULL,
    `material_id` INT NOT NULL,
    `available_qty` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `reserved_qty` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `dispatched_qty` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `coop_material_unique` (`cooperative_id`, `material_id`),
    FOREIGN KEY (`cooperative_id`) REFERENCES `cooperatives`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`material_id`) REFERENCES `materials`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. Orders Table
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_number` VARCHAR(30) NOT NULL UNIQUE,
    `buyer_id` INT NOT NULL,
    `total_amount` DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
    `order_status` ENUM('submitted', 'reviewed', 'allocated', 'in_fulfillment', 'partially_fulfilled', 'completed', 'cancelled') DEFAULT 'submitted',
    `payment_status` ENUM('unpaid', 'partially_paid', 'paid', 'credit_note_issued') DEFAULT 'unpaid',
    `shipping_address` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`buyer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Order Items Table
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `material_id` INT NOT NULL,
    `requested_qty` DECIMAL(10, 2) NOT NULL,
    `unit_price` DECIMAL(12, 2) NOT NULL,
    `subtotal` DECIMAL(14, 2) NOT NULL,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`material_id`) REFERENCES `materials`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. Split Allocations Table
CREATE TABLE IF NOT EXISTS `allocations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_item_id` INT NOT NULL,
    `cooperative_id` INT NOT NULL,
    `allocated_qty` DECIMAL(10, 2) NOT NULL,
    `prepared_qty` DECIMAL(10, 2) DEFAULT 0.00,
    `status` ENUM('pending', 'weighed', 'prepared', 'in_transit', 'delivered', 'shortage') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_item_id`) REFERENCES `order_items`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`cooperative_id`) REFERENCES `cooperatives`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. Scale Weighing Records
CREATE TABLE IF NOT EXISTS `weighing_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `allocation_id` INT NOT NULL,
    `gross_weight` DECIMAL(10, 2) NOT NULL,
    `tare_weight` DECIMAL(10, 2) NOT NULL,
    `net_weight` DECIMAL(10, 2) NOT NULL,
    `weighmaster_name` VARCHAR(100) NOT NULL,
    `notes` TEXT,
    `weighed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`allocation_id`) REFERENCES `allocations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 9. Logistics & Shipments
CREATE TABLE IF NOT EXISTS `shipments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tracking_number` VARCHAR(30) NOT NULL UNIQUE,
    `allocation_id` INT NOT NULL,
    `driver_id` INT DEFAULT NULL,
    `dispatched_qty` DECIMAL(10, 2) NOT NULL,
    `delivered_qty` DECIMAL(10, 2) DEFAULT 0.00,
    `status` ENUM('assigned', 'picked_up', 'in_transit', 'delivered', 'failed') DEFAULT 'assigned',
    `delivery_evidence` VARCHAR(255) DEFAULT NULL,
    `dispatched_at` DATETIME NULL,
    `delivered_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`allocation_id`) REFERENCES `allocations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`driver_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 10. Discrepancies & Exception Resolution
CREATE TABLE IF NOT EXISTS `discrepancies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `shipment_id` INT NOT NULL,
    `reported_by` INT NOT NULL,
    `expected_qty` DECIMAL(10, 2) NOT NULL,
    `actual_qty` DECIMAL(10, 2) NOT NULL,
    `shortfall_qty` DECIMAL(10, 2) NOT NULL,
    `status` ENUM('reported', 'resolved') DEFAULT 'reported',
    `resolution_type` ENUM('pending', 'reallocated', 'credit_note') DEFAULT 'pending',
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`shipment_id`) REFERENCES `shipments`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`reported_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 11. Payments Ledger
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `amount` DECIMAL(14, 2) NOT NULL,
    `type` ENUM('payment', 'credit_note', 'adjustment') DEFAULT 'payment',
    `reference_no` VARCHAR(50) NOT NULL,
    `notes` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 12. Audit Logs
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `action` VARCHAR(100) NOT NULL,
    `details` TEXT NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- SEED DEVELOPMENT DATA
INSERT INTO `cooperatives` (`id`, `code`, `name`, `type`, `region`) VALUES
(1, 'INDUK-001', 'Koperasi Induk Coconut Indonesia', 'induk', 'Jakarta Central'),
(2, 'ANAK-001', 'Koperasi Unit Bogor (A1)', 'anak', 'Bogor, West Java'),
(3, 'ANAK-002', 'Koperasi Unit Ciamis (A2)', 'anak', 'Ciamis, West Java'),
(4, 'ANAK-003', 'Koperasi Unit Tasikmalaya (A3)', 'anak', 'Tasikmalaya, West Java');

-- Default passwords: "password123"
INSERT INTO `users` (`id`, `cooperative_id`, `name`, `email`, `password_hash`, `role`, `phone`) VALUES
(1, 1, 'Admin Induk', 'induk@cocolink.id', '$2y$10$4.1k/B31x/3dI4/Q8d8Y2Oa75fOaJpI2b7vL5vN1V/e7K9hBqgM0S', 'induk', '081100001111'),
(2, 2, 'Coop Admin Bogor (A1)', 'bogor@cocolink.id', '$2y$10$4.1k/B31x/3dI4/Q8d8Y2Oa75fOaJpI2b7vL5vN1V/e7K9hBqgM0S', 'anak', '081222223333'),
(3, 3, 'Coop Admin Ciamis (A2)', 'ciamis@cocolink.id', '$2y$10$4.1k/B31x/3dI4/Q8d8Y2Oa75fOaJpI2b7vL5vN1V/e7K9hBqgM0S', 'anak', '081333334444'),
(4, NULL, 'PT X Industri', 'buyer@industri.com', '$2y$10$4.1k/B31x/3dI4/Q8d8Y2Oa75fOaJpI2b7vL5vN1V/e7K9hBqgM0S', 'buyer', '081444445555'),
(5, NULL, 'Budi Santoso (Driver)', 'driver@cocolink.id', '$2y$10$4.1k/B31x/3dI4/Q8d8Y2Oa75fOaJpI2b7vL5vN1V/e7K9hBqgM0S', 'driver', '081555556666');

INSERT INTO `materials` (`id`, `code`, `name`, `unit`, `price_per_unit`, `description`) VALUES
(1, 'MAT-SHELL-01', 'Coconut Shell Charcoal (Arang Tempurung)', 'Ton', 4500000.00, 'High-density coconut charcoal suitable for activated carbon industrial processing.'),
(2, 'MAT-HUSK-01', 'Raw Coconut Husk (Sabut Kelapa Raw)', 'Ton', 1800000.00, 'Fibrous raw husk harvested for eco-textile and coir mattress manufacturing.'),
(3, 'MAT-WATER-01', 'Bulk Coconut Water', 'Liter', 3500.00, 'Filtered raw coconut water for bio-fertilizers and industrial fermentation.');

INSERT INTO `inventory` (`cooperative_id`, `material_id`, `available_qty`, `reserved_qty`, `dispatched_qty`) VALUES
(2, 1, 80.00, 0.00, 0.00), -- Bogor
(3, 1, 15.00, 0.00, 0.00), -- Ciamis (for shortage simulation)
(4, 1, 40.00, 0.00, 0.00); -- Tasikmalaya