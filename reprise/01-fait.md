# Ce qui est réalisé

Application Laravel 13 / PHP 8.4, MySQL en local (SQLite en mémoire pour les tests), Blade, JS/CSS maison dans `public/`.

## Principe directeur
Rien n'est figé dans le code : toute valeur métier est un réglage modifiable dans l'administration (`app/Support/SettingsSchema.php`, lu via `Setting::get`, `ShopSettings` / `$shop` dans les vues). Un champ peut dépendre d'un autre avec `show_when`.

## Boutique
- Catalogue, catégories, marques (marques de confiance paramétrables : affichage, titre, nombre, vitesse), fiche produit avec galerie (image principale + secondaires) et **variantes** (stock, prix, photo par variante ; entrées virtuelles `slug~variantId` dans `Catalog::clientCatalog`).
- Menu avec « Tous les produits » ; barre d'annonces et en-tête figés ensemble (`.topstack`) ; couleur principale (`theme_color`) appliquée partout, y compris notifications et assistant.
- Panier, codes promo, zones de livraison, paiement (espèces à la livraison, mobile money…), **commande sans compte** (mention paramétrable), page de confirmation pensée pour mobile (`pages/order-confirmation.blade.php`, styles `.ok-*` dans `promo.css`).
- Popups, roue de la chance, newsletter (bandeau avec téléphone), notifications d'achat.
- Téléphone prioritaire sur l'e-mail, indicatif par défaut 225, stocké au format E.164 (`App\Support\Phone`, composant `x-phone-input`).
- Espace client : inscription/connexion par téléphone, historique, reçus imprimables, rattachement d'anciennes commandes par numéro + montant (`AccountLinker`).

## Back-office (`/admin`)
- Tableau de bord, commandes (aperçu rapide, saisie manuelle, impression bon de livraison / reçu / ticket 58-80 mm : réglages « Documents imprimés »), clients, produits (import/export Excel avec aperçu, implémentation maison `App\Support\Spreadsheet`), catégories, marques, stock (mouvements par variante), codes promo, popups et roue, messages, pages et blog, paramètres par groupes.
- Menu latéral repliable en icônes (état mémorisé), profil en haut à droite avec menu (Mon profil, Déconnexion), page `/admin/profile` (nom, e-mail, mot de passe), en-tête opaque.
- Tableaux CRUD simples en modale, SweetAlert2 partout (`window.MKUI`).

## Médiathèque (admin › Médiathèque, Paramètres › Médiathèque)
- Dossiers (créer, renommer, supprimer : les fichiers survivent, ils repassent « sans dossier »), fichiers (envoi multiple par glisser-déposer, renommer, déplacer, copier le lien, supprimer ; suppression refusée tant qu'un produit utilise le fichier), recherche, téléchargement d'un dossier en ZIP. Tables `media_folders` / `media_files`.
- Compression : dans le navigateur avant l'envoi (une photo de 5 Mo passe sous la limite PHP de 2 Mo) puis côté serveur avec GD (`app/Support/Media/ImageOptimizer.php` : côté max, qualité, conversion WebP, orientation EXIF, garde-fou mémoire). Bouton « Optimiser » pour recompresser un dossier (format conservé pour ne pas casser les liens produits). Tous les réglages sont dans Paramètres › Médiathèque.
- Fiche produit : les photos envoyées sont compressées et rangées automatiquement dans un dossier au nom du produit (`MediaLibrary::storeForProduct`) ; boutons « Choisir dans la médiathèque » pour la photo principale et les secondaires (sélecteur `window.MKMedia.pick`). Les fichiers de la médiathèque ne sont jamais supprimés avec un produit.

## E-mails automatiques (Paramètres › E-mails)
- Serveur SMTP réglé dans l'admin (hôte, port, chiffrement, identifiant, mot de passe chiffré, expéditeur) ou configuration du `.env` ; appliqué à chaque requête par `App\Support\Mail\MailSettings`. Bouton « Envoyer un e-mail de test ».
- `Notifier` (app/Support/Mail) : alerte à la boutique (nouvelle commande, nouveau message de contact), confirmation de commande et suivi de statut au client (seulement s'il a une adresse e-mail). Chaque envoi a son interrupteur et ses textes modifiables ; une panne SMTP n'empêche jamais une commande. Gabarit unique `resources/views/emails/shop.blade.php` (classe `ShopMail`).

## Menu de l'en-tête
- Menu principal paramétrable avec sous-menus (une ligne « Libellé | /adresse » par lien, ou le mot `catégories`). Sans menu personnalisé : les N premières catégories (`header_menu_auto_limit`, défaut 5) et le reste dans un sous-menu « Plus ». Tiroir mobile en accordéon.

## Assistant de discussion
- Deux modes (réglage `ai_mode`) : **robot intégré** (défaut, sans API : règles de mots-clés `bot_rules`, livraison, paiement, retours, contact, produits, suivi de commande ; `App\Support\Assistant\ChatBot`) ou **IA avec clé API** (Anthropic ou compatible OpenAI ; `LlmClient`, `KnowledgeBase`). Si l'IA est indisponible, le robot répond.
- Suivi de commande seulement avec numéro + téléphone correspondant, ou compte connecté. Conversations consultables en admin, purge planifiée.

## Sécurité (audit fait)
En-têtes (CSP, HSTS, anti-framing), SRI sur les scripts CDN, verrouillage de connexion par compte, sessions chiffrées, garde-fou mot de passe admin en production, formules neutralisées dans l'export CSV, liens `javascript:` bloqués, limitation de débit sur les routes sensibles.

## Tests
193 tests PHPUnit passent (`php artisan test --compact`), Pint propre.
