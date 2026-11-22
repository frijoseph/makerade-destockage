<?php

namespace App\Support\Assistant;

use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Support\Catalog;
use App\Support\Money;
use App\Support\ShopSettings;
use Illuminate\Support\Str;

/**
 * Built-in chatbot: no API, no subscription. It recognises what the visitor asks (the shop's own question/answer
 * rules first, then delivery, payment, returns, contact, order tracking, products) and answers from the shop
 * settings and the database. Order details are only given for a number + matching phone, or a logged-in account.
 */
class ChatBot
{
    /** @var array<string, list<string>> */
    private const INTENTS = [
        'delivery' => ['livraison', 'livrer', 'livre', 'delai', 'expedition', 'expedier', 'frais de port', 'frais', 'delivery', 'shipping'],
        'payment' => ['paiement', 'payer', 'payement', 'mobile money', 'wave', 'orange money', 'mtn', 'moov', 'cash', 'carte', 'especes', 'a la livraison', 'payment', 'pay'],
        'returns' => ['retour', 'retourner', 'rembours', 'echange', 'echanger', 'garantie', 'defectueux', 'return', 'refund'],
        'contact' => ['contact', 'telephone', 'appeler', 'numero', 'adresse', 'email', 'mail', 'joindre', 'localisation', 'boutique physique'],
        'human' => ['conseiller', 'humain', 'agent', 'parler a quelqu', 'whatsapp', 'service client', 'responsable'],
        'account' => ['compte', 'inscription', 'inscrire', 'connexion', 'connecter', 'mot de passe', 'login'],
        'offers' => ['promo', 'promotion', 'reduction', 'remise', 'solde', 'bon plan', 'offre', 'code'],
        'popular' => ['populaire', 'meilleur', 'best', 'conseille', 'recommande', 'suggere', 'idee', 'plus vendu', 'nouveaute'],
        'order' => ['ma commande', 'suivi', 'suivre', 'colis', 'ou en est', 'statut', 'ai commande', 'track'],
        'greeting' => ['bonjour', 'bonsoir', 'salut', 'hello', 'hey', 'coucou', 'salam'],
        'thanks' => ['merci', 'thanks', 'thank you', 'super', 'parfait', 'genial'],
    ];

    /** @var list<string> */
    private const STOP_WORDS = ['les', 'des', 'une', 'pour', 'avec', 'dans', 'est', 'que', 'qui', 'vous', 'nous', 'quel', 'quels', 'quelle', 'quelles', 'avez', 'cherche', 'voudrais', 'veux', 'bonjour', 'merci', 'produit', 'produits', 'prix', 'combien', 'coute', 'vendez', 'vend', 'avez', 'pas', 'sur', 'par', 'mon', 'mes', 'ses', 'son', 'the', 'and', 'you', 'have', 'what', 'how', 'much', 'quoi', 'aussi', 'plus', 'moins', 'tout', 'tous', 'toutes', 'cette', 'cet', 'ces', 'etes', 'sont', 'fait', 'faire', 'puis', 'peut', 'peux', 'bien'];

    /**
     * @param  list<string>  $userMessages  the customer's messages in this conversation, the current one last
     * @return array{reply: string, products: list<array<string, mixed>>, unknown: bool}
     */
    public static function answer(string $message, array $userMessages, ?User $user = null): array
    {
        $shop = app(ShopSettings::class);
        $text = self::normalise($message);

        if ($rule = self::matchRule($text)) {
            return self::say(Setting::interpolate($rule));
        }

        if ($reply = self::orderReply($message, $text, $userMessages, $user)) {
            return self::say($reply);
        }

        $intent = self::intent($text);

        if ($intent === 'greeting' && str_word_count($text) <= 4) {
            return self::say(Setting::interpolate((string) Setting::get('bot_greeting_reply')));
        }

        if ($intent === 'thanks' && str_word_count($text) <= 5) {
            return self::say(Setting::interpolate((string) Setting::get('bot_thanks_reply')));
        }

        $found = Setting::get('ai_know_catalog') ? self::searchProducts($text) : [];

        if ($found !== [] && ! in_array($intent, ['delivery', 'payment', 'returns', 'contact', 'human', 'account'], true)) {
            return self::productsReply($found);
        }

        $reply = match ($intent) {
            'delivery' => Setting::get('ai_know_delivery') ? self::delivery($shop, false) : null,
            'payment' => Setting::get('ai_know_delivery') ? self::delivery($shop, true) : null,
            'returns' => self::returns($shop),
            'contact', 'human' => self::contact($shop),
            'account' => self::account(),
            'offers' => self::offers($shop),
            'popular' => null,
            default => null,
        };

        if ($reply !== null) {
            return self::say($reply);
        }

        if ($intent === 'popular' && Setting::get('ai_know_catalog')) {
            $top = collect(Catalog::products())->sortByDesc(fn (array $product): int => ($product['sold'] ?? 0) * 10 + ($product['hot'] ? 5 : 0))->take(4)->values()->all();

            if ($top !== []) {
                return self::productsReply($top, 'Voici nos produits les plus appréciés :');
            }
        }

        return ['reply' => Setting::interpolate((string) Setting::get('bot_unknown_reply')), 'products' => [], 'unknown' => true];
    }

