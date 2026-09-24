-- MojaPonuda — complete database schema.
--
-- Tables use IF NOT EXISTS, so the initialization script can be run
-- multiple times without recreating existing tables.
-- It runs automatically when the app container starts
-- (database/init-db.php), or manually with:
--   mysql pva_db < schema.sql


-- --- Users ---
CREATE TABLE IF NOT EXISTS users (
    user_id     INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    lastname    VARCHAR(100) NOT NULL,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    email       VARCHAR(255) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    avatar_path VARCHAR(255) NULL,
    bio         VARCHAR(500) NULL,
    role        ENUM('user','admin','superadmin') NOT NULL DEFAULT 'user',
    status      ENUM('active','blocked') NOT NULL DEFAULT 'active',
    is_deleted  TINYINT(1) NOT NULL DEFAULT 0,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --- Categories ---
CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(1000) NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --- Listings ---
CREATE TABLE IF NOT EXISTS listings (
    listing_id      INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    description     VARCHAR(1000) NOT NULL,
    starting_price  DECIMAL(12,2) NOT NULL DEFAULT 0,
    current_price   DECIMAL(12,2) NOT NULL DEFAULT 0,
    started_at      DATETIME NULL,
    ended_at        DATETIME NULL,
    ended_at_actual DATETIME NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status          ENUM('active','paused','draft','sold','expired') NOT NULL DEFAULT 'draft',
    listing_type    ENUM('auction','fixed_price') NOT NULL DEFAULT 'fixed_price',
    user_id         INT NOT NULL,
    category_id     INT NULL,
    buyer_id        INT NULL,
    is_deleted      TINYINT(1) NOT NULL DEFAULT 0,
    is_featured     TINYINT(1) NOT NULL DEFAULT 0,
    views           INT NOT NULL DEFAULT 0,
    INDEX idx_listings_user (user_id),
    INDEX idx_listings_category (category_id),
    INDEX idx_listings_status (status),
    INDEX idx_listings_type (listing_type),
    CONSTRAINT fk_listings_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_listings_category FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL,
    CONSTRAINT fk_listings_buyer FOREIGN KEY (buyer_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --- Listing images ---
CREATE TABLE IF NOT EXISTS listings_images (
    image_id       INT AUTO_INCREMENT PRIMARY KEY,
    listing_id     INT NOT NULL,
    image_path     VARCHAR(255) NOT NULL,
    image_position INT NOT NULL DEFAULT 0,
    INDEX idx_images_listing (listing_id),
    CONSTRAINT fk_images_listing FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --- Auction bids ---
CREATE TABLE IF NOT EXISTS bids (
    bid_id     INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL,
    user_id    INT NOT NULL,
    price      DECIMAL(12,2) NOT NULL,
    bid_date   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_bids_listing (listing_id),
    INDEX idx_bids_user (user_id),
    CONSTRAINT fk_bids_listing FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE CASCADE,
    CONSTRAINT fk_bids_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --- Conversations ---
CREATE TABLE IF NOT EXISTS conversations (
    conversation_id     INT AUTO_INCREMENT PRIMARY KEY,
    listing_id          INT NOT NULL,
    buyer_id            INT NOT NULL,
    seller_id           INT NOT NULL,
    buyer_last_read_at  DATETIME NULL,
    seller_last_read_at DATETIME NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_conversation (listing_id, buyer_id),
    INDEX idx_conv_buyer (buyer_id),
    INDEX idx_conv_seller (seller_id),
    CONSTRAINT fk_conv_listing FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE CASCADE,
    CONSTRAINT fk_conv_buyer FOREIGN KEY (buyer_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_conv_seller FOREIGN KEY (seller_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --- Messages ---
CREATE TABLE IF NOT EXISTS messages (
    message_id      INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_user_id  INT NOT NULL,
    message         TEXT NOT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_messages_conversation (conversation_id),
    CONSTRAINT fk_messages_conversation FOREIGN KEY (conversation_id) REFERENCES conversations(conversation_id) ON DELETE CASCADE,
    CONSTRAINT fk_messages_sender FOREIGN KEY (sender_user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --- Activity logs ---
CREATE TABLE IF NOT EXISTS log_activities (
    log_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NULL,
    action     VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_log_user (user_id),
    INDEX idx_log_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --- Favorites (wishlist) ---
CREATE TABLE IF NOT EXISTS favorites (
    favorite_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    listing_id  INT NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_favorite (user_id, listing_id),
    INDEX idx_fav_user (user_id),
    INDEX idx_fav_listing (listing_id),
    CONSTRAINT fk_fav_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_fav_listing FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --- Orders (Buy Now purchases + auction winners) ---
CREATE TABLE IF NOT EXISTS orders (
    order_id   INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL,
    buyer_id   INT NOT NULL,
    seller_id  INT NOT NULL,
    price      DECIMAL(12,2) NOT NULL,
    status     ENUM('pending','completed','cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_orders_buyer (buyer_id),
    INDEX idx_orders_seller (seller_id),
    INDEX idx_orders_listing (listing_id),
    CONSTRAINT fk_orders_listing FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE CASCADE,
    CONSTRAINT fk_orders_buyer FOREIGN KEY (buyer_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_orders_seller FOREIGN KEY (seller_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --- Reviews and ratings (buyers rate sellers after purchase) ---
CREATE TABLE IF NOT EXISTS reviews (
    review_id   INT AUTO_INCREMENT PRIMARY KEY,
    listing_id  INT NOT NULL,
    reviewer_id INT NOT NULL,
    seller_id   INT NOT NULL,
    rating      TINYINT NOT NULL,
    comment     VARCHAR(1000) NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_review (listing_id, reviewer_id),
    INDEX idx_reviews_seller (seller_id),
    CONSTRAINT fk_reviews_listing FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_seller FOREIGN KEY (seller_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --- In-app notifications (bell + badge) ---
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    type            VARCHAR(50) NOT NULL,
    title           VARCHAR(255) NOT NULL,
    body            VARCHAR(1000) NULL,
    link            VARCHAR(255) NULL,
    is_read         TINYINT(1) NOT NULL DEFAULT 0,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notif_user (user_id, is_read),
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
-- Seed initial categories (inserted only if they do not exist)
-- ============================================================
INSERT IGNORE INTO categories (name, description) VALUES
    ('Elektronika',        'Telefoni, računari, TV i ostala elektronika.'),
    ('Vozila',             'Automobili, motori, delovi i oprema.'),
    ('Nekretnine',         'Stanovi, kuće, placevi i poslovni prostori.'),
    ('Moda',               'Odeća, obuća i modni dodaci.'),
    ('Dom i bašta',        'Nameštaj, alati i sve za dom.'),
    ('Sport i rekreacija', 'Oprema za sport, fitnes i aktivnosti.'),
    ('Knjige i muzika',    'Knjige, ploče, instrumenti i kolekcije.'),
    ('Ostalo',             'Sve što ne pripada drugim kategorijama.');