CREATE DATABASE IF NOT EXISTS cocolink CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cocolink;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS audit_logs, shipment_events, shipments, order_items, orders, production_logs, products, allocations, raw_batches, users, buyer_profiles, cooperatives, suppliers, raw_types;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE raw_types (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(90) NOT NULL UNIQUE,
 examples VARCHAR(255) NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE suppliers (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 legal_name VARCHAR(160) NOT NULL,
 nib VARCHAR(30) NOT NULL UNIQUE,
 province VARCHAR(90) NOT NULL,
 city VARCHAR(90) NOT NULL,
 address TEXT NOT NULL,
 latitude DECIMAL(10,7) NOT NULL,
 longitude DECIMAL(10,7) NOT NULL,
 status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
 verified_by BIGINT UNSIGNED NULL,
 verified_at DATETIME NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_supplier_status (status)
) ENGINE=InnoDB;

CREATE TABLE cooperatives (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 supplier_id BIGINT UNSIGNED NOT NULL,
 name VARCHAR(160) NOT NULL,
 province VARCHAR(90) NOT NULL,
 city VARCHAR(90) NOT NULL,
 address TEXT NOT NULL,
 specialty_raw_type_id INT UNSIGNED NOT NULL,
 latitude DECIMAL(10,7) NULL,
 longitude DECIMAL(10,7) NULL,
 status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
 verified_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_coop_supplier_name (supplier_id, name),
 INDEX idx_coop_supplier_status (supplier_id, status),
 CONSTRAINT fk_coop_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
 CONSTRAINT fk_coop_raw FOREIGN KEY (specialty_raw_type_id) REFERENCES raw_types(id)
) ENGINE=InnoDB;

CREATE TABLE buyer_profiles (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 company_name VARCHAR(160) NOT NULL,
 buyer_type ENUM('industry','collector') NOT NULL DEFAULT 'industry',
 nib VARCHAR(30) NULL,
 address TEXT NOT NULL,
 city VARCHAR(90) NOT NULL,
 status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('super_admin','induk','supplier','coop','buyer','driver') NOT NULL,
 supplier_id BIGINT UNSIGNED NULL,
 coop_id BIGINT UNSIGNED NULL,
 buyer_id BIGINT UNSIGNED NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_user_role (role),
 CONSTRAINT fk_user_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
 CONSTRAINT fk_user_coop FOREIGN KEY (coop_id) REFERENCES cooperatives(id),
 CONSTRAINT fk_user_buyer FOREIGN KEY (buyer_id) REFERENCES buyer_profiles(id)
) ENGINE=InnoDB;

CREATE TABLE raw_batches (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 supplier_id BIGINT UNSIGNED NOT NULL,
 raw_type_id INT UNSIGNED NOT NULL,
 batch_code VARCHAR(30) NOT NULL UNIQUE,
 quantity DECIMAL(13,2) NOT NULL,
 unit VARCHAR(12) NOT NULL DEFAULT 'kg',
 notes TEXT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_batch_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
 CONSTRAINT fk_batch_type FOREIGN KEY (raw_type_id) REFERENCES raw_types(id),
 CONSTRAINT chk_batch_qty CHECK (quantity > 0)
) ENGINE=InnoDB;

CREATE TABLE allocations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 batch_id BIGINT UNSIGNED NOT NULL,
 coop_id BIGINT UNSIGNED NOT NULL,
 quantity DECIMAL(13,2) NOT NULL,
 status ENUM('proposed','accepted','rejected','completed') NOT NULL DEFAULT 'proposed',
 note TEXT NULL,
 created_by BIGINT UNSIGNED NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_allocation_status (status),
 CONSTRAINT fk_all_batch FOREIGN KEY (batch_id) REFERENCES raw_batches(id),
 CONSTRAINT fk_all_coop FOREIGN KEY (coop_id) REFERENCES cooperatives(id),
 CONSTRAINT fk_all_creator FOREIGN KEY (created_by) REFERENCES users(id),
 CONSTRAINT chk_all_qty CHECK (quantity > 0)
) ENGINE=InnoDB;

CREATE TABLE products (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 coop_id BIGINT UNSIGNED NOT NULL,
 raw_type_id INT UNSIGNED NOT NULL,
 sku VARCHAR(40) NOT NULL UNIQUE,
 name VARCHAR(160) NOT NULL,
 description TEXT NULL,
 unit VARCHAR(15) NOT NULL DEFAULT 'kg',
 price DECIMAL(15,2) NOT NULL,
 stock DECIMAL(13,2) NOT NULL DEFAULT 0,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_product_coop (coop_id),
 CONSTRAINT fk_product_coop FOREIGN KEY (coop_id) REFERENCES cooperatives(id),
 CONSTRAINT fk_product_raw FOREIGN KEY (raw_type_id) REFERENCES raw_types(id),
 CONSTRAINT chk_prod_stock CHECK (stock >= 0),
 CONSTRAINT chk_prod_price CHECK (price >= 0)
) ENGINE=InnoDB;