    /**
     * @param  list<array<string, mixed>>  $products
     * @return array{reply: string, products: list<array<string, mixed>>, unknown: bool}
     */
    private static function say(string $reply, array $products = []): array
    {
        return ['reply' => $reply, 'products' => $products, 'unknown' => false];
    }

    private static function normalise(string $text): string
    {
        return trim((string) preg_replace('/\s+/', ' ', (string) preg_replace('/[^a-z0-9+@.\/ -]+/', ' ', Str::ascii(mb_strtolower($text)))));
    }

    /**
     * Does the text contain the keyword at the start of a word (so « livraisons » matches « livraison »)?
     */
    private static function has(string $text, string $keyword): bool
    {
        $keyword = self::normalise($keyword);

        return $keyword !== '' && (bool) preg_match('/(?<![a-z0-9])'.preg_quote($keyword, '/').'/', $text);
    }

    /**
     * The shop's own question/answer rules: the rule with the most (and longest) matching keywords wins.
     */
    private static function matchRule(string $text): ?string
    {
        $best = null;
        $bestScore = 0;

        foreach ((array) Setting::get('bot_rules') as $rule) {
            $score = 0;

            foreach (explode(',', (string) ($rule['keywords'] ?? '')) as $keyword) {
                if (self::has($text, $keyword)) {
                    $score += 10 + strlen(trim($keyword));
                }
            }

            if ($score > $bestScore && trim((string) ($rule['reply'] ?? '')) !== '') {
                $best = (string) $rule['reply'];
                $bestScore = $score;
            }
        }

        return $best;
    }

    private static function intent(string $text): ?string
    {
        $best = null;
        $bestScore = 0;

        foreach (self::INTENTS as $intent => $keywords) {
            $score = 0;

            foreach ($keywords as $keyword) {
                if (self::has($text, $keyword)) {
                    $score += strlen($keyword);
                }
            }

            if ($score > $bestScore) {
                $best = $intent;
                $bestScore = $score;
            }
        }

        return $best;
    }

    /**
     * @param  list<string>  $userMessages
     */
    private static function orderReply(string $message, string $text, array $userMessages, ?User $user): ?string
    {
        if (! Setting::get('ai_know_orders')) {
            return null;
        }

        $hasNumber = (bool) preg_match('/\b[A-Z]{2,5}-\d{4}-\d{3,6}\b/i', $message);
        $hasPhone = (bool) preg_match('/\+?\d[\d\s().-]{6,17}\d/', $message);
        $wantsOrder = self::intent($text) === 'order';

        if (! $hasNumber && ! $hasPhone && ! $wantsOrder) {
            return null;
        }

        ['orders' => $orders, 'gave_number' => $gaveNumber] = KnowledgeBase::verifiedOrders($userMessages, $user);

        if ($orders->isNotEmpty()) {
            return "Voici ce que je trouve pour votre commande :\n".$orders->map(fn (Order $order): string => KnowledgeBase::orderLine($order))->implode("\n")
                ."\n\nVous pouvez aussi la suivre sur ".route('track').'.';
        }

        if ($gaveNumber) {
            return 'Je ne retrouve pas cette commande avec ce numéro de téléphone. Vérifiez le numéro de commande et le téléphone utilisé lors de l’achat (avec l’indicatif du pays), puis envoyez-les-moi à nouveau.';
        }

        if ($user) {
            return 'Je ne trouve aucune commande sur votre compte pour le moment. Si vous avez commandé sans être connecté, envoyez-moi votre numéro de commande (ex. MK-2026-00001) et votre numéro de téléphone.';
        }

        if ($hasPhone && ! $hasNumber) {
            return 'Merci ! Il me faut aussi votre numéro de commande (ex. MK-2026-00001) pour la retrouver.';
        }

        return 'Pour suivre votre commande, envoyez-moi votre numéro de commande (ex. MK-2026-00001) et le numéro de téléphone utilisé lors de l’achat. Vous pouvez aussi la suivre sur '.route('track').'.';
    }

