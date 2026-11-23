# Ce qui reste

## À faire côté technique
- **Vérification du téléphone par SMS (OTP)** : aucun fournisseur SMS n'est branché, donc la propriété d'un numéro n'est pas prouvée. Mesure actuelle : un numéro non vérifié ne rattache pas une fiche client qui a déjà des commandes (il faut numéro de commande + montant).
- La réinitialisation de mot de passe ne marche que pour les comptes qui ont un e-mail.
- La CSP garde `'unsafe-inline'` pour les scripts (configuration embarquée dans les pages).
- Pas de fichier de verrouillage npm : les bibliothèques front viennent de CDN avec SRI ; l'audit des dépendances npm n'est pas possible.
- Compteurs d'événements des popups modifiables sans authentification ; l'inscription révèle si un e-mail ou un numéro existe déjà.
- Prendre le temps de tester l'assistant en mode IA avec une vraie clé (les tests utilisent des réponses simulées).
- Pas de pull request ouverte : tout est sur `feat/back-office`.

## Avant la mise en production
- `APP_DEBUG=false`, `ADMIN_PASSWORD` fort, `APP_URL` en https, sessions sécurisées.
- Brancher un vrai fournisseur d'e-mail pour la réinitialisation de mot de passe.
- Renseigner les réglages de la boutique (contact, livraison, paiement, documents imprimés).

## Dépend de l'utilisateur
- Choisir le fournisseur SMS (vérification de téléphone).
- Fournir une clé API si l'IA est souhaitée en plus du robot.
- Valider le contenu des règles du robot (« Paramètres › Assistant IA ») et des textes de la boutique.
