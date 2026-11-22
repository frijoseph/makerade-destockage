<?php

namespace App\Support\Spreadsheet;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use App\StockMovementType;
use App\Support\Inventory;
use App\Support\ProductVariants;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Excel export / import of the catalogue: a « Produits » sheet and a « Variantes » sheet.
 * The file is the source of truth for the columns it contains; columns left out are never touched.
 */
class ProductSpreadsheet
{
    /** @var array<string, string> */
    public const PRODUCT_COLUMNS = [
        'sku' => 'Référence (SKU)',
        'name' => 'Nom',
        'format' => 'Format',
        'category' => 'Catégorie',
        'brand' => 'Marque',
        'price' => 'Prix de vente',
        'old_price' => 'Prix barré',
        'cost_price' => 'Prix d’achat',
        'stock' => 'Stock',
        'low_stock_threshold' => 'Seuil d’alerte',
        'description' => 'Description',
        'visible' => 'Visible (oui/non)',
        'is_new' => 'Nouveauté (oui/non)',
        'is_hot' => 'Promo choc (oui/non)',
        'options' => 'Noms des options',
    ];

    /** @var array<string, string> */
    public const VARIANT_COLUMNS = [
        'sku' => 'Référence produit (SKU)',
        'option1' => 'Option 1',
        'option2' => 'Option 2',
        'option3' => 'Option 3',
        'variant_sku' => 'Référence variante',
        'price' => 'Prix',
        'old_price' => 'Prix barré',
        'stock' => 'Stock',
        'visible' => 'Visible (oui/non)',
    ];

    /** Extra header spellings accepted on import. */
    private const ALIASES = [
        'products' => [
            'sku' => ['sku', 'reference', 'ref'],
            'name' => ['nom', 'name', 'produit', 'designation'],
            'format' => ['format', 'variant', 'conditionnement'],
            'category' => ['categorie', 'category'],
            'brand' => ['marque', 'brand'],
            'price' => ['prix', 'prix de vente', 'price'],
            'old_price' => ['prix barre', 'ancien prix', 'old price'],
            'cost_price' => ['prix d achat', 'prix achat', 'cout'],
            'stock' => ['stock', 'quantite'],
            'low_stock_threshold' => ['seuil d alerte', 'seuil', 'seuil alerte'],
            'description' => ['description'],
            'visible' => ['visible', 'actif', 'visible oui non', 'en ligne'],
            'is_new' => ['nouveaute', 'nouveaute oui non', 'nouveau'],
            'is_hot' => ['promo choc', 'promo choc oui non'],
            'options' => ['noms des options', 'options'],
        ],
        'variants' => [
            'sku' => ['reference produit sku', 'reference produit', 'sku produit', 'sku'],
            'option1' => ['option 1', 'option1'],
            'option2' => ['option 2', 'option2'],
            'option3' => ['option 3', 'option3'],
            'variant_sku' => ['reference variante', 'sku variante'],
            'price' => ['prix'],
            'old_price' => ['prix barre'],
            'stock' => ['stock', 'quantite'],
            'visible' => ['visible', 'actif', 'visible oui non'],
        ],
    ];

    private const PRODUCTS_SHEET = 'Produits';

    private const VARIANTS_SHEET = 'Variantes';

    /**
     * @return string path of a temporary .xlsx file
     */
    public static function export(): string
    {
        $products = Product::query()->with(['category', 'brand', 'variants'])->orderBy('name')->get();

        $productRows = $products->map(fn (Product $product): array => [
            $product->sku, $product->name, $product->variant, $product->category?->name, $product->brand?->name,
            (float) $product->price, $product->old_price !== null ? (float) $product->old_price : null,
            $product->cost_price !== null ? (float) $product->cost_price : null,
            $product->stock, $product->low_stock_threshold, $product->description,
            $product->is_active ? 'oui' : 'non', $product->is_new ? 'oui' : 'non', $product->is_hot ? 'oui' : 'non',
            implode(' | ', (array) $product->option_names),
        ])->all();

        $variantRows = $products->flatMap(fn (Product $product) => $product->variants->map(fn (ProductVariant $variant): array => [
            $product->sku, $variant->option1, $variant->option2, $variant->option3, $variant->sku,
            $variant->price !== null ? (float) $variant->price : null, $variant->old_price !== null ? (float) $variant->old_price : null,
            $variant->stock, $variant->is_active ? 'oui' : 'non',
        ]))->all();

        return XlsxWriter::build(self::sheets($productRows, $variantRows), self::headerColor());
    }

