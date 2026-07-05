<?php

declare(strict_types=1);

namespace SMMPanel\Controllers;

use SMMPanel\Core\Database;
use SMMPanel\Core\Config;
use SMMPanel\Services\EmailService;
use SMMPanel\Services\NotificationService;

/**
 * AuthController — handles all user authentication flows.
 */
final class AuthController extends BaseController
{
    private Database $db;
    private EmailService $mailer;

    public function __construct()
    {
        $this->db     = Database::getInstance();
        $this->mailer = new EmailService();
    }

    // ── Registration ──────────────────────────────────────────

    public function register(array $params): void
    {
        if ($this->isLoggedIn()) {
            $this->redirect('/dashboard');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->view('auth/register', ['title' => 'Create Account']);
            return;
        }

        // POST handler
        $errors = $this->validateRegistration($_POST);

        if (!empty($errors)) {
            $this->json(['success' => false, 'errors' => $errors]);
            return;
        }

        // hCaptcha
        if (Config::get('CAPTCHA_ENABLED')) {
            if (!$this->verifyCaptcha($_POST['h-captcha-response'] ?? '')) {
                $this->json(['success' => false, 'message' => 'Captcha verification failed.']);
                return;
            }
        }

        // Check uniqueness
        $emailExists = $this->db->fetchColumn(
            'SELECT COUNT(*) FROM smmPanel_users WHERE email = ?',
            [strtolower($_POST['email'])]
        );

        if ($emailExists > 0) {
            $this->json(['success' => false, 'message' => 'Email already registered.']);
            return;
        }

        $usernameExists = $this->db->fetchColumn(
            'SELECT COUNT(*) FROM smmPanel_users WHERE username = ?',
            [strtolower($_POST['username'])]
        );

        if ($usernameExists > 0) {
            $this->json(['success' => false, 'message' => 'Username already taken.']);
            return;
        }

        $this->db->transaction(function (Database $db) {
            $referralCode  = $this->generateReferralCode();
            $defaultBalance = (int) $this->getSetting('default_balance', 0);
            $referredById  = null;

            // Handle referral
            if (!empty($_POST['referral_code'])) {
                $referrer = $db->fetchOne(
                    'SELECT id FROM smmPanel_users WHERE referral_code = ? AND status = "active"',
                    [strtoupper($_POST['referral_code'])]
                );
                $referredById = $referrer ? (int) $referrer['id'] : null;
            }

            $userId = (int) $db->insert('smmPanel_users', [
                'username'       => strtolower(trim($_POST['username'])),
                'email'          => strtolower(trim($_POST['email'])),
                'password'       => password_hash($_POST['password'], PASSWORD_BCRYPT, ['cost' => 12]),
                'full_name'      => trim($_POST['full_name']),
                'balance'        => $defaultBalance,
                'status'         => 'pending',
                'email_verified' => 0,
                'referral_code'  => $referralCode,
                'referred_by'    => $referredById,
                'ip_address'     => $this->getClientIp(),
            ]);

            // Record referral
            if ($referredById) {
                $db->insert('smmPanel_referrals', [
                    'referrer_id' => $referredById,
                    'referred_id' => $userId,
                    'status'      => 'pending',
                ]);
            }

            // Generate & send OTP
            $otp     = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            $otpHash = password_hash($otp, PASSWORD_BCRYPT, ['cost' => 10]);

            // Expire any existing OTPs
            $db->query(
                'UPDATE smmPanel_email_otps SET used_at = NOW() WHERE user_id = ? AND purpose = "email_verify" AND used_at IS NULL',
                [$userId]
            );

            $db->insert('smmPanel_email_otps', [
                'user_id'  => $userId,
                'email'    => strtolower(trim($_POST['email'])),
                'otp_hash' => $otpHash,
                'purpose'  => 'email_verify',
                'expires_at' => date('Y-m-d H:i:s', strtotime('+15 minutes')),
            ]);

            // Store pending user_id in session for OTP step
            $_SESSION['pending_otp_user'] = $userId;
            $_SESSION['pending_otp_email'] = strtolower(trim($_POST['email']));

            $this->mailer->sendOtp(trim($_POST['email']), trim($_POST['full_name']), $otp);
        });

        $this->json(['success' => true, 'redirect' => '/register/verify']);
    }

    public function verifyOtp(array $params): void
    {
        if (!isset($_SESSION['pending_otp_user'])) {
            $this->redirect('/register');
        }

        $this->view('auth/verify-otp', [
            'title' => 'Verify Email',
            'email' => $_SESSION['pending_otp_email'] ?? '',
        ]);
    }

