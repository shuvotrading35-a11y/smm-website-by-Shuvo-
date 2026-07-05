-- =============================================================
-- Shuvo SMM Panel - Seed Data
-- Run AFTER schema.sql
-- =============================================================

USE shuvo_smm_panel;

-- =============================================================
-- DEFAULT SETTINGS
-- =============================================================

INSERT INTO `smmPanel_settings` (`key`, `value`, `type`, `group`, `label`) VALUES
-- General
('site_name',         'Shuvo SMM Panel',     'string',  'general', 'Site Name'),
('site_tagline',      'The #1 Trusted SMM Panel', 'string', 'general', 'Tagline'),
('site_url',          'https://shuvosmm.com','string',  'general', 'Site URL'),
('site_email',        'support@shuvosmm.com','string',  'general', 'Support Email'),
('site_logo',         '',                    'string',  'general', 'Logo URL'),
('site_favicon',      '',                    'string',  'general', 'Favicon URL'),
('currency_symbol',   '$',                   'string',  'general', 'Currency Symbol'),
('currency_code',     'USD',                 'string',  'general', 'Currency Code'),
('maintenance_mode',  '0',                   'boolean', 'general', 'Maintenance Mode'),
('maintenance_msg',   'We are upgrading our systems. Please check back soon.', 'text', 'general', 'Maintenance Message'),

-- Registration
('registration',      'open',                'string',  'auth',    'Registration Mode (open/closed/invite)'),
('email_verify',      '1',                   'boolean', 'auth',    'Require Email Verification'),
('default_balance',   '0',                   'integer', 'auth',    'Default Balance on Signup (cents)'),
('captcha_enabled',   '1',                   'boolean', 'auth',    'Enable hCaptcha'),
('hcaptcha_site_key', '',                    'string',  'auth',    'hCaptcha Site Key'),
('hcaptcha_secret',   '',                    'string',  'auth',    'hCaptcha Secret'),

-- SMM API
('smm_api_url',       'https://smmfm.com/api/v2', 'string', 'api', 'SMM Provider API URL'),
('smm_api_key',       '',                    'string',  'api',     'SMM Provider API Key'),
('smm_api_balance',   '0.00',               'string',  'api',     'Cached Provider Balance'),
('smm_api_balance_at','',                    'string',  'api',     'Balance Last Fetched'),
('services_cache_ttl','21600',               'integer', 'api',     'Services Cache TTL (seconds)'),

-- Referral
('referral_enabled',  '1',                   'boolean', 'referral','Referral System'),
('referral_percent',  '2.00',               'string',  'referral','Referral Commission % per Order'),

-- Payments
('bkash_enabled',     '1',                   'boolean', 'payment', 'bKash Enabled'),
('bkash_number',      '',                    'string',  'payment', 'bKash Account Number'),
('bkash_type',        'Personal',            'string',  'payment', 'bKash Account Type'),
('nagad_enabled',     '1',                   'boolean', 'payment', 'Nagad Enabled'),
('nagad_number',      '',                    'string',  'payment', 'Nagad Account Number'),
('rocket_enabled',    '1',                   'boolean', 'payment', 'Rocket Enabled'),
('rocket_number',     '',                    'string',  'payment', 'Rocket Account Number'),
('usdt_trc20_enabled','1',                   'boolean', 'payment', 'USDT TRC20 Enabled'),
('usdt_trc20_address','',                    'string',  'payment', 'USDT TRC20 Wallet Address'),
('usdt_erc20_enabled','1',                   'boolean', 'payment', 'USDT ERC20 Enabled'),
('usdt_erc20_address','',                    'string',  'payment', 'USDT ERC20 Wallet Address'),
('binance_enabled',   '1',                   'boolean', 'payment', 'Binance Pay Enabled'),
('binance_id',        '',                    'string',  'payment', 'Binance Pay ID'),
('min_deposit',       '100',                 'integer', 'payment', 'Minimum Deposit (cents)'),

-- SMTP
('smtp_host',         '',                    'string',  'mail', 'SMTP Host'),
('smtp_port',         '587',                 'integer', 'mail', 'SMTP Port'),
('smtp_user',         '',                    'string',  'mail', 'SMTP Username'),
('smtp_pass',         '',                    'string',  'mail', 'SMTP Password'),
('smtp_encryption',   'tls',                 'string',  'mail', 'SMTP Encryption (tls/ssl)'),
('mail_from_name',    'Shuvo SMM Panel',     'string',  'mail', 'Mail From Name'),
('mail_from_email',   'noreply@shuvosmm.com','string',  'mail', 'Mail From Email'),

