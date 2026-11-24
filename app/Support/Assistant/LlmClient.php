<?php

namespace App\Support\Assistant;

use App\Models\Setting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * One text completion against the configured provider (Anthropic Messages API or an OpenAI-compatible
 * chat completions endpoint). Provider, model, key, URL and limits all come from the shop settings.
 */
class LlmClient
{
    private const DEFAULT_MODELS = ['anthropic' => 'claude-haiku-5-5', 'openai' => 'gpt-4o-mini'];

    private const DEFAULT_URLS = ['anthropic' => 'https://api.anthropic.com', 'openai' => 'https://api.openai.com/v1'];

    public static function apiKey(): string
    {
        return trim((string) (Setting::get('ai_api_key') ?: config('shop.ai.api_key')));
    }

    public static function isConfigured(): bool
    {
        return self::apiKey() !== '';
    }

    /**
     * @param  list<array{role: 'user'|'assistant', content: string}>  $messages  alternating roles, starting with the customer
     *
     * @throws RuntimeException when the provider is unreachable or answers with an error
     */
    public static function complete(string $system, array $messages, ?int $maxTokens = null, ?float $temperature = null): string
    {
        $key = self::apiKey();

        if ($key === '') {
            throw new RuntimeException('Aucune clé API n’est configurée.');
        }

        $provider = Setting::get('ai_provider') === 'openai' ? 'openai' : 'anthropic';
        $model = trim((string) Setting::get('ai_model')) ?: self::DEFAULT_MODELS[$provider];
        $base = rtrim(trim((string) Setting::get('ai_base_url')) ?: self::DEFAULT_URLS[$provider], '/');
        $maxTokens ??= (int) Setting::get('ai_max_tokens');
        $temperature ??= (float) Setting::get('ai_temperature');

        try {
            $response = $provider === 'anthropic'
                ? Http::withHeaders(['x-api-key' => $key, 'anthropic-version' => '2023-06-01'])->acceptJson()->timeout(30)
                    ->post("{$base}/v1/messages", ['model' => $model, 'max_tokens' => $maxTokens, 'temperature' => $temperature, 'system' => $system, 'messages' => $messages])
                : Http::withToken($key)->acceptJson()->timeout(30)
                    ->post("{$base}/chat/completions", ['model' => $model, 'max_tokens' => $maxTokens, 'temperature' => $temperature, 'messages' => [['role' => 'system', 'content' => $system], ...$messages]]);
        } catch (ConnectionException $exception) {
            Log::warning('Assistant IA injoignable : '.$exception->getMessage());

            throw new RuntimeException('Le service d’IA est injoignable.', previous: $exception);
        }

        if ($response->failed()) {
            $detail = $response->json('error.message') ?? $response->json('error') ?? $response->reason();
            Log::warning('Assistant IA : erreur '.$response->status().' — '.(is_string($detail) ? $detail : json_encode($detail)));

            throw new RuntimeException('Le fournisseur d’IA a répondu '.$response->status().' : '.(is_string($detail) ? $detail : 'erreur inconnue').'.');
        }

        $text = $provider === 'anthropic'
            ? collect($response->json('content', []))->where('type', 'text')->pluck('text')->implode("\n")
            : (string) $response->json('choices.0.message.content', '');

        $text = trim($text);

        if ($text === '') {
            throw new RuntimeException('Le fournisseur d’IA a renvoyé une réponse vide.');
        }

        return $text;
    }
}