    public function processOtp(array $params): void
    {
        $userId = $_SESSION['pending_otp_user'] ?? null;

        if (!$userId) {
            $this->json(['success' => false, 'message' => 'Session expired. Please register again.']);
            return;
        }

        $otp = trim($_POST['otp'] ?? '');

        if (!preg_match('/^\d{6}$/', $otp)) {
            $this->json(['success' => false, 'message' => 'Invalid OTP format.']);
            return;
        }

        $record = $this->db->fetchOne(
            'SELECT * FROM smmPanel_email_otps
             WHERE user_id = ? AND purpose = "email_verify" AND used_at IS NULL
             ORDER BY id DESC LIMIT 1',
            [$userId]
        );

        if (!$record || strtotime($record['expires_at']) < time()) {
            $this->json(['success' => false, 'message' => 'OTP expired. Please request a new one.']);
            return;
        }

        if ((int) $record['attempts'] >= 5) {
            $this->json(['success' => false, 'message' => 'Too many attempts. Please request a new OTP.']);
            return;
        }

        // Increment attempts
        $this->db->query(
            'UPDATE smmPanel_email_otps SET attempts = attempts + 1 WHERE id = ?',
            [$record['id']]
        );

        if (!password_verify($otp, $record['otp_hash'])) {
            $remaining = 4 - (int) $record['attempts'];
            $this->json(['success' => false, 'message' => "Incorrect OTP. {$remaining} attempt(s) left."]);
            return;
        }

        // Mark OTP used and activate user
        $this->db->query(
            'UPDATE smmPanel_email_otps SET used_at = NOW() WHERE id = ?',
            [$record['id']]
        );

        $this->db->query(
            'UPDATE smmPanel_users SET status = "active", email_verified = 1 WHERE id = ?',
            [$userId]
        );

        // Activate referral
        $this->db->query(
            'UPDATE smmPanel_referrals SET status = "active" WHERE referred_id = ?',
            [$userId]
        );

        unset($_SESSION['pending_otp_user'], $_SESSION['pending_otp_email']);

        // Auto-login
        $this->createUserSession($userId, false);

        $this->json(['success' => true, 'redirect' => '/dashboard']);
    }

    // ── Login ─────────────────────────────────────────────────

    public function login(array $params): void
    {
        if ($this->isLoggedIn()) {
            $this->redirect('/dashboard');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->view('auth/login', ['title' => 'Sign In']);
            return;
        }

        $identifier = strtolower(trim($_POST['identifier'] ?? ''));
        $password   = $_POST['password'] ?? '';
        $remember   = !empty($_POST['remember']);

        if (empty($identifier) || empty($password)) {
            $this->json(['success' => false, 'message' => 'Please fill in all fields.']);
            return;
        }

        $user = $this->db->fetchOne(
            'SELECT * FROM smmPanel_users
             WHERE (email = ? OR username = ?) AND deleted_at IS NULL
             LIMIT 1',
            [$identifier, $identifier]
        );

        if (!$user) {
            $this->json(['success' => false, 'message' => 'Invalid credentials.']);
            return;
        }

        // Check account lock
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            $remaining = ceil((strtotime($user['locked_until']) - time()) / 60);
            $this->json(['success' => false, 'message' => "Account locked. Try again in {$remaining} minute(s)."]);
            return;
        }

        // Require CAPTCHA after 5 failed attempts
        $needsCaptcha = (int) $user['login_attempts'] >= 5;
        if ($needsCaptcha) {
            if (!$this->verifyCaptcha($_POST['h-captcha-response'] ?? '')) {
                $this->json(['success' => false, 'message' => 'Please complete the captcha.', 'captcha' => true]);
                return;
            }
        }

        if (!password_verify($password, $user['password'])) {
            $attempts = (int) $user['login_attempts'] + 1;
            $lockUntil = $attempts >= 10 ? date('Y-m-d H:i:s', strtotime('+30 minutes')) : null;

            $this->db->query(
                'UPDATE smmPanel_users SET login_attempts = ?, locked_until = ? WHERE id = ?',
                [$attempts, $lockUntil, $user['id']]
            );

            $remaining = max(0, 5 - $attempts);
            $this->json([
                'success'  => false,
                'message'  => 'Invalid credentials.',
                'captcha'  => $attempts >= 5,
                'attempts' => $attempts,
            ]);
            return;
        }

        if ($user['status'] === 'banned') {
            $this->json(['success' => false, 'message' => 'Your account has been suspended. Contact support.']);
            return;
        }

        if ($user['status'] === 'pending' || !(bool) $user['email_verified']) {
            $_SESSION['pending_otp_user']  = $user['id'];
            $_SESSION['pending_otp_email'] = $user['email'];
            $this->json(['success' => false, 'redirect' => '/register/verify', 'message' => 'Please verify your email first.']);
            return;
        }

        // Reset attempts, update login info
        $this->db->query(
            'UPDATE smmPanel_users SET login_attempts = 0, locked_until = NULL, last_login_at = NOW(), last_login_ip = ? WHERE id = ?',
            [$this->getClientIp(), $user['id']]
        );

        $this->createUserSession((int) $user['id'], $remember);

