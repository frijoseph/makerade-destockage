<?php

namespace App\Support\Content;

use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use App\Support\Assistant\LlmClient;
use App\Support\RichText;
use Illuminate\Support\Str;
use Random\Engine\Mt19937;
use Random\Randomizer;
use Throwable;

/**
 * Writes product descriptions and blog articles from a name.
 *
 * Without any API the text is composed from the phrase banks of config/content.php: the choice of phrases depends on the
 * name (the same name always gives the same text, two names give two different texts) and on a "variation" number that the
 * "Rewrite" button increments. A text identical to one already used by another product or article is never returned.
 * When the shop is set to use an AI model (Paramètres › Assistant IA) and the option is on, the model writes the text instead,
 * and the composed text is the fallback when the model cannot be reached.
 */
class DescriptionWriter
{
    private const ATTEMPTS = 20;

    /**
     * @param  array{name: string, variant?: ?string, category?: ?string, brand?: ?string}  $input
     * @return array{html: string, source: 'ai'|'template'}
     */
    public static function product(array $input, int $variation = 0, ?int $ignoreProductId = null): array
    {
        $facts = self::productFacts($input);
        $used = self::usedTexts(Product::query()->when($ignoreProductId, fn ($query, $id) => $query->whereKeyNot($id))->whereNotNull('description')->pluck('description')->all());

        if (self::useModel()) {
            try {
                $html = self::askModel(self::productPrompt($facts), 700, $variation);

                if ($html !== '' && ! in_array(self::normalise($html), $used, true)) {
                    return ['html' => $html, 'source' => 'ai'];
                }
            } catch (Throwable) {
                // The composed text below takes over.
            }
        }

        for ($attempt = 0; $attempt < self::ATTEMPTS; $attempt++) {
            $html = self::composeProduct($facts, self::seed($input['name'], $variation, $attempt));

            if (! in_array(self::normalise($html), $used, true)) {
                return ['html' => $html, 'source' => 'template'];
            }
        }

        return ['html' => $html, 'source' => 'template'];
    }

    /**
     * @return array{excerpt: string, html: string, source: 'ai'|'template'}
     */
    public static function article(string $title, ?string $tag = null, int $variation = 0, ?int $ignorePageId = null): array
    {
        $facts = ['title' => trim($title), 'tag' => trim((string) $tag)];
        $used = self::usedTexts(Page::query()->when($ignorePageId, fn ($query, $id) => $query->whereKeyNot($id))->whereNotNull('body')->pluck('body')->all());

        if (self::useModel()) {
            try {
                $reply = self::askModel(self::articlePrompt($facts), 1800, $variation);

                if (preg_match('#<excerpt>(.*?)</excerpt>#s', $reply, $excerpt) && preg_match('#<body>(.*)</body>#s', $reply, $body)) {
                    $html = RichText::sanitize($body[1]);

                    if ($html !== '' && ! in_array(self::normalise($html), $used, true)) {
                        return ['excerpt' => Str::limit(trim(strip_tags($excerpt[1])), 300, ''), 'html' => $html, 'source' => 'ai'];
                    }
                }
            } catch (Throwable) {
                // The composed text below takes over.
            }
        }

        for ($attempt = 0; $attempt < self::ATTEMPTS; $attempt++) {
            $draft = self::composeArticle($facts, self::seed($facts['title'], $variation, $attempt));

            if (! in_array(self::normalise($draft['html']), $used, true)) {
                return $draft + ['source' => 'template'];
            }
        }

        return $draft + ['source' => 'template'];
    }

    public static function enabled(): bool
    {
        return (bool) Setting::get('writer_enabled');
    }

    private static function useModel(): bool
    {
        return (bool) Setting::get('writer_use_ai') && Setting::get('ai_mode') === 'llm' && LlmClient::isConfigured();
    }

