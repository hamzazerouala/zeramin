# Déploiement — DropShop

Versioning **GitVersion** (SemVer), CI/CD **GitHub Actions**, hébergement **cPanel** via SSH.

## Branches

| Branche | Rôle | Déploiement |
|---------|------|-------------|
| `main` | production | auto → cPanel (workflow `deploy-cpanel.yml`) |
| `staging` | pré-prod / QA | manuel |
| `develop` | intégration | CI uniquement |
| `feature/*` | développement | CI sur PR |

## Workflow

1. `feature/xxx` → PR vers `develop` → CI (`ci.yml` : Pint, PHPStan, tests, build SPA).
2. `develop` → `staging` pour QA.
3. `staging` → `main` → **déploiement auto** : tests → build React → SSH cPanel (`git reset`, `composer install --no-dev`, `migrate --force`, caches, upload `public/spa`) → smoke test `/api/health`.

```bash
git checkout -b feature/ma-feature
# ... commits ...
git push origin feature/ma-feature      # ouvre une PR

git checkout main && git merge develop --no-ff && git push origin main   # release
```

## Secrets GitHub à configurer

`Settings > Secrets and variables > Actions` :

```
CPANEL_HOST            server123.hostingxyz.com
CPANEL_USER            username
CPANEL_PORT            22
CPANEL_SSH_KEY         <clé privée SSH>
MAINTENANCE_SECRET     <token aléatoire>
APP_URL                https://yourdomain.com
VITE_STRIPE_PUBLIC_KEY pk_live_xxxx
```

Générer la clé SSH :

```bash
ssh-keygen -t ed25519 -f ./cpanel_deploy -N ""
# clé publique -> ~/.ssh/authorized_keys du compte cPanel
# clé privée   -> secret GitHub CPANEL_SSH_KEY
```

## Rollback

```bash
# Sur le serveur
cd /home/username/public_html
git reset --hard <tag_precedent>
php artisan migrate:rollback
php artisan config:cache
```

## Prérequis serveur cPanel

- PHP 8.2+ (extensions : mbstring, pdo_mysql, curl, intl, bcmath, apcu)
- MySQL 8.0+
- Accès SSH activé + clé publique installée
- DocumentRoot du domaine sur `public_html/public` (recommandé)
- Cron : `* * * * * cd ~/public_html && php artisan schedule:run >> /dev/null 2>&1`
