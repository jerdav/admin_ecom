# Suivi EcomAdmin - 21/02/2026

## 1) Ce qui a ete fait

### Base projet et architecture
- Projet Laravel 12.x initialise et structure admin mise en place.
- Passage de SQLite vers MySQL effectue.
- Base MySQL `ecomadmin` migree avec succes.
- Workflow Git initialise.

### Authentification et roles
- Auth admin web operationnelle.
- Role `admin` et role `customer` utilises.
- Middleware de protection admin en place (`admin.web`).

### Parametres admin
- Page parametres decoupee en onglets:
  - `Boutique`
  - `Metier`
  - `Modules`
  - `Transporteurs`
- Textes admin harmonises en francais.
- Modules activables fonctionnels.

### Transporteurs
- CRUD transporteurs dans Parametres.
- Saisie des tarifs en euros cote admin.
- Conversion et stockage en centimes cote backend.
- Gestion seuil de livraison gratuite.
- Interface amelioree pour etre plus comprensible (indications utilisateur).

### Commandes et paiements
- Liste commandes admin.
- Detail commande admin.
- Changement de statut commande.
- Remboursement paiement depuis admin.
- Logique metier ajustee:
  - commande creee apres paiement valide,
  - paiement echoue conserve le panier.

### Catalogue - Categories
- CRUD Categories separe:
  - `index`
  - `create`
  - `edit`
- Slug categorie automatique depuis le nom.
- Activation/desactivation categorie.

### Catalogue - Produits
- Refonte en ecrans separes:
  - `index`
  - `create`
  - `edit`
- Slug produit automatique depuis le nom.
- SKU rendu nullable/optionnel.
- Gestion image:
  - photo principale (upload local),
  - galerie (upload multiple),
  - miniatures affichees,
  - suppression unitaire galerie via croix.
- Redirections:
  - apres create -> index produits,
  - apres edit -> index produits.

### UI/UX admin
- Theme global passe en gris sombre moderne (non noir complet).
- Ajustements de lisibilite.
- Suppression des fleches sur inputs numeriques.
- Placeholders sur champs prix/stock.

### Qualite et tests
- Tests feature et ecommerce executes regulierement.
- Etat final valide: suite de tests au vert.


## 2) Points techniques importants

- `storage:link` deja present.
- Cache routes/config a vider en cas de nouvelle route non visible:
  - `php artisan route:clear`
  - `php artisan config:clear`
- Attention SQLite: eviter de lancer des suites de tests en parallele dans ce setup (risque corruption).


## 3) Ce qu il reste a faire (prochaine roadmap)

## Priorite 1 - Front office reel
- Brancher le panier checkout sur les vrais produits (`product_id`) de maniere complete.
- Verification stricte du stock au checkout.
- Affichage des categories/produits cote boutique (front).

## Priorite 2 - Transport et livraison
- Connecter le choix transporteur dans tout le tunnel front.
- Afficher clairement frais de port + gratuit a partir de X.
- Historiser le transporteur choisi sur la commande.

## Priorite 3 - Clients admin
- Liste clients.
- Fiche client:
  - profil,
  - commandes,
  - total depense.

## Priorite 4 - Categories et produits (confort)
- Ajout recherche/filtres sur index produits/categories.
- Tri et pagination avancee.
- Option suppression image principale (pas seulement remplacement).
- Eventuel drag-and-drop ordre galerie.

## Priorite 5 - Stabilisation
- Seeders propres (admin initial + donnees demo optionnelles).
- Commande d initialisation projet reutilisable pour nouveaux clients.
- Verification securite:
  - limites upload,
  - validations complementaires,
  - permissions fines si besoin.


## 4) Commandes utiles (rappel)

```bash
php artisan migrate
php artisan storage:link
php artisan route:clear
php artisan config:clear
php artisan view:clear
php artisan test
```