    /**
     * @param  array{name: string, variant: string, category: string, brand: string, family: array<string, mixed>}  $facts
     */
    private static function composeProduct(array $facts, int $seed): string
    {
        $random = new Randomizer(new Mt19937($seed));
        $tone = self::tone();
        $family = $facts['family'];

        $pick = fn (array $bank): string => $bank[$random->getInt(0, count($bank) - 1)];
        $fill = fn (string $text): string => self::fill($text, [
            'name' => $facts['name'], 'brand' => $facts['brand'], 'brand_part' => $facts['brand'] !== '' ? ' de '.$facts['brand'] : '',
            'category' => mb_strtolower($facts['category'] ?: 'entretien et maison'), 'topic' => $family['topic'], 'object' => $family['object'],
        ]);

        $benefits = array_slice($random->shuffleArray([...$family['benefits'], ...array_slice(config('content.generic.benefits'), 0, 2)]), 0, 4);
        $tips = array_slice($random->shuffleArray($family['tips']), 0, 2);
        $points = array_values(array_filter(array_map(fn (array $row): string => trim((string) ($row['text'] ?? '')), (array) Setting::get('writer_selling_points'))));

        if ($points !== []) {
            $benefits[$random->getInt(0, 3)] = Setting::interpolate($points[$random->getInt(0, count($points) - 1)]);
        }

        $closing = trim((string) Setting::get('writer_closing')) !== ''
            ? Setting::interpolate((string) Setting::get('writer_closing'))
            : Setting::interpolate($pick(config("content.product.closing.{$tone}")));

        $html = '<p>'.e($fill($pick(config("content.product.intro.{$tone}")))).'</p>';
        $html .= '<h3>'.e($pick(config('content.product.benefits_title'))).'</h3><ul>'.implode('', array_map(fn (string $item): string => '<li>'.e($item).'</li>', $benefits)).'</ul>';
        $html .= '<h3>'.e($pick(config('content.product.tips_title'))).'</h3><p>'.e(implode(' ', $tips)).'</p>';
        $html .= '<p><strong>'.e($closing).'</strong></p>';

        return $html;
    }

    /**
     * @param  array{title: string, tag: string}  $facts
     * @return array{excerpt: string, html: string}
     */
    private static function composeArticle(array $facts, int $seed): array
    {
        $random = new Randomizer(new Mt19937($seed));
        $tone = self::tone();
        $family = self::family($facts['title'].' '.$facts['tag']);
        $tokens = ['title' => $facts['title'], 'topic' => $family['topic'], 'object' => $family['object']];
        $pick = fn (array $bank): string => $bank[$random->getInt(0, count($bank) - 1)];
        $fill = fn (string $text): string => self::fill($text, $tokens);

        $why = array_slice($random->shuffleArray(config('content.article.why')), 0, 2);
        $tips = array_slice($random->shuffleArray([...$family['tips'], ...config('content.generic.tips')]), 0, 5);
        $mistakes = array_slice($random->shuffleArray(config('content.article.mistakes')), 0, 4);
        $shop = (string) (parse_url(route('shop'), PHP_URL_PATH) ?: '/shop');
        $link = '<a href="'.e($shop).'">découvrez nos produits</a>';

        $html = '<p>'.e($fill($pick(config("content.article.intro.{$tone}")))).'</p>';
        $html .= '<h2>'.e($pick(config('content.article.why_title'))).'</h2>'.implode('', array_map(fn (string $text): string => '<p>'.e($fill($text)).'</p>', $why));
        $html .= '<h2>'.e($pick(config('content.article.tips_title'))).'</h2><ol>'.implode('', array_map(fn (string $item): string => '<li>'.e($item).'</li>', $tips)).'</ol>';
        $html .= '<h2>'.e($pick(config('content.article.mistakes_title'))).'</h2><ul>'.implode('', array_map(fn (string $item): string => '<li>'.e($item).'</li>', $mistakes)).'</ul>';

        [$before, $after] = array_pad(explode('{link}', Setting::interpolate($fill($pick(config("content.article.closing.{$tone}")))), 2), 2, '');
        $html .= '<p>'.e($before).$link.e($after).'</p>';

        return [
            'excerpt' => Str::limit(Setting::interpolate($fill($pick(config('content.article.excerpt')))), 240, '…'),
            'html' => $html,
        ];
    }

    /**
     * @param  array{name: string, variant?: ?string, category?: ?string, brand?: ?string}  $input
     * @return array{name: string, variant: string, category: string, brand: string, family: array<string, mixed>}
     */
    private static function productFacts(array $input): array
    {
        $name = trim($input['name']);
        $variant = trim((string) ($input['variant'] ?? ''));
        $category = trim((string) ($input['category'] ?? ''));

        return [
            'name' => $variant !== '' && ! str_contains(mb_strtolower($name), mb_strtolower($variant)) ? "{$name} ({$variant})" : $name,
            'variant' => $variant,
            'category' => $category,
            'brand' => trim((string) ($input['brand'] ?? '')),
            'family' => self::family($name.' '.$category),
        ];
    }

