# 🚀 Shuvo SMM Panel

A production-ready, full-stack Social Media Marketing panel built with PHP 8.2+ MVC, MySQL 8.0+, and a premium glassmorphism UI.

---

## ✨ Features

- **Full SMM API Integration** — smmfm.com v2 API (services, orders, status, refill, cancel)
- **Premium UI** — Glassmorphism, aurora gradients, dark/light theme, particle background
- **Multi-Payment** — bKash, Nagad, Rocket, USDT TRC20/ERC20, Binance Pay
- **User Dashboard** — Orders, balance, transactions, favorites, referrals, coupons, API access
- **Admin Panel** — Users, orders, deposits, services, coupons, broadcast, support tickets, blog, logs
- **REST API v2** — Full public API with key-based auth for automation
- **Security** — bcrypt(12), CSRF, rate limiting, CSP headers, SQL injection protection
- **Cron Jobs** — Auto order sync (5 min), service sync (6 hr), notification processing (15 min)
- **Referral System** — Configurable % commission on referred user orders
- **Support Tickets** — Priority queue with admin assignment and notifications

---

## 📋 Requirements

| Requirement | Minimum Version |
|---|---|
| PHP | 8.2+ |
| MySQL | 8.0+ |
| Composer | 2.0+ |
| Apache/Nginx | Latest stable |
| SSL Certificate | Required (Let's Encrypt) |

**Required PHP Extensions:** `pdo`, `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `curl`, `json`, `gd`

---

## 🛠️ Installation

### 1. Upload Files

```bash
# Clone or upload to your web root
git clone https://github.com/youruser/shuvo-smm-panel.git /var/www/shuvosmm
cd /var/www/shuvosmm
```

### 2. Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
```

### 3. Configure Environment

```bash
cp .env.example .env
nano .env
```

Fill in **all** required values:

```env
APP_URL=https://yourdomain.com
APP_SECRET=generate_64_char_random_string_here

DB_HOST=127.0.0.1
DB_NAME=shuvo_smm_panel
DB_USER=smm_user
DB_PASS=your_strong_password

SMM_API_URL=https://smmfm.com/api/v2
SMM_API_KEY=your_provider_api_key

SMTP_HOST=smtp.gmail.com
SMTP_USER=your@gmail.com
SMTP_PASS=your_app_password
```

### 4. Create Database

```bash
mysql -u root -p -e "CREATE DATABASE shuvo_smm_panel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE USER 'smm_user'@'localhost' IDENTIFIED BY 'your_strong_password';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON shuvo_smm_panel.* TO 'smm_user'@'localhost';"
mysql -u root -p -e "FLUSH PRIVILEGES;"
```

### 5. Run Database Schema

```bash
mysql -u smm_user -p shuvo_smm_panel < database/schema.sql
mysql -u smm_user -p shuvo_smm_panel < database/seed.sql
```

### 6. Set Permissions

```bash
chmod 755 public/assets/uploads
chmod 755 public/assets/uploads/avatars
chmod 755 public/assets/uploads/proofs
chown -R www-data:www-data /var/www/shuvosmm
```

### 7. Configure Web Server

**Apache** — Point document root to `/var/www/shuvosmm/public`. The `.htaccess` handles routing.

```apache
<VirtualHost *:443>
    ServerName yourdomain.com
    DocumentRoot /var/www/shuvosmm/public

    <Directory /var/www/shuvosmm/public>
        AllowOverride All
        Require all granted
    </Directory>

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/yourdomain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/yourdomain.com/privkey.pem
</VirtualHost>
```

**Nginx:**

```nginx
server {
    listen 443 ssl;
    server_name yourdomain.com;
    root /var/www/shuvosmm/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Block access to sensitive files
    location ~ /\.(env|git) { deny all; }
    location ~ /vendor      { deny all; }
    location ~ /src         { deny all; }
    location ~ /database    { deny all; }
    location ~ /cron        { deny all; }
}
```

### 8. Configure Cron Jobs

```bash
crontab -e
```

Add these lines:

```cron
# Sync order statuses every 5 minutes
*/5 * * * * php /var/www/shuvosmm/cron/sync-orders.php >> /var/log/smm-orders.log 2>&1

# Sync services from provider every 6 hours
0 */6 * * * php /var/www/shuvosmm/cron/sync-services.php >> /var/log/smm-services.log 2>&1

# Process notifications & clean up every 15 minutes
*/15 * * * * php /var/www/shuvosmm/cron/process-notifications.php >> /var/log/smm-notify.log 2>&1
```

### 9. First Login

Navigate to `https://yourdomain.com/admin/login`

```
Username: admin
Password: Admin@12345
```

**⚠️ Change this password immediately after first login!**

---

## 🔐 Post-Installation Security Checklist

- [ ] Change the default admin password
- [ ] Set a strong `APP_SECRET` (64+ random chars)
- [ ] Enable HTTPS redirect in `.htaccess`
- [ ] Configure `ADMIN_IP_WHITELIST` if needed
- [ ] Set up hCaptcha keys for brute-force protection
- [ ] Test SMTP email delivery
- [ ] Sync services from provider: Admin → Services → Sync
- [ ] Configure payment method numbers: Admin → Settings → Payments
- [ ] Set referral commission percentage: Admin → Settings → Referral
- [ ] Set minimum deposit amount: Admin → Settings → Payments

---

## ⚙️ Admin Panel Configuration

### Payment Methods

Go to **Admin → Settings → Payments** and configure:

| Field | Description |
|---|---|
| bKash Number | Your bKash personal/merchant number |
| Nagad Number | Your Nagad account number |
| Rocket Number | Your Rocket (DBBL) number |
| USDT TRC20 Address | Your Tron wallet address |
| USDT ERC20 Address | Your Ethereum wallet address |
| Binance Pay ID | Your Binance Pay merchant ID |
| Minimum Deposit | Minimum deposit in USD |

### SMM Provider

Go to **Admin → Settings → API** and set:

- **SMM API URL** — `https://smmfm.com/api/v2` (or your provider)
- **SMM API Key** — Your provider API key

Then go to **Admin → Services → Sync Services** to import all services.

### Email (SMTP)

Go to **Admin → Settings → Mail**:

- Use Gmail App Password, Mailgun, or SendGrid
- Test by registering a new account — OTP email should arrive

---

## 📁 Project Structure

```
shuvo-smm-panel/
├── public/                  ← Web root (point domain here)
│   ├── index.php            ← Entry point
│   ├── .htaccess            ← Apache routing + security
│   └── assets/
│       ├── css/             ← app.css, dashboard.css, admin.css
│       ├── js/              ← app.js, dashboard.js, admin.js
│       ├── img/             ← logos, payment icons
│       └── uploads/         ← user avatars, deposit proofs
│
├── src/
│   ├── Core/                ← App, Router, Database, Config
│   ├── Controllers/         ← Auth, Dashboard, Order, Funds, Admin, API, Support, Public
│   ├── Models/              ← (extend as needed)
│   ├── Services/            ← SmmApi, Email, Notification
│   ├── Middleware/          ← Auth, Admin, CSRF, RateLimit
│   └── Views/
│       ├── layouts/         ← main.php, dashboard.php, admin.php
│       ├── auth/            ← login, register, otp, forgot, reset
│       ├── dashboard/       ← index, new-order, orders, add-funds, etc.
│       ├── admin/           ← dashboard, users, orders, deposits, etc.
│       ├── public/          ← home, services, api-docs, blog
│       └── errors/          ← 404.php, 500.php
│
├── database/
│   ├── schema.sql           ← Full 30-table schema
│   └── seed.sql             ← Default settings, categories, admin user
│
├── cron/
│   ├── sync-orders.php      ← Every 5 min
│   ├── sync-services.php    ← Every 6 hours
│   └── process-notifications.php ← Every 15 min
│
├── .env.example             ← Environment template
├── composer.json            ← PHP dependencies
└── README.md                ← This file
```

---

## 🔌 API Usage

**Endpoint:** `POST https://yourdomain.com/api/v2`

### Get Your API Key

Dashboard → API Access → copy your key.

### Example: Place Order

```php
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL        => 'https://yourdomain.com/api/v2',
    CURLOPT_POST       => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'key'      => 'YOUR_API_KEY',
        'action'   => 'add',
        'service'  => 1,
        'link'     => 'https://instagram.com/yourpage',
        'quantity' => 1000,
    ]),
    CURLOPT_RETURNTRANSFER => true,
]);
$result = json_decode(curl_exec($ch), true);
echo $result['order']; // Order ID
```

### Available Actions

| Action | Description |
|---|---|
| `services` | List all services |
| `add` | Place new order |
| `status` | Check order status |
| `balance` | Get account balance |
| `refill` | Request order refill |
| `cancel` | Cancel orders |

---

## 🐛 Troubleshooting

### White screen / 500 error
- Enable `APP_DEBUG=true` in `.env` temporarily
- Check PHP error log: `tail -f /var/log/php-errors.log`
- Verify all `.env` values are set correctly

### Emails not sending
- Check SMTP credentials in `.env`
- For Gmail, use App Password (not account password)
- Check spam folder

### Orders not syncing
- Verify cron jobs are running: `crontab -l`
- Check cron logs: `tail -f /var/log/smm-orders.log`
- Verify SMM API key is valid

### Services not loading
- Run sync: Admin → Services → Sync Services
- Check provider API key: Admin → Settings → API
- Verify provider URL is accessible from your server

### Database connection error
- Verify `DB_*` values in `.env`
- Check MySQL is running: `systemctl status mysql`
- Verify user permissions: `SHOW GRANTS FOR 'smm_user'@'localhost';`

---

## 📞 Support

- **Telegram:** [@shuvo_9882](https://t.me/shuvo_9882)
- **Admin Panel:** `/admin/support`

---

## 📜 License

Proprietary — All rights reserved. Developed by Shuvo Ahmed.

---

*Built with ❤️ by [@shuvo_9882](https://t.me/shuvo_9882)*
