# CLAUDE.md

Ce fichier fournit des instructions à Claude Code (claude.ai/code) pour travailler dans ce dépôt.

## Présentation du projet

**ecomadmin** est un scaffold d'administration e-commerce basé sur Laravel 12.x, conçu pour être cloné et personnalisé pour de nouveaux projets e-commerce. Il comprend un panneau d'administration web (Blade + CSS/JS statiques custom) et une couche API JSON (authentifiée via Sanctum). Vite + Tailwind CSS 4 sont présents mais réservés au futur front client — le panneau admin n'utilise pas `@vite` et charge ses propres assets depuis `public/css/admin.css` et `public/js/admin.js`.

## Commandes de développement

```bash
# Initialisation complète du projet (dépendances, clé, migrations, assets)
composer setup

# Lancer tous les services en parallèle (serveur Laravel, queue, logs, Vite)
composer dev

# Lancer la suite de tests
composer test

# Lancer un fichier de test précis
php artisan test tests/Feature/AdminProductsTest.php

# Lancer une méthode de test précise
php artisan test --filter=nom_de_la_methode

# Assets frontend
npm run dev        # Serveur Vite avec HMR
npm run build      # Build de production

# Style de code
./vendor/bin/pint  # Laravel Pint (correction automatique)

# Base de données
php artisan migrate
php artisan migrate:fresh --seed

# Vidage du cache
php artisan config:clear && php artisan route:clear && php artisan view:clear
```

## Architecture

### Couche service (`app/Services/Ecommerce/`)

Toute la logique métier est dans des services dédiés — les contrôleurs sont volontairement minces. Services principaux :

- **OrderService** — Création de commandes, transitions de statut, recalcul des totaux
- **PaymentService** — Traitement des paiements et remboursements
- **CartService** — Gestion de la session panier
- **CheckoutService** — Orchestration du flux panier → commande → paiement
- **SettingService** — Paramètres clé/valeur dynamiques avec cache et audit
- **FeatureFlagService** — Gestion des feature flags (ex. `payment.stripe`, `payment.paypal`)
- **AuditLogService** — Enregistrement des actions importantes dans la table `audit_logs`

### Routes

- `routes/web.php` — Routes du panneau admin, toutes protégées par le middleware `auth`
- `routes/api.php` — Routes API client et admin, authentifiées via Sanctum

### Contrôleurs

- `app/Http/Controllers/Admin/` — Contrôleurs web pour le CRUD admin (Products, Categories, Orders, Settings, ShippingProviders)
- `app/Http/Controllers/Api/Admin/` — Contrôleurs API (PaymentController)

### Décisions d'architecture importantes

- **Les montants sont en centimes** (entiers) partout — `price_cents`, `amount_cents` — pour éviter les problèmes de virgule flottante. Formater à l'affichage dans les vues/helpers.
- **Les feature flags** sont stockés dans la table `feature_flags` et gérés via `FeatureFlagService`. Vérifier les flags avant d'activer les passerelles de paiement ou autres modules optionnels.
- **Les paramètres** sont des lignes clé/valeur dans la table `settings` avec une colonne `type` pour le cast. Le `SettingService` les met en cache.
- **L'audit trail** est assuré par `AuditLogService` + la table `order_status_histories` pour les transitions de statut de commande.
- **Les rôles** (`admin` et `customer`) sont stockés directement dans la colonne `users.role` (pas de table de rôles séparée).

### Tests

Les tests utilisent une base SQLite en mémoire (`phpunit.xml` définit `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`). Les tests de fonctionnalité couvrent le CRUD admin et les endpoints API ; les tests de services sont dans `tests/Feature/Ecommerce/`.

### Configuration e-commerce

Les valeurs par défaut sont dans `config/ecommerce.php` (devise, taux de TVA, valeurs par défaut des feature flags, rôles utilisateur). Les surcharges à l'exécution proviennent de la table `settings` via `SettingService`.
