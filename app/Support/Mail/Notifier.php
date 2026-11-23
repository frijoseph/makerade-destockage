<?php

namespace App\Support\Mail;

use App\Mail\ShopMail;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Setting;
use App\Support\ShopSettings;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Throwable;

/**
 * The automatic e-mails of the shop. Each one has its own switch in the admin, and a mail failure
 * (SMTP down, wrong password…) is reported to the logs but never breaks an order or a contact form.
 */
class Notifier
{
    public static function orderPlaced(Order $order): void
    {
        if (! Setting::get('mail_enabled')) {
            return;
        }

        $order->loadMissing('items', 'customer');
        $shop = app(ShopSettings::class);
        $summary = self::summary($order, $shop);

        if (Setting::get('mail_admin_new_order') && ($to = self::adminAddress())) {
            self::send($to, new ShopMail(
                mailSubject: 'Nouvelle commande '.$order->number.' — '.$shop->money($order->total),
                paragraphs: ['Une nouvelle commande vient d’être passée sur la boutique.'],
                order: $summary,
                details: self::orderDetails($order, $shop),
                actionLabel: 'Ouvrir la commande',
                actionUrl: route('admin.orders.show', $order),
                replyToAddress: $order->customer?->email,
            ));
        }

        if (Setting::get('mail_customer_order') && ($to = self::customerAddress($order))) {
            self::send($to, new ShopMail(
                mailSubject: self::fill((string) Setting::get('mail_customer_order_subject'), $order, $shop),
                paragraphs: [self::fill((string) Setting::get('mail_customer_order_intro'), $order, $shop)],
                order: $summary,
                details: [
                    ['label' => 'Livraison', 'value' => collect([$order->shipping_address, $order->shipping_city])->filter()->implode(', ') ?: '—'],
                    ['label' => 'Paiement', 'value' => $order->payment_method->label()],
                ],
                actionLabel: 'Voir ma commande',
                actionUrl: URL::temporarySignedRoute('checkout.show', now()->addDays(7), ['order' => $order->number]),
            ));
        }
    }

    public static function orderStatusChanged(Order $order): void
    {
        if (! Setting::get('mail_enabled') || ! Setting::get('mail_customer_status')) {
            return;
        }

        if (! in_array($order->status->value, (array) Setting::get('mail_customer_status_statuses'), true)) {
            return;
        }

        $order->loadMissing('items', 'customer');
        $to = self::customerAddress($order);

        if (! $to) {
            return;
        }

        $shop = app(ShopSettings::class);

        self::send($to, new ShopMail(
            mailSubject: self::fill((string) Setting::get('mail_customer_status_subject'), $order, $shop),
            paragraphs: [self::fill((string) Setting::get('mail_customer_status_intro'), $order, $shop)],
            actionLabel: 'Suivre ma commande',
            actionUrl: route('track'),
        ));
    }

    public static function contactReceived(ContactMessage $message): void
    {
        if (! Setting::get('mail_enabled') || ! Setting::get('mail_admin_contact') || ! ($to = self::adminAddress())) {
            return;
        }

        $shop = app(ShopSettings::class);

        self::send($to, new ShopMail(
            mailSubject: 'Nouveau message de '.$message->name.($message->subject ? ' : '.$message->subject : ''),
            paragraphs: ['Un visiteur vient d’écrire depuis la page Contact.'],
            details: array_values(array_filter([
                ['label' => 'Nom', 'value' => $message->name],
                ['label' => 'Téléphone', 'value' => $shop->phone($message->phone)],
                $message->email ? ['label' => 'E-mail', 'value' => $message->email] : null,
                $message->subject ? ['label' => 'Sujet', 'value' => $message->subject] : null,
                ['label' => 'Message', 'value' => $message->message],
            ])),
            actionLabel: 'Ouvrir dans l’administration',
            actionUrl: route('admin.messages.show', $message),
            replyToAddress: $message->email,
        ));
    }

    private static function send(string $to, ShopMail $mail): void
    {
        try {
            Mail::to($to)->send($mail);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private static function adminAddress(): ?string
    {
        $address = trim((string) Setting::get('mail_admin_address')) ?: trim((string) Setting::get('contact_email'));

        return filter_var($address, FILTER_VALIDATE_EMAIL) ? $address : null;
    }

    private static function customerAddress(Order $order): ?string
    {
        $address = trim((string) ($order->customer?->email ?: $order->user?->email));

        return filter_var($address, FILTER_VALIDATE_EMAIL) ? $address : null;
    }

    /**
     * Replaces the order placeholders ({order_number}, {customer_name}, {total}, {status}) and the shop settings.
     */
    private static function fill(string $text, Order $order, ShopSettings $shop): string
    {
        return strtr(Setting::interpolate($text), [
            '{order_number}' => $order->number,
            '{customer_name}' => trim(explode(' ', trim((string) ($order->shipping_name ?? $order->customer?->name)))[0] ?? ''),
            '{total}' => $shop->money($order->total),
            '{status}' => mb_strtolower($order->status->label()),
        ]);
    }

    /**
     * @return array{lines: list<array{name: string, quantity: int, total: string}>, totals: list<array{label: string, value: string, strong?: bool}>}
     */
    private static function summary(Order $order, ShopSettings $shop): array
    {
        $totals = [['label' => 'Sous-total', 'value' => $shop->money($order->subtotal)]];
        $totals[] = ['label' => 'Livraison', 'value' => (float) $order->shipping_fee ? $shop->money($order->shipping_fee) : 'Gratuite'];

        if ((float) $order->discount) {
            $totals[] = ['label' => 'Réduction'.($order->coupon_code ? ' ('.$order->coupon_code.')' : ''), 'value' => '− '.$shop->money($order->discount)];
        }

        $totals[] = ['label' => 'Total', 'value' => $shop->money($order->total), 'strong' => true];

        return [
            'lines' => $order->items->map(fn ($item): array => ['name' => $item->product_name, 'quantity' => (int) $item->quantity, 'total' => $shop->money($item->line_total)])->all(),
            'totals' => $totals,
        ];
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    private static function orderDetails(Order $order, ShopSettings $shop): array
    {
        return [
            ['label' => 'Commande', 'value' => $order->number],
            ['label' => 'Client', 'value' => (string) ($order->shipping_name ?? $order->customer?->name)],
            ['label' => 'Téléphone', 'value' => $shop->phone($order->shipping_phone)],
            ['label' => 'Livraison', 'value' => collect([$order->shipping_address, $order->shipping_city])->filter()->implode(', ') ?: '—'],
            ['label' => 'Paiement', 'value' => $order->payment_method->label()],
        ];
    }
}