        $redirect = ($user['role'] !== 'user') ? '/admin' : '/dashboard';
        $this->json(['success' => true, 'redirect' => $redirect]);
    }

    // ── Logout ────────────────────────────────────────────────

    public function logout(array $params): void
    {
        $_SESSION = [];
        session_destroy();
        setcookie('SMMPSID', '', time() - 3600, '/');
        $this->redirect('/login');
    }

    // ── Forgot Password ───────────────────────────────────────

    public function forgotPassword(array $params): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->view('auth/forgot-password', ['title' => 'Reset Password']);
            return;
        }

        $email = strtolower(trim($_POST['email'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['success' => false, 'message' => 'Invalid email address.']);
            return;
        }

        // Always respond with success to avoid user enumeration
        $user = $this->db->fetchOne(
            'SELECT id, full_name FROM smmPanel_users WHERE email = ? AND status != "deleted"',
            [$email]
        );

        if ($user) {
            $token     = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);

            $this->db->query(
                'UPDATE smmPanel_password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL',
                [$user['id']]
            );

            $this->db->insert('smmPanel_password_resets', [
                'user_id'    => $user['id'],
                'email'      => $email,
                'token_hash' => $tokenHash,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            ]);

            $resetUrl = Config::get('APP_URL') . '/reset-password/' . $token;
            $this->mailer->sendPasswordReset($email, $user['full_name'], $resetUrl);
        }

        $this->json(['success' => true, 'message' => 'If that email exists, a reset link has been sent.']);
    }

    public function resetPassword(array $params): void
    {
        $token     = $params['token'] ?? '';
        $tokenHash = hash('sha256', $token);

        $record = $this->db->fetchOne(
            'SELECT * FROM smmPanel_password_resets
             WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()
             LIMIT 1',
            [$tokenHash]
        );

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if (!$record) {
                $this->view('auth/reset-invalid', ['title' => 'Link Expired']);
                return;
            }

            $this->view('auth/reset-password', ['title' => 'Set New Password', 'token' => $token]);
            return;
        }

        if (!$record) {
            $this->json(['success' => false, 'message' => 'Reset link is invalid or expired.']);
            return;
        }

        $password  = $_POST['password'] ?? '';
        $confirm   = $_POST['confirm_password'] ?? '';

        $errors = $this->validatePassword($password, $confirm);
        if ($errors) {
            $this->json(['success' => false, 'message' => $errors]);
            return;
        }

        $this->db->transaction(function (Database $db) use ($record, $password, $tokenHash) {
            $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            $db->query(
                'UPDATE smmPanel_users SET password = ? WHERE id = ?',
                [$newHash, $record['user_id']]
            );

            // Invalidate the token
            $db->query(
                'UPDATE smmPanel_password_resets SET used_at = NOW() WHERE token_hash = ?',
                [$tokenHash]
            );

            // Destroy all active sessions
            $db->query(
                'DELETE FROM smmPanel_sessions WHERE user_id = ?',
                [$record['user_id']]
            );
        });

        $this->json(['success' => true, 'message' => 'Password updated successfully.', 'redirect' => '/login']);
    }

    // ── Helpers ───────────────────────────────────────────────

    private function createUserSession(int $userId, bool $remember): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id']  = $userId;
        $_SESSION['login_at'] = time();

        $lifetime = $remember ? 60 * 60 * 24 * (int) Config::get('REMEMBER_ME_DAYS', 30) : 86400;

        $this->db->insert('smmPanel_sessions', [
            'id'          => session_id(),
            'user_id'     => $userId,
            'ip_address'  => $this->getClientIp(),
            'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'remember_me' => $remember ? 1 : 0,
            'expires_at'  => date('Y-m-d H:i:s', time() + $lifetime),
        ]);
    }

    private function validateRegistration(array $data): array
    {
        $errors = [];

        if (empty($data['full_name']) || strlen($data['full_name']) < 2) {
            $errors['full_name'] = 'Full name must be at least 2 characters.';
        }

        if (empty($data['username']) || !preg_match('/^[a-zA-Z0-9_]{3,20}$/', $data['username'])) {
            $errors['username'] = 'Username must be 3-20 characters (letters, numbers, underscore).';
        }

        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }

        $pwError = $this->validatePassword($data['password'] ?? '', $data['confirm_password'] ?? '');
        if ($pwError) {
            $errors['password'] = $pwError;
        }

        return $errors;
    }

    private function validatePassword(string $password, string $confirm): ?string
    {
        if (strlen($password) < 8) {
            return 'Password must be at least 8 characters.';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            return 'Password must contain at least one uppercase letter.';
        }

        if (!preg_match('/[0-9]/', $password)) {
            return 'Password must contain at least one number.';
        }

        if ($password !== $confirm) {
            return 'Passwords do not match.';
        }

        return null;
    }

    private function generateReferralCode(): string
    {
        do {
            $code = strtoupper(substr(md5(uniqid('', true)), 0, 8));
            $exists = $this->db->fetchColumn(
                'SELECT COUNT(*) FROM smmPanel_users WHERE referral_code = ?',
                [$code]
            );
        } while ($exists > 0);

        return $code;
    }

    private function verifyCaptcha(string $token): bool
    {
        if (empty($token)) {
            return false;
        }

        $secret = Config::get('HCAPTCHA_SECRET', '');

        if (empty($secret)) {
            return true; // Not configured, skip
        }

        $ch = curl_init('https://hcaptcha.com/siteverify');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query(['secret' => $secret, 'response' => $token]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $result = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($result, true);

        return (bool) ($data['success'] ?? false);
    }
}