    /**
     * Empty workbook with two example products and the instructions sheet.
     */
    public static function template(): string
    {
        $productRows = [
            ['EXEMPLE-001', 'Liquide vaisselle Citron', '750 ml', 'Entretien ménager', 'Marque Exemple', 1500, 1800, 900, 40, 10, 'Dégraisse sans effort.', 'oui', 'oui', 'non', ''],
            ['EXEMPLE-002', 'Lessive liquide', 'Lessive', 'Entretien ménager', 'Marque Exemple', 3000, null, 2000, null, 10, '', 'oui', 'non', 'oui', 'Taille | Parfum'],
        ];
        $variantRows = [
            ['EXEMPLE-002', '1 L', 'Citron', null, 'EXEMPLE-002-1L-C', null, null, 25, 'oui'],
            ['EXEMPLE-002', '5 L', 'Citron', null, 'EXEMPLE-002-5L-C', 12000, 14000, 8, 'oui'],
        ];

        return XlsxWriter::build(self::sheets($productRows, $variantRows), self::headerColor());
    }

    /**
     * Reads and checks a workbook without touching the database.
     *
     * @return array{products: list<array<string, mixed>>, errors: list<array{sheet: string, row: int, message: string}>, counts: array{create: int, update: int, variants: int}}
     */
    public static function parse(string $path): array
    {
        $sheets = XlsxReader::read($path);
        $errors = [];

        $productSheet = self::findSheet($sheets, self::PRODUCTS_SHEET);
        if ($productSheet === null) {
            return ['products' => [], 'errors' => [['sheet' => '', 'row' => 0, 'message' => 'La feuille « Produits » est introuvable. Partez du modèle fourni.']], 'counts' => ['create' => 0, 'update' => 0, 'variants' => 0]];
        }

        [$columns, $rows] = self::mapColumns($productSheet, 'products');
        foreach (['sku', 'name', 'format', 'category', 'price'] as $required) {
            if (! isset($columns[$required])) {
                $errors[] = ['sheet' => self::PRODUCTS_SHEET, 'row' => 1, 'message' => 'Colonne obligatoire absente : « '.self::PRODUCT_COLUMNS[$required].' ».'];
            }
        }

        $existing = Product::query()->with('variants')->get()->keyBy(fn (Product $product): string => mb_strtolower($product->sku));
        $products = [];
        $seen = [];

        foreach ($rows as $number => $row) {
            $cells = self::cells($row, $columns);
            if (self::isBlank($cells)) {
                continue;
            }

            $sku = (string) ($cells['sku'] ?? '');
            $lower = mb_strtolower($sku);
            $rowErrors = self::validateProduct($cells, $columns);

            if ($sku !== '' && isset($seen[$lower])) {
                $rowErrors[] = "La référence « {$sku} » apparaît deux fois (ligne {$seen[$lower]}).";
            }
            $seen[$lower] ??= $number;

            foreach ($rowErrors as $message) {
                $errors[] = ['sheet' => self::PRODUCTS_SHEET, 'row' => $number, 'message' => $message];
            }

            $products[$lower] = [
                'row' => $number,
                'action' => $existing->has($lower) ? 'update' : 'create',
                'cells' => $cells,
                'columns' => array_keys($columns),
                'variants' => [],
                'valid' => $rowErrors === [],
            ];
        }

        $variantCount = 0;
        $variantSheet = self::findSheet($sheets, self::VARIANTS_SHEET);
        if ($variantSheet !== null) {
            [$vColumns, $vRows] = self::mapColumns($variantSheet, 'variants');
            $combos = [];
            $variantSkus = [];

            foreach ($vRows as $number => $row) {
                $cells = self::cells($row, $vColumns);
                if (self::isBlank($cells)) {
                    continue;
                }

                $sku = mb_strtolower((string) ($cells['sku'] ?? ''));
                $rowErrors = self::validateVariant($cells);

                if (! isset($products[$sku]) && ! $existing->has($sku)) {
                    $rowErrors[] = 'Produit « '.($cells['sku'] ?? '').' » introuvable (ni dans la feuille Produits, ni dans le catalogue).';
                } elseif (! isset($products[$sku])) {
                    $products[$sku] = ['row' => 0, 'action' => 'update', 'cells' => ['sku' => $existing[$sku]->sku], 'columns' => ['sku'], 'variants' => [], 'valid' => true];
                }

                $label = ProductVariant::labelFor([$cells['option1'] ?? null, $cells['option2'] ?? null, $cells['option3'] ?? null]);
                $key = $sku.'|'.mb_strtolower($label);
                if ($label !== '' && isset($combos[$key])) {
                    $rowErrors[] = "La variante « {$label} » apparaît deux fois pour ce produit.";
                }
                $combos[$key] = true;

                $variantSku = mb_strtolower((string) ($cells['variant_sku'] ?? ''));
                if ($variantSku !== '') {
                    $owner = ProductVariant::query()->where('sku', $cells['variant_sku'])->with('product')->first();
                    $ownerClash = $owner && mb_strtolower($owner->product->sku) !== $sku;
                    if ($ownerClash || isset($variantSkus[$variantSku])) {
                        $rowErrors[] = "La référence variante « {$cells['variant_sku']} » est déjà utilisée.";
                    }
                    $variantSkus[$variantSku] = true;
                }

                foreach ($rowErrors as $message) {
                    $errors[] = ['sheet' => self::VARIANTS_SHEET, 'row' => $number, 'message' => $message];
                }

                if (isset($products[$sku])) {
                    $products[$sku]['variants'][] = ['row' => $number, 'cells' => $cells, 'valid' => $rowErrors === []];
                    $variantCount++;
                }
            }
        }

        $products = array_values($products);

        return [
            'products' => $products,
            'errors' => $errors,
            'counts' => [
                'create' => count(array_filter($products, fn (array $product): bool => $product['action'] === 'create' && $product['row'] > 0)),
                'update' => count(array_filter($products, fn (array $product): bool => $product['action'] === 'update')),
                'variants' => $variantCount,
            ],
        ];
    }

