<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\PageType;
use App\Support\Content\DescriptionWriter;
use App\Support\RichText;
use App\Support\Spreadsheet\ProductSpreadsheet;
use App\Support\Spreadsheet\XlsxWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RichTextAndWriterTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_sanitizer_keeps_formatting_and_removes_everything_dangerous(): void
    {
        $dirty = '<h1 onclick="x()">Titre</h1><p style="text-align:center;color:red">Bonjour <strong>monde</strong><script>alert(1)</script></p>'
            .'<a href="javascript:alert(1)">piégé</a> <a href="https://exemple.test/page" onclick="x()">ok</a>'
            .'<img src="data:image/png;base64,AAAA" onerror="x()"><img src="/storage/media/a.webp" alt="photo">'
            .'<iframe src="https://evil.test"></iframe><ul><li>un</li><li>deux</li></ul><div>bloc</div>';

        $clean = RichText::sanitize($dirty);

        $this->assertStringContainsString('<h2>Titre</h2>', $clean);
        $this->assertStringContainsString('<p class="ta-center">Bonjour <strong>monde</strong></p>', $clean);
        $this->assertStringContainsString('<a href="https://exemple.test/page" rel="noopener noreferrer" target="_blank">ok</a>', $clean);
        $this->assertStringContainsString('<img src="/storage/media/a.webp" alt="photo" loading="lazy">', $clean);
        $this->assertStringContainsString('<ul><li>un</li><li>deux</li></ul>', $clean);
        $this->assertStringContainsString('<p>bloc</p>', $clean);

        foreach (['script', 'alert', 'javascript', 'onclick', 'onerror', 'iframe', 'data:image', 'style=', 'color:red'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $clean);
        }

        $this->assertSame('', RichText::sanitize('<script>alert(1)</script>'));
    }

    public function test_accents_survive_the_sanitizer(): void
    {
        $this->assertSame('<p>Crème brûlée — « déjà »</p>', RichText::sanitize('<p>Crème brûlée — « déjà »</p>'));
    }

    public function test_legacy_plain_text_and_markdown_still_render_and_can_be_edited(): void
    {
        $this->assertSame("<p>Ligne un<br>\nligne deux</p><p>Autre paragraphe</p>", RichText::toHtml("Ligne un\nligne deux\n\nAutre paragraphe"));
        $this->assertStringNotContainsString('<script', (string) RichText::render('<b>x</b> ok'.'<script>1</script>'));
        $this->assertStringContainsString('&lt;', (string) RichText::render('1 < 2 et 3 > 2'));

        $page = Page::factory()->create(['type' => PageType::Page, 'body' => "## Livraison\n\nNous livrons **vite**.\n\n- Abidjan\n- Bouaké"]);

        $html = (string) $page->fresh()->renderedBody();
        $this->assertStringContainsString('<h2>Livraison</h2>', $html);
        $this->assertStringContainsString('<strong>vite</strong>', $html);
        $this->assertStringContainsString('<li>Abidjan</li>', $html);
        $this->assertStringContainsString('<h2>Livraison</h2>', $page->editorBody(), 'Markdown is shown formatted in the editor');
    }

    public function test_plain_text_extraction_for_search_and_spreadsheets(): void
    {
        $this->assertSame("Titre\nUn deux\nPoint A\nPoint B", RichText::plain('<h2>Titre</h2><p>Un <strong>deux</strong></p><ul><li>Point A</li><li>Point B</li></ul>'));
        $this->assertSame('texte brut', RichText::plain('texte brut'));
    }

    public function test_models_store_cleaned_html_and_leave_plain_text_alone(): void
    {
        $product = Product::factory()->create(['description' => '<p>Bon <b>produit</b><script>x</script></p>']);
        $this->assertSame('<p>Bon <strong>produit</strong></p>', $product->fresh()->description);

        $plain = Product::factory()->create(['description' => 'Simple texte']);
        $this->assertSame('Simple texte', $plain->fresh()->description);

        $this->assertNull(Product::factory()->create(['description' => '<p><br></p>'])->fresh()->description);
    }

    public function test_the_product_form_saves_formatted_text_and_the_storefront_shows_it_safely(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id, 'description' => null]);

        $this->actingAs($admin)->get(route('admin.products.edit', $product))->assertOk()->assertSee('data-rich', false)->assertSee('admin-editor.js', false);

        $payload = array_merge($product->only(['sku', 'name', 'variant', 'category_id', 'price', 'low_stock_threshold', 'kind', 'c1', 'c2']), [
            'description' => '<h3>Atouts</h3><p>Très <em>efficace</em></p><script>alert(1)</script>', 'is_active' => 1,
        ]);
        $this->put(route('admin.products.update', $product), $payload)->assertSessionHasNoErrors();

        $this->assertSame('<h3>Atouts</h3><p>Très <em>efficace</em></p>', $product->fresh()->description);

        $this->get(route('product', $product->fresh()->slug))->assertOk()->assertSee('<h3>Atouts</h3>', false)->assertSee('<em>efficace</em>', false)->assertDontSee('alert(1)', false);
    }

    public function test_an_article_is_saved_and_published_with_its_formatting(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route('admin.pages.store'), [
            'type' => 'article', 'title' => 'Bien laver son linge', 'tag' => 'Linge', 'is_published' => 1, 'published_at' => now()->subMinute()->format('Y-m-d\TH:i'),
            'body' => '<h2>Les bases</h2><p class="ta-center">Texte <u>souligné</u></p><p onmouseover="x()">Fin</p>',
        ])->assertSessionHasNoErrors();

        $page = Page::query()->where('title', 'Bien laver son linge')->firstOrFail();
        $this->assertStringNotContainsString('onmouseover', (string) $page->body);

        $this->get(route('blog.show', $page->slug))->assertOk()->assertSee('<h2>Les bases</h2>', false)->assertSee('class="ta-center"', false)->assertSee('<u>souligné</u>', false);
    }

    public function test_an_unchanged_spreadsheet_description_keeps_the_formatting(): void
    {
        $product = Product::factory()->create(['sku' => 'MK-9', 'description' => '<h3>Titre</h3><p>Corps du texte</p>']);

        $row = array_fill(0, count(ProductSpreadsheet::PRODUCT_COLUMNS), null);
        $keys = array_keys(ProductSpreadsheet::PRODUCT_COLUMNS);
        $set = function (string $key, mixed $value) use (&$row, $keys): void {
            $row[array_search($key, $keys, true)] = $value;
        };
        $set('sku', 'MK-9');
        $set('name', $product->name);
        $set('variant', $product->variant);
        $set('category', Category::query()->find($product->category_id)?->name ?? Category::factory()->create()->name);
        $set('price', 5);
        $set('description', RichText::plain($product->description));

        $path = XlsxWriter::build(['Produits' => ['headers' => array_values(ProductSpreadsheet::PRODUCT_COLUMNS), 'rows' => [$row]], 'Variantes' => ['headers' => array_values(ProductSpreadsheet::VARIANT_COLUMNS), 'rows' => []]]);
        ProductSpreadsheet::apply(ProductSpreadsheet::parse($path));
        @unlink($path);

        $this->assertSame('<h3>Titre</h3><p>Corps du texte</p>', $product->fresh()->description);
    }

    public function test_product_descriptions_are_generated_from_the_name_and_are_unique(): void
    {
        $category = Category::factory()->create(['name' => 'Lessives & linge']);
        $brand = Brand::factory()->create(['name' => 'Omo']);
        $input = ['name' => 'Lessive Omo Fraîcheur', 'variant' => '3 kg', 'category' => $category->name, 'brand' => $brand->name];

        $first = DescriptionWriter::product($input);
        $again = DescriptionWriter::product($input);
        $other = DescriptionWriter::product(['name' => 'Savon de Marseille', 'category' => 'Hygiène & désinfection'] + $input);

        $this->assertSame('template', $first['source']);
        $this->assertSame($first['html'], $again['html'], 'same name, same text');
        $this->assertNotSame($first['html'], $other['html']);
        $this->assertStringContainsString('Lessive Omo Fraîcheur', $first['html']);
        $this->assertStringContainsString('<ul><li>', $first['html']);
        $this->assertSame($first['html'], RichText::sanitize($first['html']), 'generated HTML is already clean');
        $this->assertNotSame($first['html'], DescriptionWriter::product($input, variation: 1)['html'], 'the rewrite button gives another text');

        Product::factory()->create(['description' => $first['html']]);
        $unique = DescriptionWriter::product($input);
        $this->assertNotSame(RichText::plain($first['html']), RichText::plain($unique['html']), 'a text already used by another product is never returned');
    }

    public function test_generated_texts_follow_the_product_family_and_the_settings(): void
    {
        Setting::putMany(['writer_closing' => 'Livraison offerte dès {free_shipping_threshold}.', 'writer_selling_points' => [['text' => 'Argument maison unique']]]);

        $laundry = DescriptionWriter::product(['name' => 'Lessive liquide', 'category' => 'Lessives & linge']);
        $this->assertMatchesRegularExpression('/linge/i', $laundry['html']);
        $this->assertStringContainsString('Argument maison unique', $laundry['html']);
        $this->assertStringContainsString('Livraison offerte dès', $laundry['html']);
        $this->assertStringNotContainsString('{', $laundry['html']);
    }

    public function test_articles_get_an_excerpt_and_a_structured_body(): void
    {
        $draft = DescriptionWriter::article('Comment enlever les taches sur le linge', 'Linge');

        $this->assertSame('template', $draft['source']);
        $this->assertNotSame('', $draft['excerpt']);
        $this->assertLessThanOrEqual(241, mb_strlen($draft['excerpt']));
        $this->assertSame(3, substr_count($draft['html'], '<h2>'));
        $this->assertStringContainsString('<ol><li>', $draft['html']);
        $this->assertStringContainsString('<a href="', $draft['html']);
        $this->assertStringContainsString('Comment enlever les taches sur le linge', $draft['html']);
        $this->assertStringNotContainsString('{', $draft['html']);

        Page::factory()->create(['type' => PageType::Article, 'body' => $draft['html']]);
        $this->assertNotSame($draft['html'], DescriptionWriter::article('Comment enlever les taches sur le linge', 'Linge')['html']);
    }

    public function test_the_generate_endpoint_for_products_and_articles(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $category = Category::factory()->create(['name' => 'Cuisine & vaisselle']);
        $brand = Brand::factory()->create(['name' => 'Sunlight']);

        $product = $this->postJson(route('admin.content.generate'), ['type' => 'product', 'name' => 'Liquide vaisselle citron', 'variant' => '750 ml', 'category_id' => $category->id, 'brand_id' => $brand->id])->assertOk();
        $this->assertStringContainsString('Liquide vaisselle citron', $product->json('html'));
        $this->assertStringContainsString('Sunlight', $product->json('html'));

        $article = $this->postJson(route('admin.content.generate'), ['type' => 'article', 'name' => 'Astuces pour une vaisselle brillante', 'tag' => 'Cuisine'])->assertOk();
        $this->assertNotEmpty($article->json('excerpt'));
        $this->assertStringContainsString('<h2>', $article->json('html'));

        $this->postJson(route('admin.content.generate'), ['type' => 'product', 'name' => ''])->assertJsonValidationErrors('name');
        $this->postJson(route('admin.content.generate'), ['type' => 'video', 'name' => 'x y'])->assertJsonValidationErrors('type');
    }

    public function test_the_generate_endpoint_can_be_switched_off_and_is_reserved_to_the_back_office(): void
    {
        $this->postJson(route('admin.content.generate'), ['type' => 'product', 'name' => 'Savon'])->assertUnauthorized();

        $this->actingAs(User::factory()->manager()->create());
        $this->postJson(route('admin.content.generate'), ['type' => 'product', 'name' => 'Savon'])->assertOk();

        Setting::putMany(['writer_enabled' => false]);
        $this->postJson(route('admin.content.generate'), ['type' => 'product', 'name' => 'Savon'])->assertForbidden();
        $this->get(route('admin.products.create'))->assertOk()->assertDontSee('data-writer-run', false);
    }

    public function test_the_forms_expose_the_writer_with_the_auto_setting(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('admin.products.create'))->assertOk()->assertSee('data-writer-run', false)->assertSee('admin-writer.js', false)->assertSee('&quot;auto&quot;:true', false);

        Setting::putMany(['writer_auto' => false]);
        $this->get(route('admin.products.create'))->assertSee('&quot;auto&quot;:false', false);
        $this->get(route('admin.pages.create', ['type' => 'article']))->assertOk()->assertSee('data-writer-run', false)->assertSee('data-rich', false);
        $this->get(route('admin.pages.create', ['type' => 'page']))->assertOk()->assertDontSee('data-writer-run', false)->assertSee('data-rich', false);
        $this->get(route('admin.settings.edit', 'writer'))->assertOk()->assertSee('Rédaction automatique');
    }

    public function test_the_ai_model_writes_the_text_when_configured_and_the_template_takes_over_on_failure(): void
    {
        Setting::putMany(['ai_mode' => 'llm', 'ai_api_key' => 'sk-test', 'writer_use_ai' => true]);
        Http::fake(['api.anthropic.com/*' => Http::sequence()
            ->push(['content' => [['type' => 'text', 'text' => '<p>Texte IA <strong>unique</strong></p><script>alert(1)</script>']]])
            ->push(['content' => [['type' => 'text', 'text' => '<excerpt>Résumé IA</excerpt><body><h2>Partie</h2><p>Texte</p><iframe src="x"></iframe></body>']]])
            ->push(['error' => ['message' => 'down']], 500)]);

        $ai = DescriptionWriter::product(['name' => 'Savon liquide']);
        $this->assertSame('ai', $ai['source']);
        $this->assertSame('<p>Texte IA <strong>unique</strong></p>', $ai['html']);

        $article = DescriptionWriter::article('Savon et peau');
        $this->assertSame('ai', $article['source']);
        $this->assertSame('Résumé IA', $article['excerpt']);
        $this->assertSame('<h2>Partie</h2><p>Texte</p>', $article['html']);

        $this->assertSame('template', DescriptionWriter::product(['name' => 'Savon liquide'])['source']);

        Setting::putMany(['writer_use_ai' => false]);
        Http::fake();
        $this->assertSame('template', DescriptionWriter::product(['name' => 'Savon liquide'])['source']);
        Http::assertNothingSent();
    }
}
