<?php

namespace App\Support\Mail;

use App\Models\Setting;
use Illuminate\Support\Facades\Mail;

/**
 * Applies the e-mail settings saved in the admin on top of the .env configuration (SMTP server, sender).
 */
class MailSettings
{
    public static function apply(): void
    {
        $from = trim((string) Setting::get('mail_from_address'));
        $host = trim((string) Setting::get('mail_host'));
        $useSmtp = Setting::get('mail_mode') === 'smtp' && $host !== '';

        if ($from !== '') {
            config([
                'mail.from.address' => $from,
                'mail.from.name' => Setting::interpolate((string) Setting::get('mail_from_name')) ?: config('mail.from.name'),
            ]);
        }

        if (! $useSmtp) {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp' => array_merge((array) config('mail.mailers.smtp'), [
                'transport' => 'smtp',
                'host' => $host,
                'port' => (int) Setting::get('mail_port'),
                'scheme' => Setting::get('mail_encryption') === 'ssl' ? 'smtps' : 'smtp',
                'username' => trim((string) Setting::get('mail_username')) ?: null,
                'password' => (string) Setting::get('mail_password') ?: null,
                'timeout' => 10,
            ]),
        ]);

        Mail::purge('smtp');
    }
}
