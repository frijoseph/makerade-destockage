<?php

namespace App\Support;

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
                    'theme_color' => ['type' => 'color', 'label' => 'Couleur du navigateur mobile', 'default' => '#1f7a34'],
                    'delivery_zone' => ['type' => 'text', 'label' => 'Zone de livraison (en-tête)', 'default' => 'Côte d’Ivoire'],
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
                    'brands_title' => ['type' => 'text', 'label' => 'Titre du bandeau des marques', 'default' => 'Nos marques de confiance'],
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
                    'header_menu' => ['type' => 'list', 'label' => 'Menu principal', 'help' => 'Laissez vide pour afficher automatiquement les catégories actives.', 'default' => [], 'fields' => self::linkFields()],
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
                    'footer_credits' => ['type' => 'markdown', 'label' => 'Crédits (bas de page)', 'default' => 'Photos d’ambiance : [StockSnap](https://stocksnap.io) et [Rawpixel](https://www.rawpixel.com) (CC0). Photos de produits : [Open Beauty Facts](https://world.openbeautyfacts.org) (CC BY-SA 3.0). Marques citées à titre d’illustration.'],
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
