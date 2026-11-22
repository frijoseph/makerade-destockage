<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\Spreadsheet\ProductSpreadsheet;
use App\Support\Spreadsheet\XlsxReader;
use App\Support\Spreadsheet\XlsxWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductSpreadsheetTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $cleanup = [];

    protected function tearDown(): void
    {
        foreach ($this->cleanup as $path) {
            @unlink($path);
        }

        parent::tearDown();
    }

    /**
     * @param  list<list<scalar|null>>  $products
     * @param  list<list<scalar|null>>  $variants
     */
    private function workbook(array $products, array $variants = [], ?array $productHeaders = null): string
    {
        $path = XlsxWriter::build([
            'Produits' => ['headers' => $productHeaders ?? array_values(ProductSpreadsheet::PRODUCT_COLUMNS), 'rows' => $products],
            'Variantes' => ['headers' => array_values(ProductSpreadsheet::VARIANT_COLUMNS), 'rows' => $variants],
        ]);

        return $this->cleanup[] = $path;
    }

    public function test_writer_and_reader_round_trip_text_numbers_and_special_characters(): void
    {
        $path = XlsxWriter::build(['Test' => ['headers' => ['A', 'B', 'C'], 'rows' => [['=SUM(1+1)', 12.5, 'Crème & <b>"quote"</b>'], [null, 3, 'oui']]]]);
        $this->cleanup[] = $path;

        $sheets = XlsxReader::read($path);

        $this->assertSame(['A', 'B', 'C'], $sheets['Test'][0]);
        $this->assertSame(['=SUM(1+1)', '12.5', 'Crème & <b>"quote"</b>'], $sheets['Test'][1]);
        $this->assertSame(['', '3', 'oui'], $sheets['Test'][2]);
        $this->assertStringContainsString('t="inlineStr"', (string) file_get_contents('zip://'.$path.'#xl/worksheets/sheet1.xml'), 'formulas are stored as text, never evaluated');
    }

    public function test_reader_rejects_files_that_are_not_workbooks(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'mkx');
        file_put_contents($path, 'not a zip');
        $this->cleanup[] = $path;

        $this->expectException(\RuntimeException::class);
        XlsxReader::read($path);
    }

    public function test_import_creates_and_updates_products_with_variants(): void
    {
        $category = Category::factory()->create(['name' => 'Entretien']);
        $existing = Product::factory()->create(['sku' => 'MK-1', 'name' => 'Ancien nom', 'price' => 10, 'stock' => 5, 'category_id' => $category->id]);

        $path = $this->workbook([
            ['MK-1', 'Nouveau nom', 'Flacon 1 L', 'entretien', null, '1 500,50', null, null, 20, 5, 'Une description', 'oui', 'non', 'non', ''],
            ['MK-2', 'Lessive', 'Lessive', 'Bien-être', 'Marque Neuve', 3000, 4000, 2000, null, null, '', 'oui', 'oui', 'oui', 'Taille | Parfum'],
        ], [
            ['MK-2', '1 L', 'Citron', null, 'MK-2-1L', null, null, 25, 'oui'],
            ['mk-2', '5 L', 'Citron', null, 'MK-2-5L', 12000, 14000, 8, 'non'],
        ]);

        $report = ProductSpreadsheet::parse($path);
        $this->assertSame([], $report['errors']);
        $this->assertSame(['create' => 1, 'update' => 1, 'variants' => 2], $report['counts']);

        $summary = ProductSpreadsheet::apply($report, User::factory()->admin()->create());
        $this->assertSame(['created' => 1, 'updated' => 1, 'variants' => 2, 'categories' => 1, 'brands' => 1], $summary);

        $existing->refresh();
        $this->assertSame('Nouveau nom', $existing->name);
        $this->assertEquals(1500.5, $existing->price);
        $this->assertSame(20, $existing->stock);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $existing->id, 'type' => 'adjustment', 'quantity' => 15, 'reason' => 'Import Excel']);

        $created = Product::query()->where('sku', 'MK-2')->with('variants')->firstOrFail();
        $this->assertSame(['Taille', 'Parfum'], $created->option_names);
        $this->assertTrue($created->is_hot);
        $this->assertSame('Marque Neuve', $created->brand->name);
        $this->assertCount(2, $created->variants);
        $this->assertSame(33, $created->stock);
        $this->assertSame('5 L / Citron', $created->variants[1]->name);
        $this->assertFalse($created->variants[1]->is_active);
        $this->assertEquals(12000, $created->variants[1]->price);
    }

    public function test_reimporting_variants_keeps_the_other_existing_variants(): void
    {
        $product = Product::factory()->create(['sku' => 'MK-5']);
        $keep = ProductVariant::factory()->create(['product_id' => $product->id, 'name' => '2 L', 'option1' => '2 L', 'stock' => 4]);
        $change = ProductVariant::factory()->create(['product_id' => $product->id, 'name' => '1 L', 'option1' => '1 L', 'stock' => 1]);

        $report = ProductSpreadsheet::parse($this->workbook([], [['MK-5', '1 L', null, null, null, null, null, 9, 'oui']]));
        $this->assertSame([], $report['errors']);
        ProductSpreadsheet::apply($report);

        $this->assertSame(4, $keep->fresh()->stock);
        $this->assertSame(9, $change->fresh()->stock);
        $this->assertSame(13, $product->fresh()->stock);
    }

    public function test_invalid_rows_are_reported_with_their_line_numbers(): void
    {
        $report = ProductSpreadsheet::parse($this->workbook([
            ['MK-1', 'Bon', 'Format', 'Cat', null, 10, null, null, 1, null, '', 'oui', 'non', 'non', ''],
            ['MK-1', '', 'Format', 'Cat', null, 'abc', 5, null, -2, null, '', 'peut-être', 'non', 'non', ''],
        ], [['INCONNU', '', null, null, null, null, null, null, 'oui']]));

        $messages = collect($report['errors'])->map(fn (array $error): string => $error['sheet'].' L'.$error['row'].' '.$error['message'])->implode("\n");

        $this->assertStringContainsString('Produits L3', $messages);
        $this->assertStringContainsString('apparaît deux fois', $messages);
        $this->assertStringContainsString('Variantes L2', $messages);
        $this->assertStringContainsString('introuvable', $messages);
        $this->assertStringContainsString('au moins une option', $messages);
        $this->assertNotSame([], $report['errors']);
    }

    public function test_missing_required_columns_are_reported(): void
    {
        $report = ProductSpreadsheet::parse($this->workbook([['MK-1', 'Nom']], [], ['Référence (SKU)', 'Nom']));

        $this->assertStringContainsString('Colonne obligatoire absente', $report['errors'][0]['message']);
    }

    public function test_admin_can_export_download_the_template_and_import_through_the_ui(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['name' => 'Entretien']);
        Product::factory()->create(['sku' => 'MK-9', 'name' => 'Export me', 'category_id' => $category->id, 'brand_id' => Brand::factory()]);

        $export = $this->actingAs($admin)->get(route('admin.products.export'));
        $export->assertOk()->assertDownload();

        $this->actingAs($admin)->get(route('admin.products.template'))->assertOk()->assertDownload('modele-import-produits.xlsx');
        $this->get(route('admin.products.import'))->assertOk()->assertSee('Télécharger le modèle');

        $file = new UploadedFile($this->workbook([['MK-NEW', 'Importé', 'Format', 'Entretien', null, 500, null, null, 12, null, '', 'oui', 'non', 'non', '']]), 'catalogue.xlsx', null, null, true);
        $preview = $this->post(route('admin.products.import.preview'), ['file' => $file])->assertOk()->assertSee('Confirmer l’import')->assertSee('MK-NEW');

        preg_match('/name="token" value="([a-z0-9]{40})"/', $preview->getContent(), $match);
        $this->assertNotEmpty($match[1] ?? null);

        $this->post(route('admin.products.import.store'), ['token' => $match[1]])->assertRedirect(route('admin.products.index'));
        $this->assertSame(12, Product::query()->where('sku', 'MK-NEW')->value('stock'));
        $this->post(route('admin.products.import.store'), ['token' => $match[1]])->assertRedirect(route('admin.products.import'));
    }

    public function test_import_rejects_non_excel_uploads_and_blocks_files_with_errors(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route('admin.products.import.preview'), ['file' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')])->assertSessionHasErrors('file');
        $this->post(route('admin.products.import.preview'), ['file' => UploadedFile::fake()->createWithContent('fake.xlsx', 'not really a workbook')])->assertSessionHasErrors('file');

        $bad = new UploadedFile($this->workbook([['', '', '', '', null, 'x', null, null, null, null, '', '', '', '', '']]), 'bad.xlsx', null, null, true);
        $preview = $this->post(route('admin.products.import.preview'), ['file' => $bad])->assertOk()->assertSee('À corriger dans le fichier');
        preg_match('/name="token" value="([a-z0-9]{40})"/', $preview->getContent(), $match);

        $this->post(route('admin.products.import.store'), ['token' => $match[1]])->assertSessionHasErrors('file');
        $this->assertSame(0, Product::query()->count());
        $this->post(route('admin.products.import.store'), ['token' => '../../etc/passwd'])->assertSessionHasErrors('token');
    }

    public function test_customers_cannot_reach_the_catalogue_import(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.products.export'))->assertRedirect(route('admin.login'));
    }
}
