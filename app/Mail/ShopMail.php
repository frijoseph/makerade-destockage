<?php

namespace App\Mail;

use App\Models\Setting;
use App\Support\ShopSettings;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * One branded e-mail layout for every message of the shop: paragraphs, an optional order recap and a button.
 */
class ShopMail extends Mailable
{
    /**
     * @param  list<string>  $paragraphs
     * @param  array{lines: list<array{name: string, quantity: int, total: string}>, totals: list<array{label: string, value: string, strong?: bool}>}|null  $order
     * @param  list<array{label: string, value: string}>  $details
     */
    public function __construct(
        public string $mailSubject,
        public array $paragraphs,
        public ?string $greeting = null,
        public ?array $order = null,
        public array $details = [],
        public ?string $actionLabel = null,
        public ?string $actionUrl = null,
        public ?string $replyToAddress = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
            replyTo: $this->replyToAddress ? [new Address($this->replyToAddress)] : [],
        );
    }

    public function content(): Content
    {
        $shop = app(ShopSettings::class);
        $color = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $shop->theme_color) ? $shop->theme_color : '#0f6a26';

        return new Content(view: 'emails.shop', with: [
            'shopName' => Setting::get('shop_name'),
            'color' => $color,
            'signature' => Setting::interpolate((string) Setting::get('mail_signature')),
            'contact' => collect([$shop->contact_phone, $shop->contact_email])->filter()->implode(' · '),
        ]);
    }
}