CREATE TABLE production_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 allocation_id BIGINT UNSIGNED NOT NULL,
 product_id BIGINT UNSIGNED NOT NULL,
 input_qty DECIMAL(13,2) NOT NULL,
 output_qty DECIMAL(13,2) NOT NULL,
 notes TEXT NULL,
 created_by BIGINT UNSIGNED NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_prodlog_all FOREIGN KEY (allocation_id) REFERENCES allocations(id),
 CONSTRAINT fk_prodlog_product FOREIGN KEY (product_id) REFERENCES products(id),
 CONSTRAINT fk_prodlog_user FOREIGN KEY (created_by) REFERENCES users(id),
 CONSTRAINT chk_prodlog_input CHECK (input_qty > 0),
 CONSTRAINT chk_prodlog_output CHECK (output_qty > 0)
) ENGINE=InnoDB;

CREATE TABLE orders (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_code VARCHAR(40) NOT NULL UNIQUE,
 buyer_id BIGINT UNSIGNED NOT NULL,
 shipping_address TEXT NOT NULL,
 city VARCHAR(90) NOT NULL,
 notes TEXT NULL,
 status ENUM('requested','approved','rejected','assigned','in_transit','delivered','completed') NOT NULL DEFAULT 'requested',
 total DECIMAL(17,2) NOT NULL DEFAULT 0,
 approved_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_order_status (status),
 INDEX idx_order_buyer (buyer_id),
 CONSTRAINT fk_order_buyer FOREIGN KEY (buyer_id) REFERENCES buyer_profiles(id),
 CONSTRAINT fk_order_approver FOREIGN KEY (approved_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE order_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NOT NULL,
 product_id BIGINT UNSIGNED NOT NULL,
 quantity DECIMAL(13,2) NOT NULL,
 unit_price DECIMAL(15,2) NOT NULL,
 CONSTRAINT fk_item_order FOREIGN KEY (order_id) REFERENCES orders(id),
 CONSTRAINT fk_item_product FOREIGN KEY (product_id) REFERENCES products(id),
 CONSTRAINT chk_item_qty CHECK (quantity > 0)
) ENGINE=InnoDB;

CREATE TABLE shipments (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NOT NULL UNIQUE,
 driver_id BIGINT UNSIGNED NOT NULL,
 status ENUM('assigned','accepted','pickup_pending','picked_up','delivered') NOT NULL DEFAULT 'assigned',
 assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 picked_up_at DATETIME NULL,
 delivered_at DATETIME NULL,
 CONSTRAINT fk_ship_order FOREIGN KEY (order_id) REFERENCES orders(id),
 CONSTRAINT fk_ship_driver FOREIGN KEY (driver_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE shipment_events (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 shipment_id BIGINT UNSIGNED NOT NULL,
 event_type ENUM('assigned','accepted','pickup_requested','pickup_rejected','picked_up','location','delivered') NOT NULL,
 note TEXT NOT NULL,
 latitude DECIMAL(10,7) NULL,
 longitude DECIMAL(10,7) NULL,
 proof_path VARCHAR(255) NULL,
 actor_id BIGINT UNSIGNED NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_event_ship_time (shipment_id, created_at),
 CONSTRAINT fk_event_ship FOREIGN KEY (shipment_id) REFERENCES shipments(id),
 CONSTRAINT fk_event_actor FOREIGN KEY (actor_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NULL,
 action VARCHAR(90) NOT NULL,
 entity_type VARCHAR(60) NOT NULL,
 entity_id BIGINT UNSIGNED NULL,
 details TEXT NULL,
 ip VARCHAR(45) NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_audit_time (created_at),
 CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

INSERT INTO raw_types (name, examples) VALUES
('Daun kelapa', 'Sapu lidi, anyaman, bahan kompos'),
('Tempurung kelapa', 'Arang, briket, karbon aktif'),
('Sabut kelapa', 'Cocopeat, cocofiber, keset'),
('Batang kelapa', 'Papan, furnitur, kerajinan'),
('Air kelapa', 'Nata de coco, pupuk cair, fermentasi'),
('Daging / ampas kelapa', 'Minyak, pakan, tepung ampas');