    /**
     * Writes a parsed (and error-free) workbook to the database in a single transaction.
     *
     * @param  array{products: list<array<string, mixed>>}  $parsed
     * @return array{created: int, updated: int, variants: int, categories: int, brands: int}
     */
    public static function apply(array $parsed, ?User $user = null): array
    {
        return DB::transaction(function () use ($parsed, $user): array {
            $summary = ['created' => 0, 'updated' => 0, 'variants' => 0, 'categories' => 0, 'brands' => 0];

            foreach ($parsed['products'] as $entry) {
                $cells = $entry['cells'];
                $columns = $entry['columns'];
                $has = fn (string $key): bool => in_array($key, $columns, true);
                $product = Product::query()->with('variants')->whereRaw('LOWER(sku) = ?', [mb_strtolower((string) $cells['sku'])])->first();
                $isNew = $product === null;
                $variantsOnly = $entry['row'] === 0;

                if ($isNew && $variantsOnly) {
                    continue;
                }

                $attributes = [];

                if ($has('name')) {
                    $attributes['name'] = $cells['name'];
                }
                if ($has('format')) {
                    $attributes['variant'] = $cells['format'];
                }
                if ($has('category')) {
                    $attributes['category_id'] = self::category((string) $cells['category'], $summary)->id;
                }
                if ($has('brand')) {
                    $attributes['brand_id'] = ($cells['brand'] ?? '') !== '' ? self::brand((string) $cells['brand'], $summary)->id : null;
                }
                foreach (['price' => 'price', 'old_price' => 'old_price', 'cost_price' => 'cost_price'] as $key => $column) {
                    if ($has($key)) {
                        $attributes[$column] = self::number($cells[$key] ?? null);
                    }
                }
                if ($has('low_stock_threshold') && ($cells['low_stock_threshold'] ?? '') !== '') {
                    $attributes['low_stock_threshold'] = (int) self::number($cells['low_stock_threshold']);
                }
                if ($has('description')) {
                    $attributes['description'] = ($cells['description'] ?? '') !== '' ? $cells['description'] : null;
                }
                foreach (['visible' => 'is_active', 'is_new' => 'is_new', 'is_hot' => 'is_hot'] as $key => $column) {
                    if ($has($key) && ($cells[$key] ?? '') !== '') {
                        $attributes[$column] = self::boolean($cells[$key]);
                    }
                }
                if ($has('options')) {
                    $attributes['option_names'] = array_values(array_filter(array_map('trim', explode('|', (string) ($cells['options'] ?? '')))));
                }

                if ($isNew) {
                    $attributes += [
                        'sku' => $cells['sku'],
                        'slug' => self::uniqueSlug(($attributes['name'] ?? '').' '.($attributes['variant'] ?? '')),
                        'kind' => Product::KINDS[0],
                        'c1' => (string) Setting::get('theme_color'),
                        'c2' => '#ffffff',
                        'low_stock_threshold' => (int) Setting::get('default_low_stock_threshold'),
                        'is_active' => true,
                        'stock' => 0,
                    ];
                    $product = Product::query()->create($attributes);
                    $summary['created']++;
                } elseif (! $variantsOnly) {
                    $product->update($attributes);
                    $summary['updated']++;
                }

                $product->unsetRelation('variants');
                $hasVariantRows = $entry['variants'] !== [];

                if ($hasVariantRows) {
                    $rows = self::mergedVariantRows($product, $entry['variants']);
                    ProductVariants::sync($product, $rows, $user);
                    $summary['variants'] += count($entry['variants']);
                } elseif ($has('stock') && ($cells['stock'] ?? '') !== '' && ! $product->variants()->exists()) {
                    $target = (int) self::number($cells['stock']);
                    $isNew
                        ? ($target > 0 ? Inventory::move($product, StockMovementType::Restock, $target, 'Import Excel', user: $user) : null)
                        : Inventory::setTo($product, $target, 'Import Excel', $user);
                }
            }

            return $summary;
        });
    }

