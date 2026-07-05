<?php
declare(strict_types=1);
namespace SMMPanel\Services;

use SMMPanel\Core\Config;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

final class EmailService
{
    private function mailer(): PHPMailer
    {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = Config::required('SMTP_HOST');
        $mail->SMTPAuth   = true;
        $mail->Username   = Config::required('SMTP_USER');
        $mail->Password   = Config::required('SMTP_PASS');
        $mail->SMTPSecure = Config::get('SMTP_ENCRYPTION', 'tls') === 'ssl'
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int) Config::get('SMTP_PORT', 587);
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom(
            Config::get('SMTP_USER', 'noreply@shuvosmm.com'),
            Config::get('MAIL_FROM_NAME', 'Shuvo SMM Panel')
        );
        return $mail;
    }

    public function sendOtp(string $to, string $name, string $otp): void
    {
        try {
            $mail = $this->mailer();
            $mail->addAddress($to, $name);
            $mail->Subject = 'Your OTP Code — Shuvo SMM Panel';
            $mail->isHTML(true);
            $mail->Body = $this->otpTemplate($name, $otp);
            $mail->send();
        } catch (\Throwable $e) {
            error_log('[Email] OTP failed: ' . $e->getMessage());
        }
    }

    public function sendPasswordReset(string $to, string $name, string $url): void
    {
        try {
            $mail = $this->mailer();
            $mail->addAddress($to, $name);
            $mail->Subject = 'Reset Your Password — Shuvo SMM Panel';
            $mail->isHTML(true);
            $mail->Body = $this->resetTemplate($name, $url);
            $mail->send();
        } catch (\Throwable $e) {
            error_log('[Email] Reset failed: ' . $e->getMessage());
        }
    }

    public function send(string $to, string $name, string $subject, string $html): void
    {
        try {
            $mail = $this->mailer();
            $mail->addAddress($to, $name);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $html;
            $mail->send();
        } catch (\Throwable $e) {
            error_log('[Email] Send failed: ' . $e->getMessage());
        }
    }

    private function otpTemplate(string $name, string $otp): string
    {
        return <<<HTML
        <div style="font-family:Inter,sans-serif;max-width:480px;margin:0 auto;background:#05060A;color:#fff;padding:40px;border-radius:16px;">
          <h2 style="color:#7C5CFF;margin-bottom:8px;">Verify your email</h2>
          <p style="color:#A8B3CF;">Hi {$name}, use the code below to verify your account.</p>
          <div style="background:rgba(124,92,255,0.15);border:1px solid rgba(124,92,255,0.3);border-radius:12px;padding:24px;text-align:center;margin:24px 0;">
            <span style="font-size:36px;font-weight:700;letter-spacing:12px;color:#7C5CFF;">{$otp}</span>
          </div>
          <p style="color:#A8B3CF;font-size:14px;">This code expires in <strong>15 minutes</strong>. Do not share it with anyone.</p>
        </div>
        HTML;
    }

    private function resetTemplate(string $name, string $url): string
    {
        return <<<HTML
        <div style="font-family:Inter,sans-serif;max-width:480px;margin:0 auto;background:#05060A;color:#fff;padding:40px;border-radius:16px;">
          <h2 style="color:#7C5CFF;margin-bottom:8px;">Reset your password</h2>
          <p style="color:#A8B3CF;">Hi {$name}, click the button below to set a new password.</p>
          <a href="{$url}" style="display:inline-block;margin:24px 0;padding:14px 32px;background:linear-gradient(135deg,#7C5CFF,#00D4FF);color:#fff;text-decoration:none;border-radius:999px;font-weight:700;">Reset Password</a>
          <p style="color:#A8B3CF;font-size:14px;">Link expires in <strong>1 hour</strong>. If you didn't request this, ignore this email.</p>
        </div>
        HTML;
    }
}
