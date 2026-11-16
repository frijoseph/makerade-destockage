<?php

namespace App\Console\Commands;

use App\Support\Catalog;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;

/**
 * Génère une version statique du site (HTML + assets) pour GitHub Pages.
 *   php artisan site:export --root=https://frijoseph.github.io/makerade-destockage --out=dist
 */
class ExportStatic extends Command
{
    protected $signature = 'site:export {--root= : URL publique de base (sans slash final)} {--out=dist : dossier de sortie}';
    protected $description = 'Exporte le site en pages HTML statiques';

    public function handle(): int
    {
        $root = rtrim((string) $this->option('root'), '/');
        if (! $root) { $this->error('--root est requis'); return 1; }
        $out = base_path($this->option('out'));
        File::deleteDirectory($out);
        File::ensureDirectoryExists($out);

        URL::forceRootUrl($root);
        URL::forceScheme(parse_url($root, PHP_URL_SCHEME) ?: 'https');

        // pages à générer : [chemin demandé => fichier de sortie]
        $pages = ['/' => '', '/shop' => 'shop', '/best-deals' => 'best-deals', '/new-arrivals' => 'new-arrivals', '/brands' => 'brands',
            '/blogs' => 'blogs', '/contact' => 'contact', '/cart' => 'cart', '/wishlist' => 'wishlist', '/account' => 'account', '/track-order' => 'track-order'];
        foreach (['shipping', 'returns', 'terms', 'privacy', 'about'] as $s) $pages["/page/$s"] = "page/$s";
        foreach (Catalog::products() as $p) $pages['/product/' . $p['slug']] = 'product/' . $p['slug'];
        foreach (['shop', 'best-deals', 'new-arrivals'] as $list) {
            foreach (Catalog::categories() as $c) $pages["/$list?cat={$c['slug']}"] = "$list/{$c['slug']}";
        }

        $kernel = app(Kernel::class);
        foreach ($pages as $uri => $dir) {
            $request = Request::create($uri, 'GET');
            $response = $kernel->handle($request);
            if ($response->getStatusCode() !== 200) { $this->warn("$uri → " . $response->getStatusCode()); continue; }
            $html = $this->rewrite($response->getContent(), $root);
            $target = $out . ($dir === '' ? '' : '/' . $dir);
            File::ensureDirectoryExists($target);
            File::put($target . '/index.html', $html);
            $kernel->terminate($request, $response);
        }

        // assets publics
        foreach (['css', 'js', 'img'] as $d) File::copyDirectory(public_path($d), $out . '/' . $d);
        File::put($out . '/.nojekyll', '');
        File::put($out . '/404.html', $this->rewrite(File::get($out . '/index.html'), $root));
        $this->info(count($pages) . ' pages exportées dans ' . $out);
        return 0;
    }

    /** Ajoute les "/" finaux pour que les liens pointent vers les dossiers (index.html). */
    private function rewrite(string $html, string $root): string
    {
        $q = preg_quote($root, '~');
        // listes filtrées : /shop?cat=x → /shop/x/
        $html = preg_replace('~(' . $q . '/(?:shop|best-deals|new-arrivals))(?:\?|&amp;)cat=([a-z]+)~', '$1/$2/', $html);
        // href/action sans extension → slash final
        $html = preg_replace_callback('~(href|action)="(' . $q . ')((?:/[A-Za-z0-9_\-]+)*)(/?)(\?[^"#]*)?(#[^"]*)?"~', function ($m) {
            $path = $m[3];
            $last = basename($path);
            return $m[1] . '="' . $m[2] . $path . '/' . ($m[5] ?? '') . ($m[6] ?? '') . '"';
        }, $html);
        return $html;
    }
}