    /**
     * Existing variants first (untouched unless the file mentions them), then the new ones.
     *
     * @param  list<array<string, mixed>>  $fileRows
     * @return list<array<string, mixed>>
     */
    private static function mergedVariantRows(Product $product, array $fileRows): array
    {
        $rows = [];
        $index = [];

        foreach ($product->variants()->get() as $variant) {
            $rows[] = [
                'id' => $variant->id, 'option1' => $variant->option1, 'option2' => $variant->option2, 'option3' => $variant->option3,
                'sku' => $variant->sku, 'price' => $variant->price, 'old_price' => $variant->old_price, 'stock' => $variant->stock,
                'is_active' => $variant->is_active,
            ];
            $index[mb_strtolower($variant->name)] = array_key_last($rows);
        }

        foreach ($fileRows as $fileRow) {
            $cells = $fileRow['cells'];
            $row = [
                'option1' => $cells['option1'] ?? null, 'option2' => $cells['option2'] ?? null, 'option3' => $cells['option3'] ?? null,
                'sku' => $cells['variant_sku'] ?? null,
                'price' => self::number($cells['price'] ?? null), 'old_price' => self::number($cells['old_price'] ?? null),
                'stock' => ($cells['stock'] ?? '') !== '' ? (int) self::number($cells['stock']) : 0,
                'is_active' => ($cells['visible'] ?? '') !== '' ? self::boolean($cells['visible']) : true,
            ];
            $key = mb_strtolower(ProductVariant::labelFor([$row['option1'], $row['option2'], $row['option3']]));

            if (isset($index[$key])) {
                $merged = $rows[$index[$key]];
                $merged['sku'] = $row['sku'] ?: $merged['sku'];
                $merged['price'] = $row['price'];
                $merged['old_price'] = $row['old_price'];
                if (($cells['stock'] ?? '') !== '') {
                    $merged['stock'] = $row['stock'];
                }
                if (($cells['visible'] ?? '') !== '') {
                    $merged['is_active'] = $row['is_active'];
                }
                $rows[$index[$key]] = $merged;
            } else {
                $rows[] = $row;
                $index[$key] = array_key_last($rows);
            }
        }

        return $rows;
    }

