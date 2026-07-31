CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('customer','staff','admin') NOT NULL DEFAULT 'customer',
    balance DECIMAL(18,2) NOT NULL DEFAULT 0,
    status ENUM('active','suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role_status (role, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL UNIQUE,
    description TEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    status TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT UNSIGNED NULL,
    name VARCHAR(190) NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE,
    description TEXT NULL,
    product_type ENUM('digital','proxy','social','service') NOT NULL DEFAULT 'digital',
    price DECIMAL(18,2) NOT NULL DEFAULT 0,
    stock_count INT NOT NULL DEFAULT 0,
    provider_code VARCHAR(80) NULL,
    status ENUM('draft','active','hidden') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_products_type_status (product_type, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_stock (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    secret_payload TEXT NOT NULL,
    status ENUM('available','reserved','sold','disabled') NOT NULL DEFAULT 'available',
    sold_order_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_stock_available (product_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    order_code VARCHAR(40) NOT NULL UNIQUE,
    subtotal DECIMAL(18,2) NOT NULL DEFAULT 0,
    total DECIMAL(18,2) NOT NULL DEFAULT 0,
    status ENUM('pending','paid','processing','completed','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
    provider_order_id VARCHAR(190) NULL,
    provider_payload JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_orders_user_status (user_id, status),
    INDEX idx_orders_provider (provider_order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(18,2) NOT NULL DEFAULT 0,
    metadata JSON NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wallet_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    direction ENUM('credit','debit') NOT NULL,
    amount DECIMAL(18,2) NOT NULL,
    balance_before DECIMAL(18,2) NOT NULL,
    balance_after DECIMAL(18,2) NOT NULL,
    provider VARCHAR(80) NULL,
    external_id VARCHAR(190) NULL,
    description VARCHAR(255) NOT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    UNIQUE KEY uq_wallet_external (provider, external_id),
    INDEX idx_wallet_user_created (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wallet_deposits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    provider VARCHAR(80) NOT NULL,
    external_id VARCHAR(190) NULL,
    deposit_code VARCHAR(40) NULL,
    amount DECIMAL(18,2) NOT NULL,
    status ENUM('pending','paid','failed','cancelled') NOT NULL DEFAULT 'pending',
    description VARCHAR(255) NULL,
    provider_payload JSON NULL,
    paid_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    UNIQUE KEY uq_deposit_provider_external (provider, external_id),
    UNIQUE KEY uq_deposit_code (deposit_code),
    INDEX idx_deposit_user_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS proxy_orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    order_id BIGINT UNSIGNED NULL,
    provider_order_id VARCHAR(190) NULL,
    provider_order_number VARCHAR(190) NULL,
    proxy_type VARCHAR(40) NOT NULL,
    country_code VARCHAR(8) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    rent_period_days INT NOT NULL,
    auth_mode ENUM('login_password','ip_whitelist') NOT NULL DEFAULT 'login_password',
    auto_renew TINYINT(1) NOT NULL DEFAULT 0,
    provider_payload JSON NULL,
    status ENUM('pending','active','expired','failed','cancelled') NOT NULL DEFAULT 'pending',
    expires_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    INDEX idx_proxy_user_status (user_id, status),
    INDEX idx_proxy_expiry (status, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS social_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    service ENUM('get_link_facebook','up_facebook','up_instagram') NOT NULL,
    status ENUM('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
    provider_request_id VARCHAR(190) NULL,
    request_payload JSON NULL,
    result_payload JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_social_user_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
    name VARCHAR(120) PRIMARY KEY,
    value TEXT NULL,
    is_secret TINYINT(1) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(120) NOT NULL,
    target_type VARCHAR(80) NULL,
    target_id VARCHAR(80) NULL,
    ip_address VARCHAR(45) NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_audit_action_created (action, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cron_runs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_name VARCHAR(120) NOT NULL,
    status ENUM('running','success','failed') NOT NULL,
    summary VARCHAR(255) NULL,
    duration_ms INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cron_job_created (job_name, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (name, value) VALUES
('site_name', 'Caffemmo'),
('maintenance_mode', '0')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO categories (name, slug, description, sort_order, status) VALUES
('Proxy premium', 'proxy-premium', 'Proxy IPv4, IPv6, ISP và Mobile.', 10, 1),
('Tài nguyên số', 'tai-nguyen-so', 'Các sản phẩm tài nguyên số có sẵn.', 20, 1),
('Dịch vụ mạng xã hội', 'dich-vu-mang-xa-hoi', 'Các dịch vụ xử lý theo yêu cầu.', 30, 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), sort_order = VALUES(sort_order), status = VALUES(status);

INSERT INTO products (category_id, name, slug, description, product_type, price, stock_count, provider_code, status)
SELECT id, 'Proxy IPv4 premium', 'proxy-ipv4-premium', 'Cấu hình proxy login/password với thời hạn linh hoạt.', 'proxy', 33800, 0, 'ipv4', 'active'
FROM categories WHERE slug = 'proxy-premium'
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), price = VALUES(price), status = VALUES(status);

INSERT INTO products (category_id, name, slug, description, product_type, price, stock_count, provider_code, status)
SELECT id, 'Get Link Facebook', 'get-link-facebook', 'Nhận link xác minh theo quy trình provider.', 'social', 10000, 0, 'get_link_facebook', 'active'
FROM categories WHERE slug = 'dich-vu-mang-xa-hoi'
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), price = VALUES(price), status = VALUES(status);

INSERT INTO products (category_id, name, slug, description, product_type, price, stock_count, provider_code, status)
SELECT id, 'Up tích Facebook', 'up-tich-facebook', 'Gửi yêu cầu xác minh Facebook.', 'social', 15000, 0, 'up_facebook', 'active'
FROM categories WHERE slug = 'dich-vu-mang-xa-hoi'
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), price = VALUES(price), status = VALUES(status);

INSERT INTO products (category_id, name, slug, description, product_type, price, stock_count, provider_code, status)
SELECT id, 'Up tích Instagram', 'up-tich-instagram', 'Gửi yêu cầu xác minh Instagram.', 'social', 15000, 0, 'up_instagram', 'active'
FROM categories WHERE slug = 'dich-vu-mang-xa-hoi'
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), price = VALUES(price), status = VALUES(status);
