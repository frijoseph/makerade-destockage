<?php

namespace App\Support;

use App\OrderStatus;
use App\PaymentMethod;

/**
 * Every shop setting editable from the admin: group, field type, label and default value.
 * The settings screen is generated from this schema and Setting::get() falls back to these defaults.
 *
 * Field types: text, textarea, markdown, email, url, tel, number, select, boolean, color, image, datetime,
 * checkboxes (list of values) and list (repeatable rows described by "fields").
 */
class SettingsSchema
{
    /**
     * Icons available in the storefront icon component, for perks and reassurance blocks.
     *
     * @var array<string, string>
     */
    public const ICONS = [
        'truck' => 'Camion', 'shieldcheck' => 'Bouclier validé', 'shield' => 'Cadenas', 'leaf' => 'Feuille',
        'grid' => 'Grille', 'refresh' => 'Retour', 'return' => 'Horloge retour', 'chat' => 'Discussion',
        'headset' => 'Casque', 'award' => 'Médaille', 'tag' => 'Étiquette', 'package' => 'Colis',
        'heart' => 'Cœur', 'smile' => 'Étoile', 'clock' => 'Horloge', 'dollar' => 'Dollar', 'phone' => 'Téléphone',
        'spray' => 'Spray', 'pump' => 'Flacon pompe', 'utensils' => 'Couverts', 'dispenser' => 'Distributeur', 'box' => 'Boîte', 'bowl' => 'Bol',
    ];

