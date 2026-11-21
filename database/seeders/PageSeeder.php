<?php

namespace Database\Seeders;

use App\Models\Page;
use App\PageType;
use Illuminate\Database\Seeder;

/**
 * Default information pages and blog articles. Every text stays editable from /admin/pages;
 * {placeholders} are replaced by the shop settings when displayed.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            'shipping' => ['Politique de livraison', "## Délais\n\nNous livrons partout en {delivery_zone}. La livraison standard prend {delivery_delay}.\n\n## Tarifs\n\nLa livraison coûte {shipping_fee} et devient **gratuite dès {free_shipping_threshold} d’achat**.\n\n## Suivi\n\nNous vous appelons au {contact_phone} pour confirmer chaque commande avant l’expédition."],
            'returns' => ['Retours & remboursements', "## Délai de retour\n\nChaque article peut être retourné sous **{return_days} jours**, dans son état d’origine.\n\n## Comment faire ?\n\nÉcrivez-nous à {contact_email} ou appelez le {contact_phone} avec votre numéro de commande.\n\n## Remboursement\n\nLe remboursement est effectué avec le moyen de paiement utilisé lors de la commande."],
            'terms' => ['Conditions générales', "## Objet\n\nLes présentes conditions régissent les ventes réalisées par {shop_name}.\n\n## Prix\n\nLes prix sont indiqués toutes taxes comprises. Les frais de livraison sont précisés avant la validation de la commande.\n\n## Contact\n\n{contact_email} — {contact_phone}"],
            'privacy' => ['Politique de confidentialité', "## Données collectées\n\nNous collectons uniquement les informations nécessaires à la livraison de vos commandes : nom, téléphone, ville et adresse.\n\n## Utilisation\n\nVos données ne sont jamais revendues. Elles servent à traiter vos commandes et, si vous l’avez demandé, à vous envoyer notre newsletter.\n\n## Vos droits\n\nPour consulter ou supprimer vos données, écrivez à {contact_email}."],
            'about' => ['À propos de {shop_name}', "## Qui sommes-nous ?\n\n{shop_name} propose des produits d’entretien et de bien-être de marques reconnues, à prix déstockés.\n\n## Notre engagement\n\nDes produits authentiques, une livraison rapide en {delivery_zone} et des retours faciles sous {return_days} jours.\n\n## Nous contacter\n\n{contact_address} — {opening_hours}"],
        ];

        foreach ($pages as $slug => [$title, $body]) {
            Page::query()->firstOrCreate(
                ['type' => PageType::Page, 'slug' => $slug],
                ['title' => $title, 'body' => $body, 'is_published' => true],
            );
        }

        $articles = [
            ['10-astuces-maison-impeccable', 'Entretien', '10 astuces pour garder votre maison impeccable', 'Des routines simples qui vous font gagner du temps chaque semaine.', '#e6f3e1', 'spray'],
            ['routine-de-soins-douce', 'Soins', 'Construire une routine de soins douce au quotidien', 'Ce qu’il faut rechercher dans vos produits d’hygiène.', '#fdf1d6', 'pump'],
            ['indispensables-cuisine', 'Cuisine', 'Les indispensables de cuisine pour chaque foyer', 'Cuisinez et nettoyez plus facilement avec les bons outils.', '#e1eef6', 'utensils'],
            ['economiser-courses-du-mois', 'Économies', 'Économisez sur vos courses du mois', 'Offres combo, achats en gros et conseils malins.', '#fbe6ec', 'tag'],
            ['salle-de-bain-en-un-week-end', 'Maison', 'Organisez votre salle de bain en un week-end', 'Désencombrez et rafraîchissez avec quelques achats malins.', '#f1ece3', 'dispenser'],
            ['habitudes-plus-vertes', 'Éco', 'Des habitudes plus vertes pour la maison', 'De petits changements pour un grand impact.', '#e6f3e1', 'smile'],
        ];

        foreach ($articles as $i => [$slug, $tag, $title, $excerpt, $color, $icon]) {
            Page::query()->firstOrCreate(
                ['type' => PageType::Article, 'slug' => $slug],
                [
                    'title' => $title,
                    'tag' => $tag,
                    'excerpt' => $excerpt,
                    'body' => "{$excerpt}\n\n## À retenir\n\nRetrouvez tous les produits cités dans notre boutique, livrés en {delivery_zone}.",
                    'cover_color' => $color,
                    'icon' => $icon,
                    'is_published' => true,
                    'published_at' => now()->subDays(($i + 1) * 4),
                ],
            );
        }
    }
}
