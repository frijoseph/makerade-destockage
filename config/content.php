<?php

/*
|--------------------------------------------------------------------------
| Banques de phrases de la rédaction automatique
|--------------------------------------------------------------------------
|
| Utilisées par App\Support\Content\DescriptionWriter pour composer des descriptions de produits et des
| articles à partir du nom saisi. Les phrases sont combinées au hasard (le hasard dépend du nom : le même
| nom donne le même texte, deux noms différents donnent deux textes différents). Les réglages de ton, les
| arguments de vente et la phrase de conclusion se règlent dans Paramètres › Rédaction automatique.
|
| Jetons disponibles : {name} nom complet, {brand} marque, {category} catégorie, {topic} thème,
| {object} ce dont on prend soin, {shop_name} nom de la boutique.
*/

return [
    'product' => [
        'intro' => [
            'friendly' => [
                '{name}{brand_part} : le réflexe malin pour {topic}, au quotidien et sans prise de tête.',
                'Envie de simplifier {topic} ? {name}{brand_part} est là pour vous faciliter la vie.',
                'Vous cherchez un bon produit de la catégorie {category} ? Découvrez {name}{brand_part}.',
                'Avec {name}{brand_part}, {topic} devient un vrai jeu d’enfant.',
                'On l’adore pour sa simplicité : {name}{brand_part} prend soin de {object} comme il faut.',
                '{name}{brand_part} rejoint votre quotidien pour {topic}, simplement.',
                'Un indispensable pour {topic} : {name}{brand_part}, à prix déstockage.',
                'Faites le bon choix pour {object} avec {name}{brand_part}.',
            ],
            'professional' => [
                '{name}{brand_part} est un produit de la catégorie {category}, conçu pour {topic}.',
                'Découvrez {name}{brand_part} : une solution fiable pour {topic}.',
                '{name}{brand_part} répond aux besoins courants pour {topic}, avec un usage simple et efficace.',
                'Pour {topic}, {name}{brand_part} offre un excellent rapport qualité-prix.',
                'Référence de la catégorie {category}, {name}{brand_part} accompagne {object} au quotidien.',
                '{name}{brand_part} : une réponse pratique pour {topic}.',
            ],
        ],
        'benefits_title' => ['Pourquoi vous allez l’adopter', 'Ses points forts', 'Ce qu’il faut retenir', 'Les petits plus'],
        'tips_title' => ['Conseils d’utilisation', 'Bien l’utiliser', 'Nos astuces', 'Pour de meilleurs résultats'],
        'closing' => [
            'friendly' => [
                'Ajoutez-le à votre panier sur {shop_name} et recevez-le directement chez vous.',
                'Profitez-en maintenant sur {shop_name} : stock limité sur les produits déstockés.',
                'Commandez sur {shop_name} en quelques clics, on s’occupe du reste.',
                'À découvrir dès maintenant sur {shop_name}, au meilleur prix.',
            ],
            'professional' => [
                'Disponible sur {shop_name} aux conditions de livraison et de paiement en vigueur.',
                'Commandez dès maintenant sur {shop_name}.',
                'Retrouvez ce produit et toute notre sélection sur {shop_name}.',
                'Offre soumise à disponibilité des stocks sur {shop_name}.',
            ],
        ],
    ],

    'article' => [
        'intro' => [
            'friendly' => [
                'Vous vous êtes déjà demandé comment aborder « {title} » sans y passer des heures ? Voici l’essentiel, expliqué simplement.',
                'Sujet du jour : « {title} ». On fait le point avec des conseils concrets, faciles à appliquer chez soi.',
                'Pas besoin d’être un expert pour réussir « {title} ». Suivez le guide.',
                '« {title} » : tout ce qu’il faut savoir pour {topic}, sans jargon.',
            ],
            'professional' => [
                'Cet article fait le point sur « {title} » et propose des repères pratiques pour {topic}.',
                '« {title} » : les points essentiels pour {topic}, présentés de façon claire et structurée.',
                'Dans ce guide consacré à « {title} », retrouvez des recommandations simples pour {topic}.',
            ],
        ],
        'why_title' => ['Pourquoi c’est important', 'Les bases à connaître', 'Comprendre l’essentiel', 'Ce qu’il faut savoir'],
        'why' => [
            'Bien {topic}, c’est gagner du temps et préserver durablement {object}. Quelques bons gestes suffisent souvent à faire la différence.',
            'Un entretien régulier évite que les problèmes s’installent : mieux vaut agir un peu et souvent que beaucoup et rarement.',
            'Le choix du bon produit compte autant que la méthode : adaptez toujours l’un à l’autre et lisez les indications de l’étiquette.',
            'Les professionnels le répètent : la régularité et le dosage correct valent mieux que la force ou les quantités excessives.',
            'Chaque foyer a ses habitudes, mais les principes de base restent les mêmes : un produit adapté, une bonne méthode et un peu de constance.',
        ],
        'tips_title' => ['Nos conseils pratiques', 'Les bons gestes', 'Comment s’y prendre', 'Notre méthode en quelques étapes'],
        'mistakes_title' => ['Les erreurs à éviter', 'À ne pas faire', 'Les pièges classiques'],
        'mistakes' => [
            'Dépasser les doses indiquées sur l’emballage',
            'Mélanger plusieurs produits sans lire les précautions d’emploi',
            'Stocker les produits à la chaleur ou en plein soleil',
            'Oublier d’aérer la pièce pendant et après l’utilisation',
            'Laisser les produits à portée des enfants et des animaux',
            'Négliger le rinçage ou l’essuyage final',
        ],
        'closing' => [
            'friendly' => [
                'Envie de passer à la pratique ? {link} et trouvez le produit qu’il vous faut.',
                'Vous avez maintenant toutes les cartes en main. {link}, on vous livre chez vous.',
            ],
            'professional' => [
                'Pour équiper votre foyer, {link} sur {shop_name}.',
                'Retrouvez les produits adaptés : {link} sur {shop_name}.',
            ],
        ],
        'excerpt' => [
            '{title} : nos conseils pratiques pour {topic}, simples à appliquer au quotidien.',
            'Tout savoir sur « {title} » : les bons gestes et les erreurs à éviter pour {topic}.',
            '{title} : le guide {shop_name} pour {topic}, étape par étape.',
        ],
    ],

    /*
    | Familles de produits : reconnues à partir du nom et de la catégorie (mots sans accent, en minuscules).
    */
    'families' => [
        'laundry' => [
            'match' => ['lessive', 'linge', 'detergent', 'adoucissant', 'javel', 'machine', 'detachant'],
            'topic' => 'prendre soin du linge', 'object' => 'votre linge',
            'benefits' => ['Nettoie en profondeur et ravive les couleurs', 'Utilisable en machine comme à la main', 'Laisse une agréable sensation de fraîcheur', 'Dosage économique : une petite quantité suffit', 'Pensé pour le lavage de tous les jours', 'S’adapte aux textiles du quotidien'],
            'tips' => ['Respectez la dose indiquée selon la quantité de linge et le degré de salissure.', 'Triez le linge par couleur avant le lavage pour préserver les teintes.', 'Rincez abondamment et faites sécher à l’air libre quand c’est possible.', 'Conservez le produit fermé, au sec et à l’abri de la chaleur.'],
        ],
        'dishes' => [
            'match' => ['vaisselle', 'cuisine', 'assiette', 'liquide vaisselle', 'dégraissant', 'degraissant', 'casserole'],
            'topic' => 'une vaisselle impeccable', 'object' => 'votre cuisine',
            'benefits' => ['Dégraisse efficacement la vaisselle du quotidien', 'Mousse généreuse pour un lavage facile', 'Rinçage simple pour une vaisselle brillante', 'Agréable à utiliser, jour après jour', 'Économique à l’usage', 'Idéal pour la cuisine de toute la famille'],
            'tips' => ['Diluez une petite quantité dans l’eau chaude pour un meilleur rendement.', 'Faites tremper les plats très gras quelques minutes avant de les laver.', 'Rincez à l’eau claire et laissez sécher à l’air libre.', 'Rangez le produit hors de portée des enfants.'],
        ],
        'cleaning' => [
            'match' => ['menager', 'entretien', 'nettoyant', 'sol', 'surface', 'multi', 'vitre', 'desinfectant', 'detergent', 'javel'],
            'topic' => 'l’entretien de la maison', 'object' => 'votre intérieur',
            'benefits' => ['Nettoie et rafraîchit les surfaces du quotidien', 'Facile à appliquer, sans effort', 'Laisse une agréable odeur de propre', 'Efficace sur les saletés courantes', 'Convient à un usage régulier', 'Un format pratique pour toute la maison'],
            'tips' => ['Testez d’abord sur une petite zone peu visible.', 'Appliquez, laissez agir quelques instants, puis essuyez avec un chiffon propre.', 'Aérez la pièce pendant l’utilisation.', 'Ne mélangez jamais deux produits d’entretien différents.'],
        ],
        'bathroom' => [
            'match' => ['bain', 'toilette', 'wc', 'sanitaire', 'douche', 'salle de bain', 'papier'],
            'topic' => 'une salle de bain nette et fraîche', 'object' => 'votre salle de bain',
            'benefits' => ['Entretient les sanitaires et la salle de bain', 'Laisse une sensation de fraîcheur durable', 'Simple et rapide à utiliser', 'Un incontournable de la maison', 'Pratique pour un usage quotidien', 'Format adapté à toute la famille'],
            'tips' => ['Utilisez-le régulièrement pour éviter les dépôts.', 'Rincez bien après application.', 'Aérez la pièce après usage.', 'Conservez-le dans un endroit sec.'],
        ],
        'hygiene' => [
            'match' => ['hygiene', 'desinfect', 'savon', 'gel', 'mains', 'antibacterien', 'dentifrice', 'brosse'],
            'topic' => 'une bonne hygiène au quotidien', 'object' => 'toute la famille',
            'benefits' => ['Pour une hygiène simple et régulière', 'Agréable sur la peau lors d’un usage normal', 'Pratique à emporter ou à garder à la maison', 'Utilisation facile pour petits et grands', 'Un geste quotidien rapide', 'Format économique pour la famille'],
            'tips' => ['Utilisez-le plusieurs fois par jour, notamment avant les repas.', 'Frottez bien, puis rincez ou laissez sécher selon le produit.', 'Évitez le contact avec les yeux.', 'Tenez-le hors de portée des jeunes enfants.'],
        ],
        'care' => [
            'match' => ['soin', 'cheveux', 'corps', 'beaute', 'creme', 'shampo', 'cosmet', 'visage', 'peau', 'lotion', 'parfum', 'deodorant'],
            'topic' => 'prendre soin de soi', 'object' => 'votre peau et vos cheveux',
            'benefits' => ['Un soin agréable à utiliser chaque jour', 'Texture facile à appliquer', 'Pensé pour le bien-être au quotidien', 'Format pratique pour la maison comme en déplacement', 'S’intègre simplement à votre routine', 'Une attention douce pour toute la famille'],
            'tips' => ['Appliquez en massant délicatement pour une meilleure répartition.', 'Respectez la quantité recommandée sur l’emballage.', 'En cas de peau sensible, faites un essai sur une petite zone.', 'Conservez à l’abri de la chaleur et de la lumière.'],
        ],
        'storage' => [
            'match' => ['rangement', 'boite', 'panier', 'organis', 'maison', 'seau', 'bassine', 'plastique', 'etagere', 'cintre'],
            'topic' => 'mieux organiser la maison', 'object' => 'votre intérieur',
            'benefits' => ['Pratique pour ranger et organiser', 'Solide et facile à entretenir', 'Un format pensé pour le quotidien', 'Gagne de la place dans la maison', 'Simple à utiliser, au salon comme à la cuisine', 'Un bon rapport qualité-prix'],
            'tips' => ['Nettoyez-le régulièrement avec un chiffon humide.', 'Ne dépassez pas la charge conseillée.', 'Placez-le à l’abri de la chaleur excessive.', 'Pensez à l’étiqueter pour retrouver vos affaires plus vite.'],
        ],
    ],

    'generic' => [
        'topic' => 'simplifier le quotidien', 'object' => 'votre foyer',
        'benefits' => ['Pratique et facile à utiliser', 'Un excellent rapport qualité-prix', 'Pensé pour le quotidien de toute la famille', 'Format adapté à un usage régulier', 'Une valeur sûre pour la maison', 'Disponible à prix déstockage'],
        'tips' => ['Lisez attentivement les indications figurant sur l’emballage.', 'Conservez le produit dans un endroit sec, à l’abri de la chaleur.', 'Tenez-le hors de portée des enfants.', 'Refermez soigneusement après chaque utilisation.'],
    ],
];