    /**
     * @return array<string, array{label: string, icon: string, fields: array<string, array<string, mixed>>}>
     */
    public static function groups(): array
    {
        $icons = self::ICONS;

        return [
            'shop' => [
                'label' => 'Boutique',
                'icon' => 'home',
                'fields' => [
                    'shop_name' => ['type' => 'text', 'label' => 'Nom de la boutique', 'default' => 'Makerade Destockage', 'rules' => ['required', 'max:120']],
                    'shop_tagline' => ['type' => 'textarea', 'label' => 'Présentation courte (pied de page)', 'default' => 'Produits d’entretien et de bien-être pour une maison saine et une vie sereine.'],
                    'meta_description' => ['type' => 'textarea', 'label' => 'Description pour les moteurs de recherche', 'default' => '{shop_name} — le déstockage au meilleur prix : tous vos produits du quotidien à prix cassés. Livraison rapide, retours faciles.'],
                    'logo' => ['type' => 'image', 'label' => 'Logo', 'default' => 'img/logo.png'],
                    'favicon' => ['type' => 'image', 'label' => 'Icône d’onglet (favicon)', 'default' => 'img/favicon.png'],
                    'theme_color' => ['type' => 'color', 'label' => 'Couleur principale', 'help' => 'Appliquée aux boutons, liens, notifications (messages), assistant et à la barre du navigateur mobile.', 'default' => '#0f6a26', 'rules' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/']],
                    'delivery_zone' => ['type' => 'text', 'label' => 'Zone de livraison (en-tête)', 'default' => 'Côte d’Ivoire'],
                    'default_country' => ['type' => 'select', 'label' => 'Pays par défaut des numéros de téléphone', 'help' => 'Indicatif proposé d’office dans tous les formulaires.', 'default' => 'CI', 'options' => collect(Phone::countries())->mapWithKeys(fn (array $country, string $iso): array => [$iso => $country['flag'].' '.$country['name'].' (+'.$country['dial'].')'])->all()],
                    'language_label' => ['type' => 'text', 'label' => 'Langue affichée (pied de page)', 'default' => 'Français'],
                    'search_placeholder' => ['type' => 'text', 'label' => 'Texte du champ de recherche', 'default' => 'Rechercher un produit d’entretien, de bien-être...'],
                    'order_prefix' => ['type' => 'text', 'label' => 'Préfixe des numéros de commande', 'help' => 'Ex. MK → MK-2026-00042.', 'default' => 'MK', 'rules' => ['required', 'alpha_num', 'max:6']],
                    'sku_prefix' => ['type' => 'text', 'label' => 'Préfixe des références produit', 'default' => 'MK', 'rules' => ['required', 'alpha_num', 'max:6']],
                    'default_low_stock_threshold' => ['type' => 'number', 'label' => 'Seuil d’alerte stock proposé pour un nouveau produit', 'default' => 10, 'rules' => ['required', 'integer', 'min:0']],
                ],
            ],
            'contact' => [
                'label' => 'Contact',
                'icon' => 'phone',
                'fields' => [
                    'contact_phone' => ['type' => 'tel', 'label' => 'Téléphone', 'default' => '+225 07 00 00 00 00'],
                    'contact_email' => ['type' => 'email', 'label' => 'E-mail', 'default' => 'contact@makerade-destockage.com', 'rules' => ['nullable', 'email']],
                    'contact_address' => ['type' => 'text', 'label' => 'Adresse', 'default' => 'Abidjan, Côte d’Ivoire'],
                    'opening_hours' => ['type' => 'text', 'label' => 'Horaires', 'default' => 'Lun–Sam, 8h00 – 19h00'],
                    'response_time' => ['type' => 'text', 'label' => 'Délai de réponse annoncé', 'default' => '24 heures'],
                    'whatsapp_number' => ['type' => 'text', 'label' => 'Numéro WhatsApp', 'help' => 'Format international, chiffres uniquement (ex. 2250700000000).', 'default' => '', 'rules' => ['nullable', 'regex:/^\d{8,15}$/']],
                    'contact_intro' => ['type' => 'textarea', 'label' => 'Texte d’introduction de la page Contact', 'default' => 'Nous serions ravis de vous lire. Notre équipe répond sous {response_time}.'],
                ],
            ],
            'social' => [
                'label' => 'Réseaux sociaux',
                'icon' => 'users',
                'fields' => [
                    'social_facebook' => ['type' => 'url', 'label' => 'Facebook', 'default' => ''],
                    'social_instagram' => ['type' => 'url', 'label' => 'Instagram', 'default' => ''],
                    'social_tiktok' => ['type' => 'url', 'label' => 'TikTok', 'default' => ''],
                    'social_youtube' => ['type' => 'url', 'label' => 'YouTube', 'default' => ''],
                    'social_pinterest' => ['type' => 'url', 'label' => 'Pinterest', 'default' => ''],
                ],
            ],
            'money' => [
                'label' => 'Monnaie et paiement',
                'icon' => 'wallet',
                'fields' => [
                    'currency_symbol' => ['type' => 'text', 'label' => 'Symbole monétaire', 'default' => '$', 'rules' => ['required', 'max:8']],
                    'currency_code' => ['type' => 'text', 'label' => 'Code devise (pied de page)', 'default' => 'USD', 'rules' => ['required', 'max:8']],
                    'currency_position' => ['type' => 'select', 'label' => 'Position du symbole', 'default' => 'before', 'options' => ['before' => 'Avant ($10)', 'after' => 'Après (10 FCFA)']],
                    'currency_decimals' => ['type' => 'select', 'label' => 'Décimales', 'default' => '2', 'options' => ['2' => '2 (10.50)', '0' => '0 (1 050)']],
                    'decimal_separator' => ['type' => 'select', 'label' => 'Séparateur décimal', 'default' => 'dot', 'options' => ['dot' => 'Point (10.50)', 'comma' => 'Virgule (10,50)']],
                    'thousands_separator' => ['type' => 'select', 'label' => 'Séparateur de milliers', 'default' => 'comma', 'options' => ['comma' => 'Virgule (1,000)', 'space' => 'Espace (1 000)', 'dot' => 'Point (1.000)', 'none' => 'Aucun (1000)']],
                    'payment_methods' => ['type' => 'checkboxes', 'label' => 'Moyens de paiement proposés', 'default' => ['cash_on_delivery', 'orange_money', 'mtn_momo', 'wave', 'moov_money'], 'options' => collect(PaymentMethod::cases())->mapWithKeys(fn (PaymentMethod $method): array => [$method->value => $method->label()])->all()],
                ],
            ],
            'delivery' => [
                'label' => 'Livraison et retours',
                'icon' => 'truck',
                'fields' => [
                    'shipping_fee' => ['type' => 'number', 'label' => 'Frais de livraison', 'default' => 4.99, 'rules' => ['required', 'numeric', 'min:0']],
                    'free_shipping_threshold' => ['type' => 'number', 'label' => 'Livraison offerte dès', 'help' => '0 = jamais offerte.', 'default' => 49, 'rules' => ['required', 'numeric', 'min:0']],
                    'delivery_delay' => ['type' => 'text', 'label' => 'Délai de livraison standard', 'default' => '2 à 4 jours ouvrés'],
                    'return_days' => ['type' => 'number', 'label' => 'Délai de retour (jours)', 'default' => 30, 'rules' => ['required', 'integer', 'min:0']],
                    'restrict_to_zones' => ['type' => 'boolean', 'label' => 'Livrer uniquement les villes du tableau ci-dessus', 'help' => 'Si activé, une commande vers une ville absente du tableau est refusée.', 'default' => false],
                    'default_city' => ['type' => 'text', 'label' => 'Ville pré-remplie au panier', 'default' => 'Abidjan'],
                    'checkout_guest_note' => ['type' => 'text', 'label' => 'Mention « commande sans compte » (panier)', 'help' => 'Affichée au-dessus du bouton de confirmation aux visiteurs non connectés. Vide = masquée.', 'default' => 'Pas besoin de compte : votre nom, téléphone et adresse suffisent.'],
                    'confirm_account_cta' => ['type' => 'text', 'label' => 'Invitation à créer un compte (page de confirmation)', 'help' => 'Montrée aux visiteurs sans compte après leur commande. Vide = masquée.', 'default' => 'Créez un compte gratuit pour suivre vos commandes et recommander en un clic.'],
                    'checkout_hint' => ['type' => 'textarea', 'label' => 'Message sous le formulaire de commande', 'default' => 'Nous vous appelons pour confirmer la commande avant la livraison.'],
                    'product_perks' => ['type' => 'list', 'label' => 'Garanties affichées sur la fiche produit', 'default' => [
                        ['icon' => 'truck', 'text' => 'Livraison gratuite dès {free_shipping_threshold} d’achat'],
                        ['icon' => 'return', 'text' => 'Retours faciles sous {return_days} jours'],
                        ['icon' => 'shield', 'text' => 'Paiement 100 % sécurisé'],
                    ], 'fields' => ['icon' => ['type' => 'select', 'label' => 'Icône', 'options' => $icons], 'text' => ['type' => 'text', 'label' => 'Texte']]],
                    'product_default_description' => ['type' => 'textarea', 'label' => 'Description par défaut d’un produit', 'help' => 'Utilisée quand le produit n’a pas de description.', 'default' => 'Une qualité de confiance pour le quotidien. Sélectionné avec soin par {shop_name}, livré rapidement et couvert par notre politique de retour sous {return_days} jours.'],
                ],
            ],
            'home' => [
                'label' => 'Page d’accueil',
                'icon' => 'grid',
                'fields' => [
                    'hero_slides' => ['type' => 'list', 'label' => 'Diaporama', 'default' => [
                        ['layout' => 'feature', 'theme' => 'fs1', 'eyebrow' => 'Entretien & bien-être', 'title' => 'Une maison', 'highlight' => 'impeccable|éclatante|saine|sereine', 'text' => 'Des produits d’entretien et de bien-être de qualité, à prix déstockés. Livraison rapide en {delivery_zone}.', 'button_label' => 'Découvrir nos produits', 'button_url' => '/shop', 'image' => 'img/mock/hero.jpg', 'badge' => 'Une maison propre, une vie sereine', 'script' => 'Maison propre, esprit léger'],
                        ['layout' => 'promo', 'theme' => 'fs2', 'eyebrow' => 'Entretien & lessives', 'title' => 'Des surfaces', 'highlight' => 'qui brillent', 'text' => 'Nettoyants, lessives et accessoires des plus grandes marques, à prix déstockés.', 'button_label' => 'Voir les produits', 'button_url' => '/shop?cat=entretien', 'image' => 'img/photos/hero-entretien.jpg', 'badge' => 'auto:entretien', 'script' => ''],
                        ['layout' => 'promo', 'theme' => 'fs3', 'eyebrow' => 'Soins & bien-être', 'title' => 'Prenez soin', 'highlight' => 'de vous', 'text' => 'Savons, shampooings et soins du quotidien pour toute la famille, à prix doux.', 'button_label' => 'Découvrir les soins', 'button_url' => '/shop?cat=bienetre', 'image' => 'img/photos/hero-soins.jpg', 'badge' => 'auto:bienetre', 'script' => ''],
                    ], 'fields' => [
                        'layout' => ['type' => 'select', 'label' => 'Mise en page', 'options' => ['feature' => 'Vedette (pastille + texte manuscrit)', 'promo' => 'Promotion (pastille de remise)']],
                        'theme' => ['type' => 'select', 'label' => 'Fond', 'options' => ['fs1' => 'Vert clair', 'fs2' => 'Pêche', 'fs3' => 'Crème']],
                        'eyebrow' => ['type' => 'text', 'label' => 'Sur-titre'],
                        'title' => ['type' => 'text', 'label' => 'Titre'],
                        'highlight' => ['type' => 'text', 'label' => 'Mot mis en avant', 'help' => 'Séparez plusieurs mots par | pour les faire défiler.'],
                        'text' => ['type' => 'textarea', 'label' => 'Texte'],
                        'button_label' => ['type' => 'text', 'label' => 'Bouton'],
                        'button_url' => ['type' => 'text', 'label' => 'Lien du bouton'],
                        'image' => ['type' => 'image', 'label' => 'Image'],
                        'badge' => ['type' => 'text', 'label' => 'Pastille', 'help' => 'Texte libre, ou auto:slug-categorie pour afficher la plus forte remise réelle de la catégorie.'],
                        'script' => ['type' => 'text', 'label' => 'Texte manuscrit (mise en page vedette)'],
                    ]],
                    'hero_perks' => ['type' => 'list', 'label' => 'Atouts sous le diaporama', 'default' => [
                        ['icon' => 'truck', 'text' => 'Livraison rapide et fiable'],
                        ['icon' => 'shieldcheck', 'text' => '100 % authentiques et de qualité'],
                        ['icon' => 'shield', 'text' => 'Paiement sûr et sécurisé'],
                    ], 'fields' => ['icon' => ['type' => 'select', 'label' => 'Icône', 'options' => $icons], 'text' => ['type' => 'text', 'label' => 'Texte']]],
                    'flash_enabled' => ['type' => 'boolean', 'label' => 'Afficher les ventes flash', 'default' => true],
                    'flash_title' => ['type' => 'text', 'label' => 'Titre des ventes flash', 'default' => 'Ventes Flash'],
                    'flash_ends_at' => ['type' => 'datetime', 'label' => 'Fin des ventes flash', 'help' => 'Vide = pas de compte à rebours.', 'default' => ''],
                    'flash_count' => ['type' => 'number', 'label' => 'Nombre de produits en vente flash', 'default' => 6, 'rules' => ['required', 'integer', 'min:1', 'max:24']],
                    'featured_title' => ['type' => 'text', 'label' => 'Titre des produits à la une', 'default' => 'Produits à la une'],
                    'featured_count' => ['type' => 'number', 'label' => 'Nombre de produits à la une', 'default' => 6, 'rules' => ['required', 'integer', 'min:1', 'max:24']],
                    'home_banners' => ['type' => 'list', 'label' => 'Bannières', 'default' => [
                        ['style' => 'fb-peach2', 'title' => 'Entretien de la maison à prix déstockés', 'text' => 'Nettoyants, désinfectants et accessoires. Une maison éclatante, sans effort.', 'button_label' => 'Acheter', 'url' => '/shop?cat=entretien', 'image' => 'img/photos/clean-test.jpg', 'badge' => 'auto:entretien'],
                        ['style' => 'fb-cream', 'title' => 'Soins & bien-être, plus de douceur', 'text' => '', 'button_label' => 'Acheter les soins', 'url' => '/shop?cat=bienetre', 'image' => 'img/photos/ban-soins.jpg', 'badge' => ''],
                        ['style' => 'fb-pink', 'title' => 'Linge propre, parfum de frais', 'text' => '', 'button_label' => 'Acheter les lessives', 'url' => '/shop?cat=lessive', 'image' => 'img/photos/hero-linge.jpg', 'badge' => ''],
                        ['style' => 'fb-gold', 'title' => 'Bons plans du moment', 'text' => 'Nos plus fortes remises', 'button_label' => 'Voir les offres', 'url' => '/best-deals', 'image' => 'img/mock/promo-4.jpg', 'badge' => 'auto:all'],
                        ['style' => 'fb-light', 'title' => 'Entretien responsable, une planète plus verte', 'text' => 'Des choix durables pour un avenir meilleur.', 'button_label' => 'En savoir plus', 'url' => '/page/about', 'image' => 'img/fresh/ban-5.jpg', 'badge' => ''],
                    ], 'fields' => [
                        'style' => ['type' => 'select', 'label' => 'Couleur', 'options' => ['fb-peach2' => 'Pêche', 'fb-cream' => 'Crème', 'fb-pink' => 'Rose', 'fb-gold' => 'Doré', 'fb-light' => 'Vert clair']],
                        'title' => ['type' => 'text', 'label' => 'Titre'],
                        'text' => ['type' => 'text', 'label' => 'Texte'],
                        'button_label' => ['type' => 'text', 'label' => 'Bouton'],
                        'url' => ['type' => 'text', 'label' => 'Lien'],
                        'image' => ['type' => 'image', 'label' => 'Image'],
                        'badge' => ['type' => 'text', 'label' => 'Pastille de remise', 'help' => 'Texte libre, ou auto:slug-categorie / auto:all pour la plus forte remise réelle.'],
                    ]],
                    'stats' => ['type' => 'list', 'label' => 'Chiffres clés', 'default' => [
                        ['source' => 'customers', 'value' => '', 'suffix' => '', 'label' => 'clients nous font confiance'],
                        ['source' => 'products', 'value' => '', 'suffix' => '', 'label' => 'produits d’entretien et de bien-être'],
                        ['source' => 'brands', 'value' => '', 'suffix' => '', 'label' => 'marques disponibles'],
                        ['source' => 'orders', 'value' => '', 'suffix' => '', 'label' => 'commandes livrées'],
                    ], 'fields' => [
                        'source' => ['type' => 'select', 'label' => 'Valeur', 'options' => ['customers' => 'Nombre de clients (auto)', 'products' => 'Produits en ligne (auto)', 'brands' => 'Marques (auto)', 'orders' => 'Commandes livrées (auto)', 'fixed' => 'Valeur saisie']],
                        'value' => ['type' => 'number', 'label' => 'Valeur saisie'],
                        'suffix' => ['type' => 'text', 'label' => 'Suffixe (ex. +, h, %)'],
                        'label' => ['type' => 'text', 'label' => 'Libellé'],
                    ]],
                    'clean_test_enabled' => ['type' => 'boolean', 'label' => 'Afficher le « test de propreté »', 'default' => true],
                    'clean_test_title' => ['type' => 'text', 'label' => 'Titre du test de propreté', 'default' => 'Le test de propreté ✨'],
                    'clean_test_text' => ['type' => 'textarea', 'label' => 'Texte du test de propreté', 'default' => 'Passez la souris (ou le doigt) sur la vitre sale pour la nettoyer et découvrir nos produits d’entretien.'],
                    'clean_test_image' => ['type' => 'image', 'label' => 'Image du test de propreté', 'default' => 'img/photos/hero-entretien.jpg'],
                    'clean_test_url' => ['type' => 'text', 'label' => 'Lien du test de propreté', 'default' => '/shop?cat=entretien'],
                    'brands_enabled' => ['type' => 'boolean', 'label' => 'Afficher le bandeau des marques', 'default' => true],
                    'brands_title' => ['type' => 'text', 'label' => 'Titre du bandeau des marques', 'default' => 'Nos marques de confiance'],
                    'brands_limit' => ['type' => 'number', 'label' => 'Nombre maximum de marques', 'help' => '0 = toutes les marques mises en avant.', 'default' => 0, 'rules' => ['required', 'integer', 'min:0', 'max:100']],
                    'brands_speed' => ['type' => 'number', 'label' => 'Durée d’un défilement complet (secondes)', 'help' => '0 = bandeau fixe, sans défilement.', 'default' => 32, 'rules' => ['required', 'integer', 'min:0', 'max:300']],
                    'brands_link_all' => ['type' => 'boolean', 'label' => 'Afficher le lien « Voir toutes les marques »', 'default' => true],
                    'why_title' => ['type' => 'text', 'label' => 'Titre « Pourquoi nous choisir »', 'default' => 'Pourquoi choisir {shop_name} ?'],
                    'why_items' => ['type' => 'list', 'label' => 'Arguments « Pourquoi nous choisir »', 'default' => [
                        ['icon' => 'grid', 'title' => 'Large choix', 'text' => 'Entretien et bien-être au même endroit'],
                        ['icon' => 'leaf', 'title' => 'Qualité garantie', 'text' => 'Des marques reconnues et authentiques'],
                        ['icon' => 'truck', 'title' => 'Livraison rapide', 'text' => 'Partout en {delivery_zone}'],
                        ['icon' => 'refresh', 'title' => 'Retours faciles', 'text' => 'Sous {return_days} jours'],
                        ['icon' => 'chat', 'title' => 'À votre écoute', 'text' => 'Réponse sous {response_time}'],
                    ], 'fields' => ['icon' => ['type' => 'select', 'label' => 'Icône', 'options' => $icons], 'title' => ['type' => 'text', 'label' => 'Titre'], 'text' => ['type' => 'text', 'label' => 'Texte']]],
                ],
            ],
            'navigation' => [
                'label' => 'Menus et pied de page',
                'icon' => 'orders',
                'fields' => [
                    'all_products_link_enabled' => ['type' => 'boolean', 'label' => 'Ajouter « Tous les produits » en tête du menu', 'default' => true],
                    'all_products_link_label' => ['type' => 'text', 'label' => 'Libellé du lien « Tous les produits »', 'default' => 'Tous les produits'],
                    'header_menu' => ['type' => 'list', 'label' => 'Menu principal', 'help' => 'Laissez vide pour afficher automatiquement les catégories actives. Chaque entrée peut avoir un sous-menu : une ligne par lien au format « Libellé | /adresse » (le mot catégories seul liste les catégories).', 'default' => [], 'fields' => self::linkFields() + ['children' => ['type' => 'textarea', 'label' => 'Sous-menu (facultatif)']]],
                    'header_menu_auto_limit' => ['type' => 'number', 'label' => 'Menu automatique : catégories affichées directement', 'help' => 'Les autres sont regroupées dans un sous-menu. 0 = toutes affichées. Sans effet si vous remplissez le menu principal ci-dessus.', 'default' => 5, 'rules' => ['required', 'integer', 'min:0', 'max:30']],
                    'header_more_label' => ['type' => 'text', 'label' => 'Menu automatique : libellé du sous-menu', 'default' => 'Plus', 'rules' => ['nullable', 'string', 'max:40']],
                    'special_link_label' => ['type' => 'text', 'label' => 'Lien mis en avant (en-tête)', 'default' => 'Offres spéciales'],
                    'special_link_url' => ['type' => 'text', 'label' => 'Adresse du lien mis en avant', 'default' => '/best-deals'],
                    'footer_columns' => ['type' => 'list', 'label' => 'Colonnes du pied de page', 'help' => 'Un lien par ligne au format « Libellé | /adresse ». Le mot catégories seul liste les catégories actives.', 'default' => [
                        ['title' => 'Boutique', 'links' => "catégories\nOffres spéciales | /best-deals"],
                        ['title' => 'Service client', 'links' => "Suivre ma commande | /track-order\nRetours & remboursements | /page/returns\nLivraison | /page/shipping\nConditions générales | /page/terms\nContact | /contact"],
                        ['title' => 'Entreprise', 'links' => "À propos | /page/about\nMarques | /brands\nBlog | /blogs\nConfidentialité | /page/privacy"],
                    ], 'fields' => ['title' => ['type' => 'text', 'label' => 'Titre'], 'links' => ['type' => 'textarea', 'label' => 'Liens']]],
                    'footer_script' => ['type' => 'text', 'label' => 'Texte manuscrit du pied de page', 'default' => 'Maison propre, vie sereine'],
                    'newsletter_title' => ['type' => 'text', 'label' => 'Titre de la newsletter', 'default' => 'Restez propre. Restez informé.'],
                    'newsletter_text' => ['type' => 'textarea', 'label' => 'Texte de la newsletter', 'default' => 'Abonnez-vous pour recevoir nos dernières offres, les arrivages et des promotions exclusives.'],
                    'footer_credits' => ['type' => 'markdown', 'label' => 'Crédits (bas de page)', 'default' => ''],
                ],
            ],
            'documents' => [
                'label' => 'Documents imprimés',
                'icon' => 'orders',
                'fields' => [
                    'print_company_name' => ['type' => 'text', 'label' => 'Raison sociale sur les documents', 'help' => 'Vide = nom de la boutique.', 'default' => ''],
                    'print_company_details' => ['type' => 'textarea', 'label' => 'Informations légales', 'help' => 'RCCM, n° de compte contribuable, adresse du siège… une ligne par information.', 'default' => ''],
                    'print_logo_enabled' => ['type' => 'boolean', 'label' => 'Afficher le logo', 'default' => true],
                    'print_delivery_title' => ['type' => 'text', 'label' => 'Titre du bon de livraison', 'default' => 'Bon de livraison'],
                    'print_receipt_title' => ['type' => 'text', 'label' => 'Titre du reçu', 'default' => 'Reçu'],
                    'print_delivery_show_prices' => ['type' => 'boolean', 'label' => 'Afficher les prix sur le bon de livraison', 'default' => true],
                    'print_delivery_note' => ['type' => 'textarea', 'label' => 'Message sur le bon de livraison', 'default' => 'Merci de vérifier vos produits en présence du livreur avant de signer.'],
                    'print_signatures_enabled' => ['type' => 'boolean', 'label' => 'Cadres de signature', 'default' => true],
                    'print_signature_left' => ['type' => 'text', 'label' => 'Libellé de la signature de gauche', 'default' => 'Le livreur'],
                    'print_signature_right' => ['type' => 'text', 'label' => 'Libellé de la signature de droite', 'default' => 'Le client'],
                    'print_footer' => ['type' => 'textarea', 'label' => 'Pied de page', 'help' => 'Valeurs automatiques possibles : {return_days}, {contact_phone}, {contact_email}, {shop_name}.', 'default' => 'Merci de votre confiance ! Retours sous {return_days} jours · {contact_phone}'],
                    'print_ticket_width' => ['type' => 'select', 'label' => 'Largeur du ticket de caisse', 'default' => '80mm', 'options' => ['58mm' => '58 mm (petite imprimante)', '80mm' => '80 mm (standard)']],
                    'print_auto_open' => ['type' => 'boolean', 'label' => 'Ouvrir la fenêtre d’impression automatiquement', 'default' => true],
                ],
            ],
            'media' => [
                'label' => 'Médiathèque',
                'icon' => 'image',
                'fields' => [
                    'media_compress_enabled' => ['type' => 'boolean', 'label' => 'Compresser automatiquement les images envoyées', 'help' => 'Réduit le poids des photos (pages plus rapides, moins d’espace disque).', 'default' => true],
                    'media_max_dimension' => ['type' => 'number', 'label' => 'Taille maximale d’une image (pixels, côté le plus long)', 'help' => '1600 convient à l’écran et au zoom produit. Les images plus grandes sont réduites.', 'default' => 1600, 'rules' => ['required', 'integer', 'min:300', 'max:6000']],
                    'media_quality' => ['type' => 'number', 'label' => 'Qualité de compression (40 à 100)', 'help' => '80–85 : excellent compromis qualité / poids.', 'default' => 82, 'rules' => ['required', 'integer', 'min:40', 'max:100']],
                    'media_convert_webp' => ['type' => 'boolean', 'label' => 'Convertir les JPEG et PNG en WebP', 'help' => 'Format moderne, 25 à 35 % plus léger à qualité égale. Compatible avec tous les navigateurs récents.', 'default' => true],
                    'media_max_upload_mb' => ['type' => 'number', 'label' => 'Poids maximal d’un fichier envoyé (Mo)', 'help' => 'Limité aussi par la configuration PHP du serveur (upload_max_filesize).', 'default' => 8, 'rules' => ['required', 'integer', 'min:1', 'max:50']],
                    'media_allow_documents' => ['type' => 'boolean', 'label' => 'Accepter aussi les documents (PDF, Word, Excel, CSV, texte)', 'default' => true],
                    'media_product_folders' => ['type' => 'boolean', 'label' => 'Classer les photos des produits dans un dossier à leur nom', 'help' => 'Les photos envoyées depuis une fiche produit sont rangées automatiquement dans la médiathèque.', 'default' => true],
                ],
            ],
            'emails' => [
                'label' => 'E-mails',
                'icon' => 'mail',
                'fields' => [
                    'mail_enabled' => ['type' => 'boolean', 'label' => 'Envoyer les e-mails automatiques', 'help' => 'Interrupteur général : désactivé, plus aucun e-mail de la boutique ne part (sauf le test et la réinitialisation de mot de passe).', 'default' => true],
                    'mail_mode' => ['type' => 'select', 'label' => 'Serveur d’envoi', 'help' => 'Choisissez « Serveur SMTP » pour tout régler ici, ou conservez la configuration du fichier .env.', 'default' => 'smtp', 'options' => ['smtp' => 'Serveur SMTP (réglé ci-dessous)', 'env' => 'Configuration du fichier .env']],
                    'mail_host' => ['show_when' => ['mail_mode' => 'smtp'], 'type' => 'text', 'label' => 'Serveur SMTP', 'help' => 'Ex. smtp.gmail.com, smtp.mailgun.org, mail.votredomaine.com.', 'default' => '', 'rules' => ['nullable', 'string', 'max:255']],
                    'mail_port' => ['show_when' => ['mail_mode' => 'smtp'], 'type' => 'number', 'label' => 'Port', 'help' => '587 (TLS) ou 465 (SSL).', 'default' => 587, 'rules' => ['required', 'integer', 'min:1', 'max:65535']],
                    'mail_encryption' => ['show_when' => ['mail_mode' => 'smtp'], 'type' => 'select', 'label' => 'Chiffrement', 'default' => 'tls', 'options' => ['tls' => 'TLS / STARTTLS (port 587)', 'ssl' => 'SSL (port 465)']],
                    'mail_username' => ['show_when' => ['mail_mode' => 'smtp'], 'type' => 'text', 'label' => 'Identifiant SMTP', 'default' => '', 'rules' => ['nullable', 'string', 'max:255']],
                    'mail_password' => ['show_when' => ['mail_mode' => 'smtp'], 'type' => 'secret', 'label' => 'Mot de passe SMTP', 'help' => 'Stocké chiffré, jamais réaffiché.', 'default' => ''],
                    'mail_from_address' => ['type' => 'email', 'label' => 'Adresse d’expédition', 'help' => 'Souvent la même que l’identifiant SMTP. Vide = adresse du fichier .env.', 'default' => '', 'rules' => ['nullable', 'email']],
                    'mail_from_name' => ['type' => 'text', 'label' => 'Nom de l’expéditeur', 'default' => '{shop_name}', 'rules' => ['nullable', 'string', 'max:120']],
                    'mail_admin_address' => ['type' => 'email', 'label' => 'Adresse qui reçoit les alertes', 'help' => 'Vide = e-mail de contact de la boutique.', 'default' => '', 'rules' => ['nullable', 'email']],
                    'mail_admin_new_order' => ['type' => 'boolean', 'label' => 'Alerte : nouvelle commande', 'default' => true],
                    'mail_admin_contact' => ['type' => 'boolean', 'label' => 'Alerte : nouveau message de contact', 'default' => true],
                    'mail_customer_order' => ['type' => 'boolean', 'label' => 'Client : confirmation de commande', 'help' => 'Envoyée seulement si le client a laissé une adresse e-mail.', 'default' => true],
                    'mail_customer_order_subject' => ['type' => 'text', 'label' => 'Objet de la confirmation', 'help' => 'Valeurs : {order_number}, {customer_name}, {total}, {shop_name}.', 'default' => 'Votre commande {order_number} est bien enregistrée', 'rules' => ['nullable', 'string', 'max:200']],
                    'mail_customer_order_intro' => ['type' => 'textarea', 'label' => 'Texte de la confirmation', 'default' => 'Merci {customer_name} ! Nous avons bien reçu votre commande {order_number}. Nous vous appelons très vite pour confirmer la livraison.'],
                    'mail_customer_status' => ['type' => 'boolean', 'label' => 'Client : suivi de commande', 'help' => 'Un e-mail quand le statut de la commande change.', 'default' => true],
                    'mail_customer_status_statuses' => ['type' => 'checkboxes', 'label' => 'Statuts qui déclenchent un e-mail', 'default' => ['confirmed', 'shipped', 'delivered', 'cancelled'], 'options' => collect(OrderStatus::cases())->mapWithKeys(fn (OrderStatus $status): array => [$status->value => $status->label()])->all()],
                    'mail_customer_status_subject' => ['type' => 'text', 'label' => 'Objet du suivi', 'help' => 'Valeurs : {order_number}, {status}, {customer_name}, {shop_name}.', 'default' => 'Commande {order_number} : {status}', 'rules' => ['nullable', 'string', 'max:200']],
                    'mail_customer_status_intro' => ['type' => 'textarea', 'label' => 'Texte du suivi', 'default' => 'Bonjour {customer_name}, le statut de votre commande {order_number} a changé : {status}.'],
                    'mail_signature' => ['type' => 'text', 'label' => 'Signature', 'default' => 'À bientôt, l’équipe {shop_name}', 'rules' => ['nullable', 'string', 'max:200']],
                ],
            ],
            'assistant' => [
                'label' => 'Assistant IA',
                'icon' => 'sparkles',
                'fields' => [
                    'ai_enabled' => ['type' => 'boolean', 'label' => 'Activer l’assistant sur la boutique', 'help' => 'Affiche le bouton de discussion aux visiteurs.', 'default' => false],
                    'ai_mode' => ['type' => 'select', 'label' => 'Type d’assistant', 'help' => 'Le robot intégré fonctionne sans API ni abonnement : il répond avec vos règles, votre catalogue, la livraison, les paiements et le suivi de commande. L’IA avec clé API comprend le langage libre mais est payante. Si l’IA est indisponible, le robot prend le relais.', 'default' => 'bot', 'options' => ['bot' => 'Robot intégré (sans API, gratuit)', 'llm' => 'IA avec clé API (Claude, OpenAI…)']],
                    'ai_provider' => ['show_when' => ['ai_mode' => 'llm'], 'type' => 'select', 'label' => 'Fournisseur d’IA (mode IA)', 'default' => 'anthropic', 'options' => ['anthropic' => 'Anthropic (Claude)', 'openai' => 'OpenAI ou compatible (Mistral, Groq, Ollama…)']],
                    'ai_api_key' => ['show_when' => ['ai_mode' => 'llm'], 'type' => 'secret', 'label' => 'Clé API', 'help' => 'Stockée chiffrée, jamais réaffichée. Vous pouvez aussi la définir dans le fichier .env (AI_API_KEY).', 'default' => ''],
                    'ai_model' => ['show_when' => ['ai_mode' => 'llm'], 'type' => 'text', 'label' => 'Modèle', 'help' => 'Vide = modèle conseillé du fournisseur (claude-haiku-5-5 ou gpt-4o-mini).', 'default' => ''],
                    'ai_base_url' => ['show_when' => ['ai_mode' => 'llm'], 'type' => 'text', 'label' => 'Adresse de l’API (facultatif)', 'help' => 'Pour un fournisseur compatible OpenAI (ex. https://api.mistral.ai/v1). Vide = adresse officielle.', 'default' => '', 'rules' => ['nullable', 'url', 'max:255']],
                    'ai_temperature' => ['show_when' => ['ai_mode' => 'llm'], 'type' => 'number', 'label' => 'Créativité (0 = factuel, 1 = créatif)', 'default' => 0.4, 'rules' => ['required', 'numeric', 'min:0', 'max:1']],
                    'ai_max_tokens' => ['show_when' => ['ai_mode' => 'llm'], 'type' => 'number', 'label' => 'Longueur maximale d’une réponse (tokens)', 'default' => 500, 'rules' => ['required', 'integer', 'min:100', 'max:4000']],
                    'ai_name' => ['type' => 'text', 'label' => 'Prénom de l’assistant', 'default' => 'Léa', 'rules' => ['required', 'max:40']],
                    'ai_title' => ['type' => 'text', 'label' => 'Sous-titre dans la fenêtre', 'default' => 'Assistante {shop_name}'],
                    'ai_avatar' => ['type' => 'image', 'label' => 'Photo ou avatar', 'help' => 'Vide = icône par défaut.', 'default' => ''],
                    'ai_launcher_label' => ['type' => 'text', 'label' => 'Texte du bouton flottant', 'default' => 'Besoin d’aide ?'],
                    'ai_position' => ['type' => 'select', 'label' => 'Position du bouton', 'default' => 'right', 'options' => ['right' => 'En bas à droite', 'left' => 'En bas à gauche']],
                    'ai_welcome' => ['type' => 'textarea', 'label' => 'Message d’accueil', 'default' => 'Bonjour ! Je suis {ai_name}, l’assistante de {shop_name}. Je peux vous conseiller, vérifier un prix ou un stock, suivre une commande… Comment puis-je vous aider ?'],
                    'ai_suggestions' => ['type' => 'list', 'label' => 'Questions suggérées', 'help' => 'Boutons proposés à l’ouverture de la conversation.', 'default' => [
                        ['text' => 'Quels sont vos délais de livraison ?'],
                        ['text' => 'Quels moyens de paiement acceptez-vous ?'],
                        ['text' => 'Pouvez-vous me conseiller un produit ?'],
                        ['text' => 'Où en est ma commande ?'],
                    ], 'fields' => ['text' => ['type' => 'text', 'label' => 'Question']]],
                    'ai_instructions' => ['show_when' => ['ai_mode' => 'llm'], 'type' => 'textarea', 'label' => 'Personnalité et consignes', 'help' => 'Ton, style, règles propres à votre boutique. L’assistant connaît déjà le catalogue, la livraison, les paiements et les pages du site.', 'default' => 'Tu es chaleureuse, claire et concise. Tu tutoies ou vouvoies comme le client. Tu proposes toujours une étape suivante (voir le produit, ajouter au panier, passer commande). Tu ne promets jamais de remise ni de délai qui ne figure pas dans les informations fournies.'],
                    'ai_site_guide' => ['type' => 'textarea', 'label' => 'Guide du site (comment le site fonctionne)', 'help' => 'Aide l’assistant à orienter les clients vers les bonnes pages. Adaptez-le si vous modifiez votre site.', 'default' => 'Boutique : /shop (tous les produits), /best-deals (bons plans), /new-arrivals (nouveautés), /brands (marques).
Panier et commande : /cart — le client ajoute des produits, saisit son téléphone (avec l’indicatif du pays), son adresse, choisit un moyen de paiement puis valide. Un code promo peut être saisi dans le panier.
Compte : /account — inscription et connexion avec le numéro de téléphone (l’e-mail est facultatif), historique des commandes, reçus imprimables.
Suivi de commande : /track-order — avec le numéro de commande et le téléphone.
Contact : /contact. Pages d’information : /page/returns (retours), /page/shipping (livraison), /page/terms (conditions).
Jeux et surprises : des codes promo, une roue de la chance et des offres peuvent apparaître en fenêtre sur le site.'],
                    'ai_extra_knowledge' => ['type' => 'textarea', 'label' => 'Connaissances supplémentaires (FAQ)', 'help' => 'Questions fréquentes, horaires, conseils d’utilisation des produits… tout ce que l’assistant doit savoir en plus.', 'default' => ''],
                    'bot_rules' => ['type' => 'list', 'label' => 'Questions / réponses du robot', 'help' => 'Mode robot : si le message contient un des mots-clés (séparés par des virgules), la réponse est envoyée. Vous pouvez utiliser {shop_name}. Plus il y a de règles, plus le robot est malin.', 'default' => [
                        ['keywords' => 'horaire, ouvert, ouverture, heure', 'reply' => 'Nous prenons les commandes en ligne 24h/24. Le service client répond {response_time}.'],
                        ['keywords' => 'promo, promotion, reduction, remise, code', 'reply' => 'Retrouvez nos bons plans sur la page /best-deals et tentez la roue de la chance sur le site !'],
                    ], 'fields' => [
                        'keywords' => ['type' => 'text', 'label' => 'Mots-clés (séparés par des virgules)'],
                        'reply' => ['type' => 'textarea', 'label' => 'Réponse'],
                    ]],
                    'bot_greeting_reply' => ['type' => 'textarea', 'label' => 'Réponse à « bonjour » (mode robot)', 'default' => 'Bonjour et bienvenue chez {shop_name} ! Je peux vous renseigner sur nos produits, la livraison, les paiements ou suivre votre commande.'],
                    'bot_thanks_reply' => ['type' => 'textarea', 'label' => 'Réponse à « merci » (mode robot)', 'default' => 'Avec plaisir ! N’hésitez pas si vous avez d’autres questions.'],
                    'bot_unknown_reply' => ['type' => 'textarea', 'label' => 'Réponse quand le robot ne comprend pas', 'default' => 'Je n’ai pas bien compris. Essayez avec le nom d’un produit, ou demandez-moi la livraison, les paiements, les retours ou le suivi de commande. Un conseiller peut aussi vous répondre.'],
                    'ai_know_catalog' => ['type' => 'boolean', 'label' => 'Connaît le catalogue (produits, prix, stock, variantes)', 'default' => true],
                    'ai_know_delivery' => ['type' => 'boolean', 'label' => 'Connaît la livraison et les moyens de paiement', 'default' => true],
                    'ai_know_pages' => ['type' => 'boolean', 'label' => 'Connaît les pages d’information (retours, conditions…)', 'default' => true],
                    'ai_know_orders' => ['type' => 'boolean', 'label' => 'Peut renseigner sur une commande', 'help' => 'Uniquement si le client donne son numéro de commande ET son téléphone, ou s’il est connecté à son compte.', 'default' => true],
                    'ai_products_in_context' => ['show_when' => ['ai_mode' => 'llm'], 'type' => 'number', 'label' => 'Produits transmis à l’IA à chaque question', 'default' => 8, 'rules' => ['required', 'integer', 'min:0', 'max:30']],
                    'ai_handoff_enabled' => ['type' => 'boolean', 'label' => 'Proposer de parler à un humain (WhatsApp)', 'help' => 'Utilise le numéro WhatsApp de l’onglet Contact.', 'default' => true],
                    'ai_handoff_label' => ['type' => 'text', 'label' => 'Texte du bouton « humain »', 'default' => 'Parler à un conseiller'],
                    'ai_fallback_message' => ['type' => 'textarea', 'label' => 'Message si l’assistant est indisponible', 'default' => 'Je rencontre un souci technique. Vous pouvez nous écrire depuis la page Contact ou sur WhatsApp : nous répondons très vite.'],
                    'ai_rate_limit' => ['type' => 'number', 'label' => 'Messages maximum par visiteur et par minute', 'default' => 10, 'rules' => ['required', 'integer', 'min:1', 'max:60']],
                    'ai_daily_limit' => ['show_when' => ['ai_mode' => 'llm'], 'type' => 'number', 'label' => 'Plafond de messages par jour (toute la boutique)', 'help' => '0 = illimité. Protège votre budget.', 'default' => 500, 'rules' => ['required', 'integer', 'min:0', 'max:100000']],
                    'ai_history_length' => ['type' => 'number', 'label' => 'Messages précédents retenus dans la conversation', 'default' => 12, 'rules' => ['required', 'integer', 'min:2', 'max:40']],
                    'ai_save_conversations' => ['type' => 'boolean', 'label' => 'Enregistrer les conversations (consultables dans l’administration)', 'default' => true],
                    'ai_retention_days' => ['type' => 'number', 'label' => 'Durée de conservation des conversations (jours)', 'help' => '0 = conservation illimitée.', 'default' => 90, 'rules' => ['required', 'integer', 'min:0', 'max:3650']],
                ],
            ],
            'marketing' => [
                'label' => 'Promotions et animations',
                'icon' => 'trend-up',
                'fields' => [
                    'announcement_enabled' => ['type' => 'boolean', 'label' => 'Afficher la barre d’annonces (haut du site)', 'default' => true],
                    'announcement_items' => ['type' => 'list', 'label' => 'Messages de la barre d’annonces', 'help' => 'Les messages défilent à tour de rôle. Valeurs automatiques possibles : {free_shipping_threshold}, {return_days}…', 'default' => [
                        ['icon' => 'truck', 'text' => 'Livraison offerte dès {free_shipping_threshold} d’achat', 'url' => '/shop'],
                        ['icon' => 'tag', 'text' => 'Jusqu’à -30 % sur nos bons plans', 'url' => '/best-deals'],
                        ['icon' => 'shield', 'text' => 'Paiement à la livraison ou Mobile Money', 'url' => ''],
                    ], 'fields' => ['icon' => ['type' => 'select', 'label' => 'Icône', 'options' => $icons], 'text' => ['type' => 'text', 'label' => 'Message'], 'url' => ['type' => 'text', 'label' => 'Lien (facultatif)']]],
                    'announcement_bg' => ['type' => 'color', 'label' => 'Couleur de fond de la barre', 'default' => '#0f4a1f'],
                    'announcement_color' => ['type' => 'color', 'label' => 'Couleur du texte de la barre', 'default' => '#ffffff'],
                    'announcement_ends_at' => ['type' => 'datetime', 'label' => 'Compte à rebours dans la barre (date de fin)', 'help' => 'Vide = pas de compte à rebours.', 'default' => ''],
                    'welcome_coupon_code' => ['type' => 'text', 'label' => 'Code promo offert à l’inscription d’un compte', 'help' => 'Créez le code dans Promotions, puis saisissez-le ici. Vide = aucun cadeau.', 'default' => ''],
                    'mobile_nav_enabled' => ['type' => 'boolean', 'label' => 'Barre de navigation fixe sur mobile', 'default' => true],
                    'sticky_cta_enabled' => ['type' => 'boolean', 'label' => 'Bouton « Ajouter au panier » fixe sur les fiches produit (mobile)', 'default' => true],
                    'whatsapp_float_enabled' => ['type' => 'boolean', 'label' => 'Bouton WhatsApp flottant', 'help' => 'Utilise le numéro WhatsApp de l’onglet Contact.', 'default' => true],
                    'whatsapp_message' => ['type' => 'text', 'label' => 'Message WhatsApp pré-rempli', 'default' => 'Bonjour {shop_name}, j’ai une question.'],
                    'confetti_enabled' => ['type' => 'boolean', 'label' => 'Confettis (commande validée, code appliqué, roue gagnante)', 'default' => true],
                    'recently_viewed_enabled' => ['type' => 'boolean', 'label' => 'Section « Récemment consultés »', 'default' => true],
                    'recent_sales_enabled' => ['type' => 'boolean', 'label' => 'Afficher les achats récents réels', 'help' => 'Ville et produit des dernières commandes, sans le nom du client.', 'default' => true],
                    'recent_sales_days' => ['type' => 'number', 'label' => 'Ancienneté maximale des achats affichés (jours)', 'default' => 7, 'rules' => ['required', 'integer', 'min:1', 'max:90']],
                    'low_stock_badge' => ['type' => 'number', 'label' => 'Afficher « plus que X en stock » à partir de', 'help' => '0 = jamais.', 'default' => 20, 'rules' => ['required', 'integer', 'min:0']],
                    'show_ratings' => ['type' => 'boolean', 'label' => 'Afficher les notes et avis des produits', 'default' => true],
                ],
            ],
        ];
    }

    /**
     * Every field indexed by key, across groups.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function fields(): array
    {
        return once(fn (): array => collect(self::groups())->flatMap(fn (array $group): array => $group['fields'])->all());
    }

    public static function field(string $key): ?array
    {
        return self::fields()[$key] ?? null;
    }

    /**
     * @return array<string, array<string, string>>
     */
    private static function linkFields(): array
    {
        return ['label' => ['type' => 'text', 'label' => 'Libellé'], 'url' => ['type' => 'text', 'label' => 'Adresse']];
    }
}
