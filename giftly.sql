-- =====================================================================
-- GIFTLY DATABASE  |  MySQL 8.0+ / MariaDB 10.5+  |  InnoDB, utf8mb4
-- File: database/giftly.sql
-- 12 core tables + 1 optional table (contact_messages)
-- Security notes:
--   * Passwords: store only PHP password_hash() output in users.password_hash
--   * Payments: never store card numbers or CVV, only gateway references
--   * order_items stores the price at purchase time so old orders stay accurate
-- =====================================================================

CREATE DATABASE IF NOT EXISTS giftly
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE giftly;

-- ---------------------------------------------------------------------
-- 1. roles: defines Customer and Admin
-- ---------------------------------------------------------------------
CREATE TABLE roles (
  role_id      TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  role_name    VARCHAR(30)      NOT NULL,
  description  VARCHAR(150)     NULL,
  PRIMARY KEY (role_id),
  UNIQUE KEY uq_roles_name (role_name)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. users: customer and administrator accounts
-- ---------------------------------------------------------------------
CREATE TABLE users (
  user_id        INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  role_id        TINYINT UNSIGNED NOT NULL,
  first_name     VARCHAR(60)      NOT NULL,
  last_name      VARCHAR(60)      NOT NULL,
  email          VARCHAR(150)     NOT NULL,
  phone          VARCHAR(20)      NULL,
  password_hash  VARCHAR(255)     NOT NULL,
  is_active      TINYINT(1)       NOT NULL DEFAULT 1,
  created_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role (role_id),
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (role_id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. categories: Perfumes, Mugs, Gift Boxes, etc.
-- ---------------------------------------------------------------------
CREATE TABLE categories (
  category_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  category_name  VARCHAR(80)  NOT NULL,
  slug           VARCHAR(100) NOT NULL,
  description    TEXT         NULL,
  image_path     VARCHAR(255) NULL,
  is_active      TINYINT(1)   NOT NULL DEFAULT 1,
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (category_id),
  UNIQUE KEY uq_categories_name (category_name),
  UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. products: details, prices, images and stock
-- ---------------------------------------------------------------------
CREATE TABLE products (
  product_id      INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  category_id     INT UNSIGNED  NOT NULL,
  product_name    VARCHAR(150)  NOT NULL,
  slug            VARCHAR(170)  NOT NULL,
  description     TEXT          NULL,
  price           DECIMAL(10,2) NOT NULL,
  image_path      VARCHAR(255)  NULL,
  stock_quantity  INT UNSIGNED  NOT NULL DEFAULT 0,
  is_active       TINYINT(1)    NOT NULL DEFAULT 1,
  is_featured     TINYINT(1)    NOT NULL DEFAULT 0,
  created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (product_id),
  UNIQUE KEY uq_products_slug (slug),
  KEY idx_products_category (category_id),
  KEY idx_products_price (price),
  KEY idx_products_active (is_active, is_featured),
  FULLTEXT KEY ft_products_search (product_name, description),
  CONSTRAINT chk_products_price CHECK (price >= 0),
  CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories (category_id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. occasions: Graduation, Wedding, Anniversary, Birthday, etc.
-- ---------------------------------------------------------------------
CREATE TABLE occasions (
  occasion_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  occasion_name  VARCHAR(80)  NOT NULL,
  slug           VARCHAR(100) NOT NULL,
  description    VARCHAR(255) NULL,
  image_path     VARCHAR(255) NULL,
  is_active      TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (occasion_id),
  UNIQUE KEY uq_occasions_name (occasion_name),
  UNIQUE KEY uq_occasions_slug (slug)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. product_occasions: many-to-many link (one product, many occasions)
-- ---------------------------------------------------------------------
CREATE TABLE product_occasions (
  product_id   INT UNSIGNED NOT NULL,
  occasion_id  INT UNSIGNED NOT NULL,
  PRIMARY KEY (product_id, occasion_id),
  KEY idx_po_occasion (occasion_id),
  CONSTRAINT fk_po_product  FOREIGN KEY (product_id)  REFERENCES products (product_id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_po_occasion FOREIGN KEY (occasion_id) REFERENCES occasions (occasion_id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 7. product_preferences: gift characteristic tags per product
--    Examples: luxury, personalised, romantic, practical, handmade
-- ---------------------------------------------------------------------
CREATE TABLE product_preferences (
  preference_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id      INT UNSIGNED NOT NULL,
  preference_tag  VARCHAR(50)  NOT NULL,
  PRIMARY KEY (preference_id),
  UNIQUE KEY uq_pref_product_tag (product_id, preference_tag),
  KEY idx_pref_tag (preference_tag),
  CONSTRAINT fk_pref_product FOREIGN KEY (product_id) REFERENCES products (product_id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 8. carts: one cart per customer, or per guest session
-- ---------------------------------------------------------------------
CREATE TABLE carts (
  cart_id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED NULL,
  session_id  VARCHAR(128) NULL,
  status      ENUM('active','converted','abandoned') NOT NULL DEFAULT 'active',
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (cart_id),
  KEY idx_carts_user (user_id, status),
  KEY idx_carts_session (session_id),
  CONSTRAINT fk_carts_user FOREIGN KEY (user_id) REFERENCES users (user_id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
-- Application rule: every cart must have either a user_id or a session_id.

-- ---------------------------------------------------------------------
-- 9. cart_items: products and quantities in each cart
-- ---------------------------------------------------------------------
CREATE TABLE cart_items (
  cart_item_id  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  cart_id       INT UNSIGNED NOT NULL,
  product_id    INT UNSIGNED NOT NULL,
  quantity      INT UNSIGNED NOT NULL DEFAULT 1,
  added_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (cart_item_id),
  UNIQUE KEY uq_cart_product (cart_id, product_id),
  KEY idx_ci_product (product_id),
  CONSTRAINT chk_cart_items_qty CHECK (quantity > 0),
  CONSTRAINT fk_ci_cart    FOREIGN KEY (cart_id)    REFERENCES carts (cart_id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_ci_product FOREIGN KEY (product_id) REFERENCES products (product_id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 10. orders: customer orders, delivery details and status
-- ---------------------------------------------------------------------
CREATE TABLE orders (
  order_id         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  order_number     VARCHAR(25)   NOT NULL,
  user_id          INT UNSIGNED  NOT NULL,
  recipient_name   VARCHAR(120)  NOT NULL,
  contact_phone    VARCHAR(20)   NOT NULL,
  delivery_method  ENUM('delivery','pickup') NOT NULL DEFAULT 'delivery',
  delivery_address VARCHAR(255)  NULL,
  city             VARCHAR(80)   NULL,
  delivery_notes   VARCHAR(255)  NULL,
  gift_message     VARCHAR(255)  NULL,
  subtotal         DECIMAL(10,2) NOT NULL,
  delivery_fee     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total_amount     DECIMAL(10,2) NOT NULL,
  order_status     ENUM('pending','confirmed','processing','out_for_delivery','delivered','cancelled')
                   NOT NULL DEFAULT 'pending',
  placed_at        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (order_id),
  UNIQUE KEY uq_orders_number (order_number),
  KEY idx_orders_user (user_id, placed_at),
  KEY idx_orders_status (order_status),
  CONSTRAINT chk_orders_amounts CHECK (subtotal >= 0 AND delivery_fee >= 0 AND total_amount >= 0),
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users (user_id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;
-- Application rule: delivery_address and city are required when delivery_method = 'delivery'.

-- ---------------------------------------------------------------------
-- 11. order_items: products and quantities in each order
--     product_name and unit_price are copied at purchase time
-- ---------------------------------------------------------------------
CREATE TABLE order_items (
  order_item_id  INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  order_id       INT UNSIGNED  NOT NULL,
  product_id     INT UNSIGNED  NOT NULL,
  product_name   VARCHAR(150)  NOT NULL,
  unit_price     DECIMAL(10,2) NOT NULL,
  quantity       INT UNSIGNED  NOT NULL,
  line_total     DECIMAL(12,2) AS (unit_price * quantity) STORED,
  PRIMARY KEY (order_item_id),
  KEY idx_oi_order (order_id),
  KEY idx_oi_product (product_id),
  CONSTRAINT chk_oi_values CHECK (unit_price >= 0 AND quantity > 0),
  CONSTRAINT fk_oi_order   FOREIGN KEY (order_id)   REFERENCES orders (order_id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_oi_product FOREIGN KEY (product_id) REFERENCES products (product_id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 12. payments: references, amounts and status (no card data)
--     An order can have several attempts, for example a failed then a successful one
-- ---------------------------------------------------------------------
CREATE TABLE payments (
  payment_id         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  order_id           INT UNSIGNED  NOT NULL,
  payment_method     ENUM('mobile_money','card','cash_on_delivery') NOT NULL,
  provider           VARCHAR(40)   NULL,
  gateway_reference  VARCHAR(100)  NULL,
  amount             DECIMAL(10,2) NOT NULL,
  currency           CHAR(3)       NOT NULL DEFAULT 'GHS',
  payment_status     ENUM('pending','successful','failed','refunded') NOT NULL DEFAULT 'pending',
  paid_at            DATETIME      NULL,
  created_at         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (payment_id),
  UNIQUE KEY uq_payments_reference (gateway_reference),
  KEY idx_payments_order (order_id),
  KEY idx_payments_status (payment_status),
  CONSTRAINT chk_payments_amount CHECK (amount >= 0),
  CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders (order_id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 13. contact_messages (optional): messages from the Contact page
-- ---------------------------------------------------------------------
CREATE TABLE contact_messages (
  message_id  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name   VARCHAR(120) NOT NULL,
  email       VARCHAR(150) NOT NULL,
  subject     VARCHAR(150) NULL,
  message     TEXT         NOT NULL,
  is_read     TINYINT(1)   NOT NULL DEFAULT 0,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (message_id),
  KEY idx_cm_read (is_read, created_at)
) ENGINE=InnoDB;

-- =====================================================================
-- STARTER DATA (safe to edit)
-- =====================================================================
INSERT INTO roles (role_name, description) VALUES
  ('Customer', 'Can browse, order and manage their own account'),
  ('Admin',    'Can manage products, categories, orders and customers');

INSERT INTO occasions (occasion_name, slug) VALUES
  ('Wedding',      'wedding'),
  ('Anniversary',  'anniversary'),
  ('Graduation',   'graduation'),
  ('Birthday',     'birthday'),
  ('Thank You',    'thank-you'),
  ('Just Because', 'just-because');

INSERT INTO categories (category_name, slug) VALUES
  ('Gift Boxes',          'gift-boxes'),
  ('Perfumes',            'perfumes'),
  ('Mugs',                'mugs'),
  ('Jewelry',             'jewelry'),
  ('Bouquets',            'bouquets'),
  ('Teddy Bears',         'teddy-bears'),
  ('Tech Gifts',          'tech-gifts'),
  ('Watches',             'watches'),
  ('Personalized Gifts',  'personalized-gifts'),
  ('Home & Decor',        'home-decor'),
  ('Cakes & Treats',      'cakes-treats'),
  ('Chocolates',          'chocolates'),
  ('Bags & Accessories',  'bags-accessories');

-- =====================================================================
-- EXAMPLE: guided recommendation query
-- Occasion = Birthday, preference tag = romantic, category = Perfumes, budget GHS 50 to 100
-- =====================================================================
-- SELECT DISTINCT p.product_id, p.product_name, p.price, p.image_path
-- FROM products p
-- JOIN product_occasions po ON po.product_id = p.product_id
-- JOIN occasions o          ON o.occasion_id = po.occasion_id
-- JOIN categories c         ON c.category_id = p.category_id
-- LEFT JOIN product_preferences pp ON pp.product_id = p.product_id
-- WHERE p.is_active = 1
--   AND p.stock_quantity > 0
--   AND o.slug = 'birthday'
--   AND c.slug = 'perfumes'
--   AND p.price BETWEEN 50 AND 100
--   AND (pp.preference_tag = 'romantic' OR pp.preference_tag IS NULL)
-- ORDER BY p.is_featured DESC, p.price ASC;
