<?php
declare(strict_types=1);
namespace SMMPanel\Services;

use SMMPanel\Core\Config;

final class EmailService
{
    private const RESEND_API_URL = 'https://api.resend.com/emails';

    private function send(string $to, string $name, string $subject, string $html): void
    {
        try {
            $payload = json_encode([
                'from'    => Config::get('MAIL_FROM_NAME', 'Shuvo SMM Panel')
                             . ' <' . Config::get('MAIL_FROM_EMAIL', 'onboarding@resend.dev') . '>',
                'to'      => [$to],
                'subject' => $subject,
                'html'    => $html,
            ], JSON_THROW_ON_ERROR);

            $ch = curl_init(self::RESEND_API_URL);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . Config::required('RESEND_API_KEY'),
                    'Content-Type: application/json',
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr  = curl_error($ch);
            curl_close($ch);

            if ($response === false || $httpCode >= 300) {
                error_log("[Email] Resend failed: HTTP {$httpCode} - {$curlErr} - {$response}");
            }
        } catch (\Throwable $e) {
            error_log('[Email] Send failed: ' . $e->getMessage());
        }
    }

    public function sendOtp(string $to, string $name, string $otp): void
    {
        $this->send($to, $name, 'Your OTP Code — Shuvo SMM Panel', $this->otpTemplate($name, $otp));
    }

    public function sendPasswordReset(string $to, string $name, string $url): void
    {
        $this->send($to, $name, 'Reset Your Password — Shuvo SMM Panel', $this->resetTemplate($name, $url));
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