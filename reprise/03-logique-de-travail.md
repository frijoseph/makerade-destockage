# Logique de travail

## Conventions du projet
- Aucune valeur métier en dur : tout se paramètre dans l'admin (`SettingsSchema`).
- SweetAlert2 pour toutes les notifications et confirmations (jamais `alert()` / `confirm()`).
- Tableaux CRUD simples en modale (modèle : `resources/views/admin/coupons/index.blade.php`, attribut `data-crud`).
- Mobile d'abord : vérifier à 375×812.
- Lire `CLAUDE.md` (règles Laravel Boost) : Pint avant de finir, tests ciblés, pas de nouvelle dépendance sans accord, pas de documentation créée sans demande.

## Commandes
- Dans l'environnement Claude, `php` n'est **pas** dans le PATH de Bash : utiliser PowerShell.
  - Tests : `php artisan test --compact` (≈ 40 s) ; un fichier : `php artisan test --compact tests/Feature/ChatBotTest.php`.
  - Style : `vendor\bin\pint --dirty --format agent`.
- Base de développement MySQL (root, mot de passe défini dans `.env`) ; tests en SQLite mémoire.
- Si un changement front ne s'affiche pas : `npm run build` ou `npm run dev`.

## Pièges rencontrés
- Dans Bash, les heredocs contenant des guillemets mixtes plantent parfois : écrire un script Python dans le dossier temporaire de session et l'exécuter.
- Fichiers en CRLF : les scripts de patch doivent conserver les fins de ligne (Git avertit « CRLF will be replaced », sans conséquence).
- Les migrations doivent rester portables SQLite/MySQL (pas de `UPDATE … JOIN`).
- Les réponses JSON échappent les accents : comparer sur `json('reply')` plutôt que sur le texte brut.
- Se connecter dans le navigateur de test : si `form.submit()` n'aboutit pas, poster via `fetch` puis recharger.
- Compte admin de test : `admin@makerade.test` / `password`. Nettoyer les données de test créées dans la base de développement.
