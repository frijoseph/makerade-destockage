<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Setting;
use App\Support\ShopSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeaderMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['shop.catalog_from_db' => true]);
    }

    public function test_the_automatic_menu_groups_extra_categories_in_a_sub_menu(): void
    {
        foreach (range(1, 8) as $number) {
            Category::factory()->create(['name' => "Rayon {$number}", 'slug' => "rayon-{$number}"]);
        }

        Setting::putMany(['header_menu_auto_limit' => 3, 'header_more_label' => 'Voir plus']);

        $menu = app(ShopSettings::class)->headerMenu();
        $more = collect($menu)->firstWhere('label', 'Voir plus');

        $this->assertNotNull($more);
        $this->assertCount(5, $more['children']);
        $this->assertSame(3, collect($menu)->filter(fn (array $link): bool => str_starts_with($link['label'], 'Rayon'))->count());

        $this->get('/')->assertOk()->assertSee('subdrop', false)->assertSee('Voir plus');
    }

    public function test_a_zero_limit_keeps_every_category_in_the_bar(): void
    {
        foreach (range(1, 8) as $number) {
            Category::factory()->create(['name' => "Rayon {$number}", 'slug' => "rayon-{$number}"]);
        }

        Setting::putMany(['header_menu_auto_limit' => 0]);

        $menu = app(ShopSettings::class)->headerMenu();

        $this->assertSame([], collect($menu)->pluck('children')->flatten(1)->all());
    }

    public function test_custom_menu_entries_can_have_sub_menus(): void
    {
        Setting::putMany(['header_menu' => [
            ['label' => 'Maison', 'url' => '/shop?cat=maison', 'children' => "Cuisine | /shop?cat=cuisine\nSalle de bain | /shop?cat=bain\njavascript:alert(1)"],
            ['label' => 'Blog', 'url' => '/blogs', 'children' => ''],
        ]]);

        $menu = app(ShopSettings::class)->headerMenu();
        $maison = collect($menu)->firstWhere('label', 'Maison');

        $this->assertSame(['Cuisine', 'Salle de bain', 'javascript:alert(1)'], array_column($maison['children'], 'label'));
        $this->assertSame('#', $maison['children'][2]['url'], 'script links are neutralised');
        $this->assertSame([], collect($menu)->firstWhere('label', 'Blog')['children']);

        $this->get('/')->assertOk()->assertSee('Salle de bain')->assertSee('d-sub-list', false);
    }
}
