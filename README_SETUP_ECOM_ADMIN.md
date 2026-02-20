# Plan d'implémentation - Administration e-commerce générique (backend only)

## Objectif
Construire une administration Laravel réutilisable pour lancer rapidement plusieurs sites e-commerce simples, sans développer le frontend maintenant.

## Portée
- Inclus: back-office, configuration, modules activables, rôles/permissions, audit, tests backend.
- Exclu (pour l’instant): thème, design, pages frontend publiques.

## Principes d’architecture
1. Éviter le code en dur: privilégier paramètres en base.
2. Garder un noyau métier stable: commandes, paiements, livraison, clients.
3. Isoler les options variables via feature flags et services.
4. Préparer l’ajout d’un frontend plus tard via API/services clairs.

## Roadmap (2 semaines)

### Phase 1 - Fondation technique (J1-J2)
- Créer `config/ecommerce.php` pour les valeurs par défaut.
- Définir les services métier:
  - `OrderService`
  - `PaymentService`
  - `ShippingService`
  - `SettingService`
- Standardiser l’organisation:
  - `app/Services/Ecommerce/...`
  - `app/DTO/...` (si nécessaire)
  - Policies pour actions sensibles.

### Phase 2 - Configuration dynamique (J3-J4)
- Migration `settings`:
  - `id`
  - `key` (unique)
  - `value` (text/json)
  - `type` (string: `string|int|bool|json`)
  - `group` (nullable)
  - timestamps
- Créer modèle `Setting` + `SettingService` avec cache.
- Prévoir fallback vers `config/ecommerce.php` si clé absente.

### Phase 3 - Modules activables (J5-J6)
- Migration `feature_flags`:
  - `id`
  - `code` (unique)
  - `enabled` (bool)
  - `scope` (nullable, ex: `global`)
  - timestamps
- Exemples de flags:
  - `payment.stripe`
  - `payment.paypal`
  - `shipping.flat_rate`
  - `mail.order_notifications`
- Brancher les flags dans les services (pas dans les contrôleurs).

### Phase 4 - Provisioning projet (S2 J1-J2)
- Créer commande Artisan:
  - `php artisan shop:init {project}`
- Cette commande doit:
  - créer/mettre à jour les réglages initiaux
  - créer l’utilisateur admin principal
  - appliquer les rôles de base
  - initialiser les feature flags par défaut
  - seed minimal (statuts commande, pages légales back-office, etc.)

### Phase 5 - Sécurité admin (S2 J3-J4)
- Rôles recommandés:
  - `super_admin`
  - `manager`
  - `editor`
  - `support`
- Implémenter permissions par domaine (produits, commandes, remboursements, réglages).
- Migration `audit_logs`:
  - `id`
  - `user_id`
  - `action`
  - `entity_type`
  - `entity_id`
  - `before` (json nullable)
  - `after` (json nullable)
  - timestamps
- Logger les actions sensibles:
  - changement de prix
  - changement de statut commande
  - remboursement
  - modification des settings critiques.

### Phase 6 - Qualité & exploitation (S2 J5)
- Tests backend minimum:
  - création de commande
  - transitions de statut
  - paiement simulé (mock)
  - envoi d’email transactionnel
- Checklist production:
  - queue worker actif
  - cron Laravel actif
  - SMTP configuré
  - logs centralisés
  - sauvegarde DB planifiée

## Ordre de build conseillé
1. `settings`
2. `feature_flags`
3. `shop:init`
4. RBAC + `audit_logs`
5. tests automatisés

## Conventions recommandées
- Ne pas appeler directement des SDK externes depuis les contrôleurs.
- Contrôleurs fins, logique dans services.
- Toutes les valeurs métier modifiables passent par `SettingService`.
- Les modules passent par `FeatureFlagService`.

## Commandes utiles (exemple)
```bash
php artisan make:model Setting -m
php artisan make:model FeatureFlag -m
php artisan make:model AuditLog -m
php artisan make:command ShopInitCommand
php artisan migrate
php artisan test
```

## Risques à surveiller
- Explosion de conditions `if(flag)` partout: centraliser dans services.
- Dérive des settings: documenter chaque clé (`key`, type, défaut, impact).
- Permissions trop larges: tester chaque rôle sur actions critiques.

## Définition de “prêt à cloner”
Le socle est prêt quand:
1. Un nouveau projet se lance via `shop:init`.
2. Les réglages critiques sont en base et éditables.
3. Les rôles limitent correctement les actions.
4. Les tests smoke backend passent.
5. La checklist de prod est validée.
