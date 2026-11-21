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
php artisan storage:link
php artisan serve
```

En environnement `local`, le seed ajoute aussi des clients et ~60 jours de commandes de démonstration.
Sans MySQL, laisser `CATALOG_USE_DB=false` : le site utilise alors les données intégrées (`app/Support/Catalog.php`).

## Administration

Back-office sur `/admin` (identifiants du premier compte : `ADMIN_EMAIL` / `ADMIN_PASSWORD` dans `.env`, créé par `db:seed`).

- **Vue d'ensemble** : chiffre d'affaires, commandes, panier moyen, santé du stock, ventes par catégorie, écoulement du stock.
- **Commandes** : commandes du site + saisie manuelle (WhatsApp, téléphone, magasin), statut, paiement (Orange Money, MTN MoMo, Wave, Moov, livraison).
- **Produits, catégories, marques** : CRUD avec photo, prix barré, visibilité.
- **Stock** : réapprovisionnement, inventaire, pertes ; chaque mouvement est tracé (les ventes et annulations sont automatiques).
- **Clients** : créés à la commande, reconnus par leur téléphone.
- **Paramètres** (administrateurs) : monnaie, frais et seuil de livraison, contacts.

Rôles : `admin` (tout), `manager` (tout sauf paramètres), `customer` (pas d'accès).

## Structure utile

- `app/Support/Catalog.php` — catalogue de la boutique (MySQL ou données intégrées)
- `app/Support/OrderPlacement.php` — création des commandes et changements de statut (stock inclus)
- `app/Support/Inventory.php` — tous les mouvements de stock
- `app/Support/SalesReport.php` — chiffres du tableau de bord
- `app/Http/Controllers/Admin` et `resources/views/admin` — back-office (`public/css/admin.css`, `public/js/admin.js`)
- `resources/views` — pages Blade (accueil, boutique, fiche produit, panier…)
- `public/css` (`app.css`, `fresh.css`, `fx.css`, `live.css`) et `public/js` (`app.js`, `fx.js`, `live.js`)

## Tests

```bash
php artisan test --compact
```

## Crédits images

Photos d'ambiance : StockSnap et Rawpixel (CC0). Photos de produits : Open Beauty Facts (CC BY-SA 3.0).
Marques citées à titre d'illustration uniquement.
