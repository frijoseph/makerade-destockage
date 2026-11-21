<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Setting;
use App\PageType;
use App\Support\Catalog;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function home(): View
    {
        return view('pages.home', [
            'categories' => Catalog::categories(),
            'featured' => array_slice(Catalog::products(), 0, (int) Setting::get('featured_count')),
            'flash' => array_slice(collect(Catalog::deals())->sortByDesc('off')->values()->all(), 0, (int) Setting::get('flash_count')),
            'brands' => Catalog::brands(),
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     */
    private function listing(string $title, string $sub, array $products, string $active): View
    {
        $cat = request('cat');
        if ($cat && $cat !== 'all') {
            $products = array_values(array_filter($products, fn ($p) => $p['category'] === $cat));
        }
        if ($q = trim((string) request('q'))) {
            $products = array_values(array_filter($products, fn ($p) => stripos($p['name'].' '.$p['brand'].' '.$p['variant'], $q) !== false));
        }

        return view('pages.listing', [
            'title' => $title, 'sub' => $sub, 'products' => $products, 'active' => $active,
            'categories' => Catalog::categories(), 'cat' => $cat,
        ]);
    }

    public function shop(): View
    {
        return $this->listing('Tous les produits', 'Tout pour votre maison, au même endroit.', Catalog::products(), 'shop');
    }

    public function deals(): View
    {
        return $this->listing('Bons plans', 'Nos produits remisés, du plus fort au plus faible rabais.', collect(Catalog::deals())->sortByDesc('off')->values()->all(), 'deals');
    }

    public function arrivals(): View
    {
        return $this->listing('Nouveautés', 'Les derniers arrivages en rayon.',
            array_values(array_filter(Catalog::products(), fn ($p) => $p['is_new'])), 'arrivals');
    }

    public function brands(): View
    {
        return view('pages.brands', ['brands' => Catalog::brands(), 'products' => Catalog::products()]);
    }

    public function product(string $slug): View
    {
        $product = Catalog::product($slug) ?? abort(404);
        $related = array_slice(array_values(array_filter(Catalog::products(),
            fn ($p) => $p['slug'] !== $slug && $p['category'] === $product['category'])), 0, 4);

        return view('pages.product', compact('product', 'related'));
    }

    public function blogs(): View
    {
        return view('pages.blogs', [
            'posts' => Page::query()->ofType(PageType::Article)->published()->latest('published_at')->get(),
        ]);
    }

    public function article(string $slug): View
    {
        $post = Page::query()->ofType(PageType::Article)->published()->where('slug', $slug)->firstOrFail();

        return view('pages.article', [
            'post' => $post,
            'others' => Page::query()->ofType(PageType::Article)->published()->whereKeyNot($post->id)->latest('published_at')->limit(3)->get(),
        ]);
    }

    public function contact(): View
    {
        return view('pages.contact');
    }

    public function cart(): View
    {
        return view('pages.cart', ['catalog' => Catalog::products()]);
    }

    public function wishlist(): View
    {
        return view('pages.wishlist', ['catalog' => Catalog::products()]);
    }

    public function track(): View
    {
        return view('pages.track');
    }

    public function info(string $slug): View
    {
        $page = Page::query()->ofType(PageType::Page)->published()->where('slug', $slug)->firstOrFail();

        return view('pages.info', compact('page'));
    }
}
