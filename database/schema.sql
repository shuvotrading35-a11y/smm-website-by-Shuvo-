-- =============================================================
-- Shuvo SMM Panel - Complete Database Schema
-- Version: 1.0.0 | Engine: InnoDB | Charset: utf8mb4
-- =============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';


-- =============================================================
-- USERS & AUTH
-- =============================================================

CREATE TABLE IF NOT EXISTS `smmPanel_users` (
  `id`              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `username`        VARCHAR(20)      NOT NULL,
  `email`           VARCHAR(180)     NOT NULL,
  `password`        VARCHAR(255)     NOT NULL COMMENT 'bcrypt cost 12',
  `full_name`       VARCHAR(100)     NOT NULL,
  `avatar`          VARCHAR(255)     DEFAULT NULL,
  `balance`         DECIMAL(18,4)    NOT NULL DEFAULT 0.0000,
  `total_spent`     DECIMAL(18,4)    NOT NULL DEFAULT 0.0000,
  `total_orders`    INT UNSIGNED     NOT NULL DEFAULT 0,
  `role`            ENUM('user','admin','superadmin') NOT NULL DEFAULT 'user',
  `status`          ENUM('active','banned','pending','deleted') NOT NULL DEFAULT 'pending',
  `email_verified`  TINYINT(1)       NOT NULL DEFAULT 0,
  `two_fa_enabled`  TINYINT(1)       NOT NULL DEFAULT 0,
  `two_fa_secret`   VARCHAR(64)      DEFAULT NULL,
  `api_key`         VARCHAR(64)      DEFAULT NULL UNIQUE COMMENT 'SHA-256 prefix stored, full key hashed',
  `api_key_hash`    VARCHAR(255)     DEFAULT NULL,
  `referral_code`   VARCHAR(20)      NOT NULL,
  `referred_by`     INT UNSIGNED     DEFAULT NULL,
  `ip_address`      VARCHAR(45)      DEFAULT NULL COMMENT 'Registration IP',
  `last_login_at`   DATETIME         DEFAULT NULL,
  `last_login_ip`   VARCHAR(45)      DEFAULT NULL,
  `login_attempts`  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `locked_until`    DATETIME         DEFAULT NULL,
  `language`        VARCHAR(10)      NOT NULL DEFAULT 'en',
  `created_at`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at`      DATETIME         DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`),
  UNIQUE KEY `uq_email` (`email`),
  UNIQUE KEY `uq_referral_code` (`referral_code`),
  KEY `idx_status` (`status`),
  KEY `idx_role` (`role`),
  KEY `idx_referred_by` (`referred_by`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `smmPanel_user_meta` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `meta_key`   VARCHAR(100) NOT NULL,
  `meta_value` TEXT         DEFAULT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_meta` (`user_id`, `meta_key`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `smmPanel_sessions` (
  `id`           VARCHAR(128)  NOT NULL,
  `user_id`      INT UNSIGNED  NOT NULL,
  `ip_address`   VARCHAR(45)   DEFAULT NULL,
  `user_agent`   VARCHAR(512)  DEFAULT NULL,
  `remember_me`  TINYINT(1)    NOT NULL DEFAULT 0,
  `expires_at`   DATETIME      NOT NULL,
  `created_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_used_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `smmPanel_password_resets` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `email`      VARCHAR(180) NOT NULL,
  `token_hash` VARCHAR(255) NOT NULL COMMENT 'SHA-256 of reset token',
  `expires_at` DATETIME     NOT NULL,
  `used_at`    DATETIME     DEFAULT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_token_hash` (`token_hash`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `smmPanel_email_otps` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `email`      VARCHAR(180) NOT NULL,
  `otp_hash`   VARCHAR(255) NOT NULL COMMENT 'bcrypt of 6-digit OTP',
  `purpose`    ENUM('email_verify','login_2fa','withdraw') NOT NULL DEFAULT 'email_verify',
  `attempts`   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `expires_at` DATETIME     NOT NULL,
  `used_at`    DATETIME     DEFAULT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_purpose` (`user_id`, `purpose`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================
-- SERVICES
-- =============================================================

CREATE TABLE IF NOT EXISTS `smmPanel_service_categories` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `slug`       VARCHAR(100) NOT NULL,
  `icon`       VARCHAR(100) DEFAULT NULL COMMENT 'Font Awesome class',
  `color`      VARCHAR(20)  DEFAULT NULL COMMENT 'Brand hex color',
  `sort_order` SMALLINT     NOT NULL DEFAULT 0,
  `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`),
  KEY `idx_sort` (`sort_order`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `smmPanel_services` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `api_service_id`  INT UNSIGNED    NOT NULL COMMENT 'ID from SMM provider',
  `category_id`     INT UNSIGNED    NOT NULL,
  `name`            VARCHAR(255)    NOT NULL,
  `custom_name`     VARCHAR(255)    DEFAULT NULL COMMENT 'Admin override',
  `description`     TEXT            DEFAULT NULL,
  `custom_desc`     TEXT            DEFAULT NULL,
  `type`            VARCHAR(50)     DEFAULT NULL COMMENT 'e.g. Default, Custom Comments',
  `rate`            DECIMAL(10,4)   NOT NULL COMMENT 'Provider rate per 1000',
  `markup_type`     ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
  `markup_value`    DECIMAL(10,4)   NOT NULL DEFAULT 0.0000,
  `min_quantity`    INT UNSIGNED    NOT NULL DEFAULT 10,
  `max_quantity`    INT UNSIGNED    NOT NULL DEFAULT 10000,
  `refill`          TINYINT(1)      NOT NULL DEFAULT 0,
  `cancel`          TINYINT(1)      NOT NULL DEFAULT 0,
  `is_active`       TINYINT(1)      NOT NULL DEFAULT 1,
  `sort_order`      SMALLINT        NOT NULL DEFAULT 0,
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_api_service_id` (`api_service_id`),
  KEY `idx_category` (`category_id`, `is_active`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `smmPanel_service_cache_log` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `synced_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `total_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `added`       INT UNSIGNED NOT NULL DEFAULT 0,
  `updated`     INT UNSIGNED NOT NULL DEFAULT 0,
  `errors`      TEXT         DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================
-- ORDERS
-- =============================================================

CREATE TABLE IF NOT EXISTS `smmPanel_orders` (
  `id`              BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`         INT UNSIGNED     NOT NULL,
  `service_id`      INT UNSIGNED     NOT NULL,
  `api_order_id`    VARCHAR(64)      DEFAULT NULL COMMENT 'Order ID from provider',
  `link`            TEXT             NOT NULL,
  `quantity`        INT UNSIGNED     NOT NULL,
  `charge`          DECIMAL(18,4)    NOT NULL,
  `start_count`     INT UNSIGNED     DEFAULT NULL,
  `remains`         INT UNSIGNED     DEFAULT NULL,
  `status`          ENUM('pending','processing','in_progress','completed','partial','cancelled','refunded','error') NOT NULL DEFAULT 'pending',
  `api_status`      VARCHAR(50)      DEFAULT NULL COMMENT 'Raw status from provider',
  `coupon_id`       INT UNSIGNED     DEFAULT NULL,
  `discount_amount` DECIMAL(18,4)    NOT NULL DEFAULT 0.0000,
  `refill_id`       VARCHAR(64)      DEFAULT NULL,
  `refill_status`   VARCHAR(50)      DEFAULT NULL,
  `cancel_note`     TEXT             DEFAULT NULL,
  `notes`           TEXT             DEFAULT NULL,
  `ip_address`      VARCHAR(45)      DEFAULT NULL,
  `created_at`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `completed_at`    DATETIME         DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_service_id` (`service_id`),
  KEY `idx_status` (`status`),
  KEY `idx_api_order_id` (`api_order_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `smmPanel_order_logs` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`   BIGINT UNSIGNED NOT NULL,
  `old_status` VARCHAR(50)     DEFAULT NULL,
  `new_status` VARCHAR(50)     NOT NULL,
  `message`    TEXT            DEFAULT NULL,
  `source`     ENUM('user','admin','cron','api') NOT NULL DEFAULT 'api',
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================
-- PAYMENTS & TRANSACTIONS
-- =============================================================

CREATE TABLE IF NOT EXISTS `smmPanel_deposits` (
  `id`             INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id`        INT UNSIGNED    NOT NULL,
  `amount`         DECIMAL(18,4)   NOT NULL,
  `method`         ENUM('bkash','nagad','rocket','usdt_trc20','usdt_erc20','binance','manual') NOT NULL,
  `transaction_id` VARCHAR(100)    DEFAULT NULL COMMENT 'User-provided TrxID',
  `proof_image`    VARCHAR(255)    DEFAULT NULL COMMENT 'Screenshot path',
  `note`           TEXT            DEFAULT NULL,
  `status`         ENUM('pending','approved','rejected','expired') NOT NULL DEFAULT 'pending',
  `admin_note`     TEXT            DEFAULT NULL,
  `reviewed_by`    INT UNSIGNED    DEFAULT NULL,
  `reviewed_at`    DATETIME        DEFAULT NULL,
  `approved_amount` DECIMAL(18,4)  DEFAULT NULL COMMENT 'Actual credited amount',
  `ip_address`     VARCHAR(45)     DEFAULT NULL,
  `created_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_method` (`method`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `smmPanel_transactions` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED    NOT NULL,
  `type`        ENUM('deposit','order','refund','referral','bonus','deduction','withdraw') NOT NULL,
  `amount`      DECIMAL(18,4)   NOT NULL COMMENT 'Positive = credit, Negative = debit',
  `balance_before` DECIMAL(18,4) NOT NULL,
  `balance_after`  DECIMAL(18,4) NOT NULL,
  `reference_id`   BIGINT UNSIGNED DEFAULT NULL COMMENT 'order_id or deposit_id',
  `reference_type` VARCHAR(50)     DEFAULT NULL,
  `description` VARCHAR(255)    DEFAULT NULL,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `smmPanel_balance_logs` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED    NOT NULL,
  `action`     VARCHAR(50)     NOT NULL,
  `amount`     DECIMAL(18,4)   NOT NULL,
  `admin_id`   INT UNSIGNED    DEFAULT NULL,
  `reason`     TEXT            DEFAULT NULL,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================
-- COUPONS
-- =============================================================

CREATE TABLE IF NOT EXISTS `smmPanel_coupons` (
  `id`           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `code`         VARCHAR(50)     NOT NULL,
  `type`         ENUM('percent','fixed','bonus_balance') NOT NULL DEFAULT 'percent',
  `value`        DECIMAL(10,4)   NOT NULL,
  `min_order`    DECIMAL(10,4)   NOT NULL DEFAULT 0.0000,
  `max_discount` DECIMAL(10,4)   DEFAULT NULL COMMENT 'Cap for percent type',
  `max_uses`     INT UNSIGNED    DEFAULT NULL COMMENT 'NULL = unlimited',
  `uses_per_user` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `total_used`   INT UNSIGNED    NOT NULL DEFAULT 0,
  `is_active`    TINYINT(1)      NOT NULL DEFAULT 1,
  `expires_at`   DATETIME        DEFAULT NULL,
  `created_by`   INT UNSIGNED    NOT NULL,
  `created_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_code` (`code`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `smmPanel_coupon_usage` (
  `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `coupon_id`  INT UNSIGNED    NOT NULL,
  `user_id`    INT UNSIGNED    NOT NULL,
  `order_id`   BIGINT UNSIGNED NOT NULL,
  `discount`   DECIMAL(10,4)   NOT NULL,
  `used_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_coupon_user` (`coupon_id`, `user_id`),
  KEY `idx_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================
-- REFERRALS
-- =============================================================

CREATE TABLE IF NOT EXISTS `smmPanel_referrals` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `referrer_id` INT UNSIGNED    NOT NULL,
  `referred_id` INT UNSIGNED    NOT NULL,
  `status`      ENUM('pending','active') NOT NULL DEFAULT 'pending',
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_referred` (`referred_id`),
  KEY `idx_referrer` (`referrer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `smmPanel_referral_earnings` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `referrer_id` INT UNSIGNED    NOT NULL,
  `referred_id` INT UNSIGNED    NOT NULL,
  `order_id`    BIGINT UNSIGNED NOT NULL,
  `order_amount` DECIMAL(18,4)  NOT NULL,
  `percent`     DECIMAL(5,2)    NOT NULL,
  `earned`      DECIMAL(18,4)   NOT NULL,
  `credited_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_referrer_id` (`referrer_id`),
  KEY `idx_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================
-- API KEYS & LOGS
-- =============================================================

CREATE TABLE IF NOT EXISTS `smmPanel_api_keys` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`       INT UNSIGNED NOT NULL,
  `key_prefix`    VARCHAR(10)  NOT NULL COMMENT 'First 8 chars shown to user',
  `key_hash`      VARCHAR(255) NOT NULL COMMENT 'SHA-256 full key',
  `requests_today` INT UNSIGNED NOT NULL DEFAULT 0,
  `requests_month` INT UNSIGNED NOT NULL DEFAULT 0,
  `requests_total` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `last_used_at`  DATETIME     DEFAULT NULL,
  `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_id` (`user_id`),
  KEY `idx_key_hash` (`key_hash`(20))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `smmPanel_api_logs` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED    DEFAULT NULL COMMENT 'NULL for provider calls',
  `action`      VARCHAR(50)     NOT NULL,
  `endpoint`    VARCHAR(255)    DEFAULT NULL,
  `request`     JSON            DEFAULT NULL,
  `response`    JSON            DEFAULT NULL,
  `status_code` SMALLINT        DEFAULT NULL,
  `duration_ms` INT UNSIGNED    DEFAULT NULL,
  `ip_address`  VARCHAR(45)     DEFAULT NULL,
  `error`       TEXT            DEFAULT NULL,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================
-- NOTIFICATIONS & BROADCAST
-- =============================================================

CREATE TABLE IF NOT EXISTS `smmPanel_notifications` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED    NOT NULL,
  `type`       VARCHAR(50)     NOT NULL COMMENT 'order_update, deposit, broadcast, etc.',
  `title`      VARCHAR(255)    NOT NULL,
  `body`       TEXT            DEFAULT NULL,
  `data`       JSON            DEFAULT NULL,
  `icon`       VARCHAR(100)    DEFAULT NULL,
  `url`        VARCHAR(255)    DEFAULT NULL,
  `is_read`    TINYINT(1)      NOT NULL DEFAULT 0,
  `read_at`    DATETIME        DEFAULT NULL,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_unread` (`user_id`, `is_read`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `smmPanel_broadcast_queue` (
  `id`           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `admin_id`     INT UNSIGNED    NOT NULL,
  `title`        VARCHAR(255)    NOT NULL,
  `body`         TEXT            NOT NULL,
  `target_type`  ENUM('all','segment','specific') NOT NULL DEFAULT 'all',
  `target_data`  JSON            DEFAULT NULL COMMENT 'Filter params or user IDs',
  `channels`     SET('email','inapp','sms') NOT NULL DEFAULT 'inapp',
  `status`       ENUM('draft','scheduled','sending','sent','failed') NOT NULL DEFAULT 'draft',
  `scheduled_at` DATETIME        DEFAULT NULL,
  `sent_at`      DATETIME        DEFAULT NULL,
  `total_sent`   INT UNSIGNED    NOT NULL DEFAULT 0,
  `total_failed` INT UNSIGNED    NOT NULL DEFAULT 0,
  `created_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================
-- SUPPORT TICKETS
-- =============================================================

CREATE TABLE IF NOT EXISTS `smmPanel_support_tickets` (
  `id`           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id`      INT UNSIGNED    NOT NULL,
  `subject`      VARCHAR(255)    NOT NULL,
  `category`     ENUM('order','payment','technical','general','other') NOT NULL DEFAULT 'general',
  `priority`     ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `status`       ENUM('open','in_progress','waiting','resolved','closed') NOT NULL DEFAULT 'open',
  `assigned_to`  INT UNSIGNED    DEFAULT NULL COMMENT 'Admin user ID',
  `order_id`     BIGINT UNSIGNED DEFAULT NULL,
  `last_reply_at` DATETIME        DEFAULT NULL,
  `closed_at`    DATETIME        DEFAULT NULL,
  `created_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_assigned_to` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `smmPanel_ticket_replies` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `ticket_id`   INT UNSIGNED    NOT NULL,
  `user_id`     INT UNSIGNED    NOT NULL,
  `body`        TEXT            NOT NULL,
  `attachments` JSON            DEFAULT NULL COMMENT 'Array of file paths',
  `is_admin`    TINYINT(1)      NOT NULL DEFAULT 0,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ticket_id` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================
-- BLOG
-- =============================================================

CREATE TABLE IF NOT EXISTS `smmPanel_blog_categories` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `slug`       VARCHAR(100) NOT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `smmPanel_blog_posts` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `author_id`     INT UNSIGNED NOT NULL,
  `category_id`   INT UNSIGNED DEFAULT NULL,
  `title`         VARCHAR(255) NOT NULL,
  `slug`          VARCHAR(255) NOT NULL,
  `excerpt`       TEXT         DEFAULT NULL,
  `body`          LONGTEXT     NOT NULL,
  `featured_img`  VARCHAR(255) DEFAULT NULL,
  `tags`          JSON         DEFAULT NULL,
  `seo_title`     VARCHAR(255) DEFAULT NULL,
  `seo_desc`      TEXT         DEFAULT NULL,
  `status`        ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  `views`         INT UNSIGNED NOT NULL DEFAULT 0,
  `published_at`  DATETIME     DEFAULT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`),
  KEY `idx_status` (`status`),
  KEY `idx_published_at` (`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================
-- FAVORITES
-- =============================================================

CREATE TABLE IF NOT EXISTS `smmPanel_favorites` (
  `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED    NOT NULL,
  `service_id` INT UNSIGNED    NOT NULL,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_service` (`user_id`, `service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =============================================================
-- SETTINGS & LOGS
-- =============================================================

CREATE TABLE IF NOT EXISTS `smmPanel_settings` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key`        VARCHAR(100) NOT NULL,
  `value`      TEXT         DEFAULT NULL,
  `type`       ENUM('string','integer','boolean','json','text') NOT NULL DEFAULT 'string',
  `group`      VARCHAR(50)  NOT NULL DEFAULT 'general',
  `label`      VARCHAR(150) DEFAULT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_key` (`key`),
  KEY `idx_group` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `smmPanel_admin_logs` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id`   INT UNSIGNED    NOT NULL,
  `action`     VARCHAR(100)    NOT NULL,
  `target_type` VARCHAR(50)    DEFAULT NULL,
  `target_id`  BIGINT UNSIGNED DEFAULT NULL,
  `old_data`   JSON            DEFAULT NULL,
  `new_data`   JSON            DEFAULT NULL,
  `ip_address` VARCHAR(45)     DEFAULT NULL,
  `user_agent` VARCHAR(512)    DEFAULT NULL,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `smmPanel_system_logs` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `level`      ENUM('debug','info','warning','error','critical') NOT NULL DEFAULT 'info',
  `channel`    VARCHAR(50)     NOT NULL DEFAULT 'app',
  `message`    TEXT            NOT NULL,
  `context`    JSON            DEFAULT NULL,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_level` (`level`),
  KEY `idx_channel` (`channel`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Rate limiting table
CREATE TABLE IF NOT EXISTS `smmPanel_rate_limits` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key`        VARCHAR(128)    NOT NULL COMMENT 'ip:action or user_id:action',
  `hits`       SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `window_end` DATETIME        NOT NULL,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_key` (`key`),
  KEY `idx_window_end` (`window_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


SET FOREIGN_KEY_CHECKS = 1;