    private static function delivery(ShopSettings $shop, bool $paymentOnly): string
    {
        $block = preg_replace('/^## .*\n/', '', KnowledgeBase::deliveryAndPayments($shop)) ?? '';
        $lines = array_values(array_filter(explode("\n", $block)));
        $payment = array_values(array_filter($lines, fn (string $line): bool => str_contains($line, 'paiement')));
        $delivery = array_values(array_diff($lines, $payment));

        if ($paymentOnly) {
            return implode("\n", $payment ?: ['Contactez-nous pour connaître les moyens de paiement.']);
        }

        return "Voici nos conditions de livraison :\n".implode("\n", $delivery).($shop->delivery_zone ? "\nZone desservie : {$shop->delivery_zone}." : '');
    }

    private static function returns(ShopSettings $shop): string
    {
        $days = (int) Setting::get('return_days');

        return ($days > 0 ? "Vous pouvez retourner un article sous **{$days} jours**." : 'Contactez-nous pour toute demande de retour.')
            .' Les conditions détaillées sont sur '.url('/page/returns').'. En cas de souci, écrivez-nous : '.($shop->contact_email ?: $shop->contact_phone).'.';
    }

    private static function contact(ShopSettings $shop): string
    {
        $lines = array_filter([
            $shop->contact_phone ? "- Téléphone : {$shop->contact_phone}" : null,
            $shop->whatsapp_number ? "- WhatsApp : +{$shop->whatsapp_number}" : null,
            $shop->contact_email ? "- E-mail : {$shop->contact_email}" : null,
            $shop->contact_address ? "- Adresse : {$shop->contact_address}" : null,
        ]);

        return "Voici comment nous joindre :\n".implode("\n", $lines)."\nNous répondons sous {$shop->response_time}. Formulaire : ".route('contact').'.';
    }

    private static function account(): string
    {
        return 'Créez votre compte sur '.route('account').' avec votre numéro de téléphone (l’e-mail est facultatif) : vous retrouverez vos commandes et pourrez imprimer vos reçus.';
    }

    private static function offers(ShopSettings $shop): ?string
    {
        $offers = collect($shop->announcements())->map(fn (array $item): string => '- '.$item['text'])->implode("\n");

        return $offers !== '' ? "Nos offres du moment :\n{$offers}\nRetrouvez aussi les bons plans sur ".url('/best-deals').'.' : 'Retrouvez nos bons plans sur '.url('/best-deals').'.';
    }

    /**
     * Products matching the visitor's words (name, format, brand, category, description); empty when nothing matches.
     *
     * @return list<array<string, mixed>>
     */
    private static function searchProducts(string $text): array
    {
        $tokens = collect(preg_split('/[^a-z0-9]+/', $text) ?: [])
            ->filter(fn (string $token): bool => strlen($token) >= 3 && ! in_array($token, self::STOP_WORDS, true))
            ->unique()->values();

        if ($tokens->isEmpty()) {
            return [];
        }

        return collect(Catalog::products())->map(function (array $product) use ($tokens): array {
            $name = Str::ascii(mb_strtolower($product['name']));
            $haystack = Str::ascii(mb_strtolower(implode(' ', [$product['name'], $product['variant'] ?? '', $product['brand'] ?? '', $product['category'] ?? '', $product['description'] ?? ''])));
            $score = $tokens->sum(fn (string $token): int => str_contains($name, $token) ? 3 : (str_contains($haystack, $token) ? 1 : 0));

            return ['score' => $score, 'product' => $product];
        })->where('score', '>', 0)->sortByDesc('score')->pluck('product')->take(4)->values()->all();
    }

    /**
     * @param  list<array<string, mixed>>  $products
     * @return array{reply: string, products: list<array<string, mixed>>, unknown: bool}
     */
    private static function productsReply(array $products, string $intro = 'Voici ce que j’ai trouvé :'): array
    {
        $lines = collect($products)->map(function (array $product): string {
            $stock = (int) ($product['stock'] ?? 0);
            $price = ! empty($product['price_from']) ? 'dès '.Money::format($product['price_from']) : Money::format($product['price']);

            return "- **{$product['name']}** ".($product['variant'] ? "({$product['variant']}) " : '')."— {$price} — ".($stock <= 0 ? 'en rupture' : ($stock <= 5 ? "plus que {$stock} en stock" : 'en stock'));
        })->implode("\n");

        return self::say("{$intro}\n{$lines}\nTouchez une fiche ci-dessous pour voir le détail ou l’ajouter au panier.", $products);
    }
}
