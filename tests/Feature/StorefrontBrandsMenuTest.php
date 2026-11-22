<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontBrandsMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['shop.catalog_from_db' => true]);
    }

    public function test_home_brand_band_only_shows_featured_brands_in_order_and_respects_the_limit(): void
    {
        Brand::factory()->create(['name' => 'Marque Second', 'is_featured' => true, 'position' => 2]);
        Brand::factory()->create(['name' => 'Marque Premiere', 'is_featured' => true, 'position' => 1]);
        Brand::factory()->create(['name' => 'Marque Cachee', 'is_featured' => false, 'position' => 0]);

        $this->get('/')->assertOk()
            ->assertSeeInOrder(['Marque Premiere', 'Marque Second'])
            ->assertDontSee('Marque Cachee');

        Setting::putMany(['brands_limit' => 1]);
        $this->get('/')->assertDontSee('Marque Second');

        $this->get('/brands')->assertOk()->assertSee('Marque Cachee');
    }

    public function test_brand_band_can_be_disabled_and_titled(): void
    {
        Brand::factory()->create(['name' => 'Marque Visible']);
        Setting::putMany(['brands_title' => 'Ils nous font confiance']);
        $this->get('/')->assertSee('Ils nous font confiance');

        Setting::putMany(['brands_enabled' => false]);
        $this->get('/')->assertDontSee('Ils nous font confiance');
    }

    public function test_menu_starts_with_all_products_and_can_be_changed_or_removed(): void
    {
        $this->get('/shop')->assertOk()->assertSee('Tous les produits');

        Setting::putMany(['all_products_link_label' => 'Tout le catalogue']);
        $this->get('/shop')->assertSee('Tout le catalogue');

        Setting::putMany(['all_products_link_enabled' => false]);
        $this->get('/shop')->assertDontSee('Tout le catalogue');
    }

    public function test_primary_colour_is_injected_as_css_variables(): void
    {
        Setting::putMany(['theme_color' => '#c2410c']);

        $this->get('/')->assertOk()->assertSee('--g:#c2410c', false)->assertSee('"primary":"#c2410c"', false);
    }

    public function test_announcement_bar_and_header_share_one_sticky_container(): void
    {
        $this->get('/')->assertOk()->assertSee('id="topstack"', false);
    }
}
