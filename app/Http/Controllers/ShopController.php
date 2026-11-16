<?php

namespace App\Http\Controllers;

use App\Support\Catalog;

class ShopController extends Controller
{
    public function home()
    {
        return view('pages.home', [
            'categories' => Catalog::categories(),
            'featured'   => array_slice(Catalog::products(), 0, 6),
            'flash'      => array_slice(collect(Catalog::deals())->sortByDesc('off')->values()->all(), 0, 6),
            'brands'     => Catalog::brands(),
        ]);
    }

    private function listing(string $title, string $sub, array $products, string $active)
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

    public function shop()
    {
        return $this->listing('Tous les produits', 'Tout pour votre maison, au même endroit.', Catalog::products(), 'shop');
    }

    public function deals()
    {
        return $this->listing('Bons plans', 'Des remises sélectionnées, mises à jour chaque jour.', Catalog::deals(), 'deals');
    }

    public function arrivals()
    {
        return $this->listing('Nouveautés', 'Fraîchement arrivés en rayon cette semaine.',
            array_values(array_filter(Catalog::products(), fn ($p) => $p['is_new'])), 'arrivals');
    }

    public function brands()
    {
        return view('pages.brands', ['brands' => Catalog::brands(), 'products' => Catalog::products()]);
    }

    public function product(string $slug)
    {
        $product = Catalog::product($slug) ?? abort(404);
        $related = array_slice(array_values(array_filter(Catalog::products(),
            fn ($p) => $p['slug'] !== $slug && $p['category'] === $product['category'])), 0, 4);
        return view('pages.product', compact('product', 'related'));
    }

    public function blogs()    { return view('pages.blogs'); }
    public function contact()  { return view('pages.contact'); }
    public function cart()     { return view('pages.cart', ['catalog' => Catalog::products()]); }
    public function wishlist() { return view('pages.wishlist', ['catalog' => Catalog::products()]); }
    public function account()  { return view('pages.account'); }
    public function track()    { return view('pages.track'); }

    public function info(string $slug)
    {
        $pages = [
            'shipping' => 'Politique de livraison', 'returns' => 'Retours & remboursements', 'terms' => 'Conditions générales',
            'privacy' => 'Politique de confidentialité', 'about' => 'À propos de Makerade Destockage',
        ];
        $title = $pages[$slug] ?? abort(404);
        return view('pages.info', compact('title'));
    }
}