    /**
     * The family of products (laundry, dishes…) whose keywords appear most often in the text.
     *
     * @return array<string, mixed>
     */
    private static function family(string $text): array
    {
        $text = Str::ascii(mb_strtolower($text));
        $best = null;
        $bestScore = 0;

        foreach ((array) config('content.families') as $family) {
            $score = 0;

            foreach ($family['match'] as $keyword) {
                if (str_contains($text, Str::ascii($keyword))) {
                    $score++;
                }
            }

            if ($score > $bestScore) {
                [$best, $bestScore] = [$family, $score];
            }
        }

        return $best ?? config('content.generic');
    }

    /**
     * @param  array<string, string>  $tokens
     */
    private static function fill(string $text, array $tokens): string
    {
        $tokens += ['shop_name' => (string) Setting::get('shop_name')];

        return preg_replace_callback('/\{([a-z_]+)\}/', fn (array $match): string => $tokens[$match[1]] ?? $match[0], $text) ?? $text;
    }

    private static function tone(): string
    {
        return Setting::get('writer_tone') === 'professional' ? 'professional' : 'friendly';
    }

    private static function seed(string $name, int $variation, int $attempt): int
    {
        return crc32(Str::lower(trim($name)).'|'.$variation.'|'.$attempt) & 0x7FFFFFFF;
    }

    /**
     * @param  list<string|null>  $texts
     * @return list<string>
     */
    private static function usedTexts(array $texts): array
    {
        return array_values(array_unique(array_map(fn (?string $text): string => self::normalise((string) $text), $texts)));
    }

    private static function normalise(string $html): string
    {
        return Str::lower(preg_replace('/\s+/u', ' ', RichText::plain($html)) ?? '');
    }

    /**
     * @param  array{name: string, variant: string, category: string, brand: string, family: array<string, mixed>}  $facts
     */
    private static function productPrompt(array $facts): string
    {
        return "Rédige la fiche produit d’une boutique en ligne de déstockage.\n"
            ."Produit : {$facts['name']}\n".($facts['brand'] !== '' ? "Marque : {$facts['brand']}\n" : '').($facts['category'] !== '' ? "Catégorie : {$facts['category']}\n" : '')
            .'Ton : '.(self::tone() === 'professional' ? 'professionnel et sobre' : 'chaleureux et convivial').".\n"
            ."Format : HTML uniquement, avec les balises p, h3, ul, li et strong. Un paragraphe d’introduction, une liste de 4 points forts, un court paragraphe de conseils d’utilisation, une phrase d’invitation à commander.\n"
            .'Longueur : 110 à 170 mots. N’invente aucune caractéristique technique, composition, certification ni chiffre. Réponds uniquement avec le HTML.';
    }

    /**
     * @param  array{title: string, tag: string}  $facts
     */
    private static function articlePrompt(array $facts): string
    {
        return "Rédige un article de blog pour une boutique en ligne de produits d’entretien et de bien-être.\n"
            ."Titre : {$facts['title']}\n".($facts['tag'] !== '' ? "Rubrique : {$facts['tag']}\n" : '')
            .'Ton : '.(self::tone() === 'professional' ? 'professionnel et sobre' : 'chaleureux et convivial').".\n"
            ."Format de réponse : <excerpt>résumé de 1 à 2 phrases</excerpt><body>HTML de l’article</body>. Le HTML n’utilise que les balises p, h2, h3, ul, ol, li, strong et em.\n"
            .'Structure : introduction, 3 sections avec intertitres h2 (dont une liste de conseils), conclusion. 350 à 550 mots. Conseils prudents et généraux, sans affirmation médicale ni chiffre inventé.';
    }

    private static function askModel(string $prompt, int $maxTokens, int $variation): string
    {
        $system = 'Tu es rédacteur e-commerce francophone pour la boutique « '.Setting::get('shop_name').' ». Tu écris toujours à la troisième personne, en français correct, sans emoji.'
            .($variation > 0 ? " Propose une rédaction différente des précédentes (variante n°{$variation})." : '');

        $reply = LlmClient::complete($system, [['role' => 'user', 'content' => $prompt]], $maxTokens, 0.8);

        return str_contains($reply, '<excerpt>') ? $reply : RichText::sanitize(preg_replace('/^```(?:html)?|```$/m', '', $reply) ?? $reply);
    }
}