    /**
     * @param  list<list<scalar|null>>  $productRows
     * @param  list<list<scalar|null>>  $variantRows
     * @return array<string, array{headers: list<string>, rows: list<list<scalar|null>>, widths?: list<int>}>
     */
    private static function sheets(array $productRows, array $variantRows): array
    {
        return [
            self::PRODUCTS_SHEET => ['headers' => array_values(self::PRODUCT_COLUMNS), 'rows' => $productRows, 'widths' => [18, 32, 20, 22, 20, 14, 12, 14, 10, 14, 40, 16, 18, 18, 22]],
            self::VARIANTS_SHEET => ['headers' => array_values(self::VARIANT_COLUMNS), 'rows' => $variantRows, 'widths' => [24, 16, 16, 16, 22, 12, 12, 10, 16]],
            'Aide' => ['headers' => ['Comment remplir ce fichier'], 'rows' => array_map(fn (string $line): array => [$line], self::help()), 'widths' => [120]],
        ];
    }

    /**
     * @return list<string>
     */
    private static function help(): array
    {
        return [
            'Feuille « Produits » : une ligne par produit. La Référence (SKU) identifie le produit : si elle existe déjà, le produit est mis à jour, sinon il est créé.',
            'Obligatoire : Référence (SKU), Nom, Format, Catégorie, Prix de vente. Les catégories et marques inconnues sont créées automatiquement.',
            'Prix : nombres sans symbole monétaire (1500 ou 1500,50). Le prix barré doit être supérieur au prix de vente.',
            'Visible, Nouveauté, Promo choc : oui ou non.',
            'Stock : pour un produit existant, le stock est ramené à la valeur indiquée (un mouvement « Ajustement » est enregistré). Laissez vide pour ne pas y toucher.',
            'Noms des options : ex. « Taille | Parfum » (3 maximum) quand le produit a des variantes.',
            'Feuille « Variantes » (facultative) : une ligne par variante, reliée au produit par sa Référence (SKU). Option 1 à 3 = valeurs (ex. 5 L, Citron). Les variantes déjà existantes non listées sont conservées.',
            'Pour un produit avec variantes, le stock se saisit par variante : la colonne Stock de la feuille Produits est ignorée.',
            'Les photos se gèrent depuis la fiche produit de l’administration.',
            'Ne modifiez pas les titres des colonnes ; vous pouvez en revanche changer leur ordre ou supprimer celles que vous ne souhaitez pas mettre à jour.',
        ];
    }

    private static function headerColor(): string
    {
        $color = (string) Setting::get('theme_color');

        return preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? strtoupper(substr($color, 1)) : '0F6A26';
    }

    /**
     * @param  array<string, list<list<string>>>  $sheets
     * @return list<list<string>>|null
     */
    private static function findSheet(array $sheets, string $name): ?array
    {
        foreach ($sheets as $title => $rows) {
            if (self::normalize($title) === self::normalize($name)) {
                return $rows;
            }
        }

        return null;
    }

    /**
     * @param  list<list<string>>  $sheet
     * @return array{0: array<string, int>, 1: array<int, list<string>>} column key => index, rows keyed by their Excel row number
     */
    private static function mapColumns(array $sheet, string $kind): array
    {
        $header = $sheet[0] ?? [];
        $lookup = [];
        foreach (self::ALIASES[$kind] as $key => $aliases) {
            foreach ($aliases as $alias) {
                $lookup[self::normalize($alias)] ??= $key;
            }
        }
        foreach (($kind === 'products' ? self::PRODUCT_COLUMNS : self::VARIANT_COLUMNS) as $key => $label) {
            $lookup[self::normalize($label)] = $key;
        }

        $columns = [];
        foreach ($header as $index => $title) {
            $key = $lookup[self::normalize($title)] ?? null;
            if ($key !== null && ! isset($columns[$key])) {
                $columns[$key] = $index;
            }
        }

        $rows = [];
        foreach (array_slice($sheet, 1, null, true) as $index => $row) {
            $rows[$index + 1] = $row;
        }

        return [$columns, $rows];
    }

    /**
     * @param  list<string>  $row
     * @param  array<string, int>  $columns
     * @return array<string, string>
     */
    private static function cells(array $row, array $columns): array
    {
        $cells = [];
        foreach ($columns as $key => $index) {
            $cells[$key] = trim((string) ($row[$index] ?? ''));
        }

        return $cells;
    }