-- Tawk.to
('tawkto_enabled',    '0',                   'boolean', 'live_chat', 'Tawk.to Enabled'),
('tawkto_widget_id',  '',                    'string',  'live_chat', 'Tawk.to Widget ID'),

-- API Rate limits
('api_rate_limit',    '60',                  'integer', 'api', 'API Requests per Minute'),
('user_rate_login',   '5',                   'integer', 'security', 'Login Attempts before CAPTCHA'),
('user_rate_register','3',                   'integer', 'security', 'Register Attempts per Minute'),

-- Stats (updated by cron)
('stat_total_orders', '2000000',             'integer', 'stats', 'Total Orders Displayed'),
('stat_total_users',  '100000',              'integer', 'stats', 'Total Users Displayed'),
('stat_total_services','50000',              'integer', 'stats', 'Total Services Displayed'),
('stat_uptime',       '99.9',               'string',  'stats', 'Uptime Percentage')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);


-- =============================================================
-- SERVICE CATEGORIES
-- =============================================================

INSERT INTO `smmPanel_service_categories` (`name`, `slug`, `icon`, `color`, `sort_order`) VALUES
('Instagram',  'instagram',  'fab fa-instagram',  '#E1306C', 1),
('Facebook',   'facebook',   'fab fa-facebook',   '#1877F2', 2),
('TikTok',     'tiktok',     'fab fa-tiktok',     '#010101', 3),
('YouTube',    'youtube',    'fab fa-youtube',    '#FF0000', 4),
('Twitter/X',  'twitter',    'fab fa-twitter',    '#1DA1F2', 5),
('Telegram',   'telegram',   'fab fa-telegram',   '#0088CC', 6),
('Spotify',    'spotify',    'fab fa-spotify',    '#1DB954', 7),
('LinkedIn',   'linkedin',   'fab fa-linkedin',   '#0A66C2', 8),
('Discord',    'discord',    'fab fa-discord',    '#5865F2', 9),
('Snapchat',   'snapchat',   'fab fa-snapchat',   '#FFFC00', 10),
('Pinterest',  'pinterest',  'fab fa-pinterest',  '#E60023', 11),
('Twitch',     'twitch',     'fab fa-twitch',     '#9146FF', 12),
('SoundCloud', 'soundcloud', 'fab fa-soundcloud', '#FF5500', 13),
('Reddit',     'reddit',     'fab fa-reddit',     '#FF4500', 14),
('Other',      'other',      'fas fa-globe',      '#7C5CFF', 15)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);


-- =============================================================
-- DEFAULT ADMIN USER
-- Password: Admin@12345 (bcrypt cost 12)
-- CHANGE THIS IMMEDIATELY AFTER INSTALLATION
-- =============================================================

INSERT IGNORE INTO `smmPanel_users`
  (`username`, `email`, `password`, `full_name`, `role`, `status`, `email_verified`, `referral_code`)
VALUES
  (
    'admin',
    'admin@shuvosmm.com',
    '$2y$12$K9ZxcUGDpEo.uqSKGIL6/.3kBhM7MUXcJbMfJbXpYCLIgBZJHo6Gy',
    'Super Admin',
    'superadmin',
    'active',
    1,
    'ADMIN0001'
  );


-- =============================================================
-- DEFAULT BLOG CATEGORIES
-- =============================================================

INSERT IGNORE INTO `smmPanel_blog_categories` (`name`, `slug`) VALUES
('Social Media Tips', 'social-media-tips'),
('Platform Updates',  'platform-updates'),
('Marketing Guides',  'marketing-guides'),
('Panel News',        'panel-news'),
('Tutorials',         'tutorials');


-- =============================================================
-- SAMPLE COUPONS
-- =============================================================

INSERT IGNORE INTO `smmPanel_coupons`
  (`code`, `type`, `value`, `min_order`, `max_uses`, `uses_per_user`, `is_active`, `created_by`)
VALUES
  ('WELCOME10', 'percent', 10.00, 1.00, 1000, 1, 1, 1),
  ('FLAT5',     'fixed',    5.00, 20.00, 500, 1, 1, 1);
