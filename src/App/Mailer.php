<?php

namespace App;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class Mailer
{
    private static ?PHPMailer $mailer = null;
    private static bool $enabled = false;

    public static function init(): void
    {
        if (self::$mailer !== null) {
            return;
        }

        $driver = strtolower(getenv('MAIL_DRIVER') ?: '');
        if ($driver !== 'smtp') {
            self::$enabled = false;
            return;
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = getenv('MAIL_HOST') ?: 'localhost';
            $mail->SMTPAuth   = true;
            $mail->Username   = getenv('MAIL_USERNAME') ?: '';
            $mail->Password   = getenv('MAIL_PASSWORD') ?: '';
            $mail->SMTPSecure = getenv('MAIL_ENCRYPTION') ?: 'tls';
            $mail->Port       = (int) (getenv('MAIL_PORT') ?: 587);

            $mail->setFrom(
                getenv('MAIL_FROM_ADDRESS') ?: 'noreply@exchangeportal.com',
                getenv('MAIL_FROM_NAME') ?: 'Global Exchange Portal'
            );

            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';

            self::$mailer = $mail;
            self::$enabled = true;
        } catch (PHPMailerException $e) {
            app_log()->warning('Mailer init failed: ' . $e->getMessage());
            self::$enabled = false;
        }
    }

    public static function send(string $to, string $subject, string $htmlBody): bool
    {
        if (!self::$enabled || self::$mailer === null) {
            app_log()->debug('Mail skipped (not configured)', ['to' => $to, 'subject' => $subject]);
            return false;
        }

        try {
            $mail = clone self::$mailer;
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = strip_tags($htmlBody);
            $mail->send();
            app_log()->info('Email sent', ['to' => $to, 'subject' => $subject]);
            return true;
        } catch (PHPMailerException $e) {
            app_log()->error('Mail send failed', [
                'to' => $to, 'subject' => $subject, 'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public static function sendTemplate(string $to, string $subject, string $template, array $data = []): bool
    {
        $html = self::renderTemplate($template, $data);
        return self::send($to, $subject, $html);
    }

    private static function renderTemplate(string $name, array $data): string
    {
        $file = dirname(__DIR__, 2) . '/resources/emails/' . $name . '.html';
        if (!file_exists($file)) {
            app_log()->warning('Email template not found: ' . $file);
            return '<p>' . ($data['message'] ?? '') . '</p>';
        }

        $html = file_get_contents($file);
        foreach ($data as $key => $val) {
            $html = str_replace('{{' . $key . '}}', htmlspecialchars((string) $val, ENT_QUOTES, 'UTF-8'), $html);
        }
        return $html;
    }

    public static function isEnabled(): bool
    {
        return self::$enabled;
    }
}