    /**
     * @param  array<string, string>  $cells
     */
    private static function isBlank(array $cells): bool
    {
        return implode('', $cells) === '';
    }

    /**
     * @param  array<string, string>  $cells
     * @param  array<string, int>  $columns
     * @return list<string>
     */
    private static function validateProduct(array $cells, array $columns): array
    {
        $data = array_map(fn (string $value): ?string => $value === '' ? null : $value, $cells);
        foreach (['price', 'old_price', 'cost_price', 'stock', 'low_stock_threshold'] as $numeric) {
            if (isset($data[$numeric])) {
                $data[$numeric] = self::number($data[$numeric]) ?? $data[$numeric];
            }
        }

        $rules = [
            'sku' => ['required', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:255'],
            'format' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'old_price' => ['nullable', 'numeric', 'min:0', 'gt:price'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'description' => ['nullable', 'string', 'max:5000'],
            'options' => ['nullable', 'string', 'max:200'],
        ];

        $validator = Validator::make($data, array_intersect_key($rules, $columns + ['sku' => 0]), [], array_map('mb_strtolower', self::PRODUCT_COLUMNS));
        $errors = $validator->errors()->all();

        foreach (['visible', 'is_new', 'is_hot'] as $flag) {
            if (($cells[$flag] ?? '') !== '' && self::boolean($cells[$flag]) === null) {
                $errors[] = '« '.self::PRODUCT_COLUMNS[$flag].' » : répondez oui ou non.';
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, string>  $cells
     * @return list<string>
     */
    private static function validateVariant(array $cells): array
    {
        $errors = [];

        if (($cells['sku'] ?? '') === '') {
            $errors[] = 'Référence produit manquante.';
        }
        if (ProductVariant::labelFor([$cells['option1'] ?? null, $cells['option2'] ?? null, $cells['option3'] ?? null]) === '') {
            $errors[] = 'Renseignez au moins une option.';
        }
        foreach (['price', 'old_price', 'stock'] as $numeric) {
            $value = $cells[$numeric] ?? '';
            if ($value !== '' && (self::number($value) === null || self::number($value) < 0)) {
                $errors[] = '« '.self::VARIANT_COLUMNS[$numeric].' » doit être un nombre positif.';
            }
        }
        if (($cells['visible'] ?? '') !== '' && self::boolean($cells['visible']) === null) {
            $errors[] = '« Visible » : répondez oui ou non.';
        }

        return $errors;
    }

    private static function normalize(string $text): string
    {
        $ascii = Str::ascii(mb_strtolower($text));

        return trim(preg_replace('/[^a-z0-9]+/', ' ', $ascii) ?? '');
    }

    private static function number(mixed $value): int|float|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        $clean = str_replace([' ', "\u{00A0}", "\u{202F}"], '', (string) $value);
        $clean = str_replace(',', '.', $clean);

        return is_numeric($clean) ? $clean + 0 : null;
    }

    private static function boolean(mixed $value): ?bool
    {
        return match (self::normalize((string) $value)) {
            'oui', 'o', 'yes', 'y', 'true', 'vrai', '1', 'x' => true,
            'non', 'n', 'no', 'false', 'faux', '0' => false,
            default => null,
        };
    }

    /**
     * @param  array<string, int>  $summary
     */
    private static function category(string $name, array &$summary): Category
    {
        $category = Category::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

        if ($category) {
            return $category;
        }

        $summary['categories']++;

        return Category::query()->create([
            'slug' => self::uniqueSlug($name, Category::class),
            'name' => $name,
            'position' => (int) Category::query()->max('position') + 1,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, int>  $summary
     */
    private static function brand(string $name, array &$summary): Brand
    {
        $brand = Brand::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

        if ($brand) {
            return $brand;
        }

        $summary['brands']++;

        return Brand::query()->create([
            'slug' => self::uniqueSlug($name, Brand::class),
            'name' => $name,
            'color' => '#2a2a2a',
            'style' => 'plain',
            'is_active' => true,
            'is_featured' => false,
        ]);
    }

    /**
     * @param  class-string<Product|Category|Brand>  $model
     */
    private static function uniqueSlug(string $text, string $model = Product::class): string
    {
        $base = Str::slug($text) ?: 'article';
        $slug = $base;
        $n = 2;

        while ($model::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }
}
