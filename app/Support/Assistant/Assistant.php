<?php

namespace App\Support\Assistant;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\Setting;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Handles one customer message: builds the knowledge, calls the model, stores the exchange
 * and returns the reply with the product cards it mentions.
 */
class Assistant
{
    /**
     * @return array{reply: string, products: list<array<string, mixed>>, conversation: string|null, fallback: bool}
     */
    public static function reply(string $message, ?string $token, ?User $user = null): array
    {
        $message = Str::limit(trim($message), 1000, '');
        $conversation = self::conversation($token, $user);
        $history = $conversation ? self::history($conversation) : [];

        $userMessages = [...array_column(array_filter($history, fn (array $line): bool => $line['role'] === 'user'), 'content'), $message];

        if (! self::usesModel() || self::dailyLimitReached()) {
            return self::viaBot($conversation, $message, $userMessages, $user);
        }

        try {
            $knowledge = KnowledgeBase::build($userMessages, $user);
            $reply = LlmClient::complete($knowledge['prompt'], self::alternate([...$history, ['role' => 'user', 'content' => $message]]));
        } catch (RuntimeException) {
            // The provider is down or misconfigured: the built-in bot still answers.
            return self::viaBot($conversation, $message, $userMessages, $user);
        }

        $cards = self::cards($reply, $knowledge['products']);
        self::store($conversation, $message, $reply, $cards);

        return ['reply' => $reply, 'products' => $cards, 'conversation' => $conversation?->token, 'fallback' => false];
    }

    /**
     * The model is used only when the shop chose it AND a key exists; otherwise the built-in bot answers.
     */
    public static function usesModel(): bool
    {
        return Setting::get('ai_mode') === 'llm' && LlmClient::isConfigured();
    }

    /**
     * @param  list<string>  $userMessages
     * @return array{reply: string, products: list<array<string, mixed>>, conversation: string|null, fallback: bool}
     */
    private static function viaBot(?AiConversation $conversation, string $message, array $userMessages, ?User $user): array
    {
        $answer = ChatBot::answer($message, $userMessages, $user);
        $cards = self::cardsFromProducts($answer['products']);
        self::store($conversation, $message, $answer['reply'], $cards, $answer['unknown']);

        return ['reply' => $answer['reply'], 'products' => $cards, 'conversation' => $conversation?->token, 'fallback' => false];
    }

    /**
     * @return array{reply: string, products: list<array<string, mixed>>, conversation: string|null, fallback: bool}
     */
    private static function fallback(?AiConversation $conversation, string $message): array
    {
        $reply = Setting::interpolate((string) Setting::get('ai_fallback_message'));
        self::store($conversation, $message, $reply, [], true);

        return ['reply' => $reply, 'products' => [], 'conversation' => $conversation?->token, 'fallback' => true];
    }

    private static function conversation(?string $token, ?User $user): ?AiConversation
    {
        if (! Setting::get('ai_save_conversations')) {
            return null;
        }

        $existing = $token && preg_match('/^[A-Za-z0-9]{40}$/', $token) ? AiConversation::query()->where('token', $token)->first() : null;

        return $existing ?? AiConversation::query()->create(['token' => Str::random(40), 'user_id' => $user?->id]);
    }

    /**
     * @return list<array{role: string, content: string}>
     */
    private static function history(AiConversation $conversation): array
    {
        return $conversation->messages()->reorder('id', 'desc')->limit((int) Setting::get('ai_history_length'))->get()
            ->reverse()->map(fn (AiMessage $line): array => ['role' => $line->role, 'content' => $line->content])->values()->all();
    }

    /**
     * Merges consecutive messages of the same role and makes sure the list starts with the customer.
     *
     * @param  list<array{role: string, content: string}>  $messages
     * @return list<array{role: 'user'|'assistant', content: string}>
     */
    private static function alternate(array $messages): array
    {
        $result = [];

        foreach ($messages as $line) {
            if ($result === [] && $line['role'] !== 'user') {
                continue;
            }

            if ($result !== [] && $result[array_key_last($result)]['role'] === $line['role']) {
                $result[array_key_last($result)]['content'] .= "\n".$line['content'];

                continue;
            }

            $result[] = $line;
        }

        return $result;
    }

    private static function dailyLimitReached(): bool
    {
        $limit = (int) Setting::get('ai_daily_limit');

        return $limit > 0 && AiMessage::query()->where('role', 'user')->where('created_at', '>=', now()->startOfDay())->count() >= $limit;
    }

    /**
     * @param  list<array<string, mixed>>  $products
     * @return list<array<string, mixed>>
     */
    private static function cards(string $reply, array $products): array
    {
        $text = Str::ascii(mb_strtolower($reply));

        return self::cardsFromProducts(collect($products)
            ->filter(fn (array $product): bool => str_contains($text, Str::ascii(mb_strtolower($product['name']))))
            ->all());
    }

    /**
     * @param  list<array<string, mixed>>  $products
     * @return list<array<string, mixed>>
     */
    private static function cardsFromProducts(array $products): array
    {
        return collect($products)
            ->take(4)
            ->map(fn (array $product): array => [
                'slug' => $product['slug'],
                'name' => $product['name'],
                'variant' => $product['variant'],
                'price' => ! empty($product['price_from']) ? 'Dès '.Money::format($product['price_from']) : Money::format($product['price']),
                'old_price' => empty($product['price_from']) && $product['old_price'] ? Money::format($product['old_price']) : null,
                'image' => $product['image'] ?? null,
                'url' => route('product', $product['slug']),
                'has_variants' => ! empty($product['variants']),
                'in_stock' => (int) ($product['stock'] ?? 0) > 0,
            ])->values()->all();
    }

    /**
     * @param  list<array<string, mixed>>  $cards
     */
    private static function store(?AiConversation $conversation, string $message, string $reply, array $cards, bool $failed = false): void
    {
        if (! $conversation) {
            return;
        }

        $conversation->messages()->create(['role' => 'user', 'content' => $message]);
        $conversation->messages()->create(['role' => 'assistant', 'content' => $reply, 'meta' => ['products' => array_column($cards, 'slug'), 'fallback' => $failed]]);
        $conversation->update([
            'summary' => $conversation->summary ?: Str::limit($message, 120, '…'),
            'messages_count' => $conversation->messages()->count(),
            'last_message_at' => now(),
        ]);
    }
}
