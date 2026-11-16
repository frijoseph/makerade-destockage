# Makerade Destockage

Site e-commerce de **produits d'entretien et de bien-être** (déstockage) — Laravel + MySQL.
Maquette dynamique : diaporama animé, ventes flash avec compte à rebours, panier latéral,
test de propreté interactif, bulles de savon, notifications d'achat, 100 % responsive.

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Dans `.env`, configurer MySQL :

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=makerade
DB_USERNAME=root
DB_PASSWORD=
CATALOG_USE_DB=true
SESSION_DRIVER=file
CACHE_STORE=file
```

```bash
php artisan migrate --seed
php artisan serve
```

Sans MySQL, laisser `CATALOG_USE_DB=false` : le site utilise alors les données intégrées (`app/Support/Catalog.php`).

## Structure utile

- `app/Support/Catalog.php` — catalogue (catégories, produits, marques)
- `resources/views` — pages Blade (accueil, boutique, fiche produit, panier…)
- `public/css` (`app.css`, `fresh.css`, `fx.css`, `live.css`) et `public/js` (`app.js`, `fx.js`, `live.js`)

## Crédits images

Photos d'ambiance : StockSnap et Rawpixel (CC0). Photos de produits : Open Beauty Facts (CC BY-SA 3.0).
Marques citées à titre d'illustration uniquement.
