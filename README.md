# DropShop — Plateforme e-commerce dropshipping

Application **full-stack** : backend **Laravel 11 + MySQL 8.0** (API REST + Sanctum, Stripe, import AliExpress) et frontend **React 18 + TypeScript + Vite + Tailwind**, le tout optimisé pour un hébergement **cPanel mutualisé** (sans Redis ni workers persistants). Le SPA est compilé dans `public/spa/` et servi par Laravel.

**État : Phase 1 + Phase 2 (features cœur) — backend + frontend complets.** ~197 fichiers, syntaxe validée (parser PHP réel + esbuild, 0 erreur). Reste l'exécution réelle (composer/npm) et le débogage runtime côté machine cible.

## Stack & adaptations cPanel

| Composant | Choix | Adaptation cPanel |
|-----------|-------|-------------------|
| Backend | Laravel 11 (PHP 8.2+) | — |
| Base de données | MySQL 8.0 InnoDB, utf8mb4 | natif cPanel |
| Cache | APCu (`CACHE_STORE=apcu`) | pas de Redis, fallback file/db |
| Sessions | MySQL (`SESSION_DRIVER=database`) | cross-device |
| Queue | MySQL (`QUEUE_CONNECTION=database`) | exécutée par cron |
| Auth API | Sanctum (tokens) + 2FA TOTP (google2fa) | — |
| Paiements | stripe/stripe-php + Stripe Elements | webhook hors CSRF |
| Import produits | Guzzle + symfony/dom-crawler | scraping JSON-LD/OG |
| Frontend | React 18, TS, Vite, Tailwind, React Query, Zustand | build → `public/spa` |

## Installation

```bash
# Backend
composer install
cp .env.example .env
php artisan key:generate
# configurer DB_*, STRIPE_*, VITE_STRIPE_PUBLIC_KEY dans .env
php artisan migrate --seed

# Frontend
npm install
npm run build            # compile le SPA dans public/spa
# ou en dev : npm run dev (proxy /api -> http://localhost:8000)

php artisan serve
php artisan test
```

Admin créé par le seeder : `admin@dropshop.local` / `ChangeMe!2026`.

## Fonctionnalités (Phase 1)

**Backend** — Auth + 2FA TOTP, catalogue (filtres + recherche fulltext), espace vendeur (CRUD produits, import AliExpress, dashboard/analytics, commandes, paramètres), panier invité/connecté, checkout (frais de port par vendeur, PaymentIntent localisé), paiement Stripe idempotent + webhook signé, création d'une commande par vendeur, emails transactionnels, jobs (commande AliExpress avec remboursement auto, sync stock).

**Frontend (SPA React)** — catalogue + tri/filtres, fiche produit (galerie, variantes, ajout panier), page boutique, panier (quantités, coupon), checkout en 2 étapes avec **Stripe Elements**, connexion/inscription (+ 2FA), espace client (commandes, suivi), espace vendeur complet (KPIs, produits, import, commandes + statut/tracking, paramètres & zones de livraison).

**Phase 2 (cœur)** — avis produits (achat vérifié, recalcul de note), wishlist (favoris), espace admin (utilisateurs, vérification KYC vendeur, litiges), import AliExpress fiabilisé (extraction `window.runParams` + variantes), jeu de données de démonstration (`migrate --seed` génère 3 boutiques, ~24 produits, comptes de test).

## Architecture

```
app/
├── Http/Controllers/Api   10 contrôleurs · Requests · Resources · Middleware
├── Services/              Pricing, Shipping, TwoFactor, Stripe,
│                          AliExpressScraper, AliExpressOrder, Order
├── Jobs/ · Mail/ · Models/ (20)
database/migrations/       23 migrations
routes/api.php             ~40 endpoints (CDC §3.3)
tests/Feature/             Auth, Catalogue, Cart, Health

resources/js/              SPA React (TypeScript)
├── lib/        api (axios+interceptors), queryClient, format
├── stores/     auth (Zustand)
├── hooks/      useCart, useProducts
├── components/ Layout, Navbar, ProductCard, ProtectedRoute, Loader, Alert
└── pages/      Home, ProductDetail, Shop, Cart, Checkout, Login, Register,
                account/(Orders, OrderDetail), seller/(Dashboard, Products,
                ImportProduct, Orders, Settings), NotFound
vite.config.ts · tailwind.config.js · tsconfig.json
```

## Déploiement cPanel

DocumentRoot idéalement sur `public_html/public` ; sinon le `.htaccess` racine redirige vers `public/`. `npm run build` doit être lancé (en local ou CI) avant déploiement pour générer `public/spa/`. Variables cPanel : `DB_*`, `STRIPE_*`, `VITE_STRIPE_PUBLIC_KEY`, `SENDGRID_API_KEY`/`MAIL_*`, `APP_KEY`, `APP_URL`.

### Cron (cPanel > Cron Jobs)

```bash
* * * * * cd /home/username/public_html && php artisan schedule:run >> /dev/null 2>&1
```

Déclenche : `queue:work` (5 min), `aliexpress:sync` (4 h), `carts:cleanup` (horaire), purge cache (quotidien).

### CI/CD (GitHub Actions + GitVersion)

- `.github/workflows/ci.yml` — sur chaque PR : Pint, PHPStan (niveau 5), tests PHPUnit (SQLite mémoire), type-check + build du SPA.
- `.github/workflows/deploy-cpanel.yml` — sur `main` : tests → build React → déploiement SSH cPanel (composer `--no-dev`, `migrate --force`, caches, upload `public/spa`) → smoke test `/api/health`.
- Versioning SemVer via `GitVersion.yml`. Détails et secrets requis : voir `DEPLOYMENT.md`.

## Comptes de démonstration (après `migrate --seed`)

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Admin | `admin@dropshop.local` | `ChangeMe!2026` |
| Vendeur | `vendeur1@dropshop.local` (1-3) | `Password123!` |
| Client | `client@dropshop.local` | `Password123!` |

## Reste à faire

- **Exécution & débogage runtime** : lancer `composer install`, `php artisan migrate --seed`, `npm install && npm run build` sur la machine cible (impossible dans l'environnement de génération).
- **Fulfillment AliExpress automatique** (`AliExpressOrderService::placeViaApi`) — nécessite l'API officielle ou un navigateur headless ; le mode manuel est opérationnel.
- **Import AliExpress en production** : le site étant anti-bot/JS, prévoir un fetch headless (`config aliexpress.fetcher`).
- Payouts vendeurs (Stripe Connect), multi-devise avancée, tests E2E (Playwright/Cypress).
