# CLAUDE.md

Guide for AI assistants working on the endlech.lu codebase.

## Project Overview

**Endlech.lu** is an open platform to find and rate accessible restaurants in Luxembourg. Built with Symfony 8 (PHP 8.4+), Tailwind CSS v4, and Hotwire (Stimulus + Turbo). Currently in MVP phase with a "Coming Soon" landing page.

The UI language is German/Luxembourgish. The codebase comments (Makefile, templates) use German.

## Tech Stack

- **Backend:** PHP 8.4+, Symfony 8.0.*
- **Database:** PostgreSQL 16 (via Docker)
- **ORM:** Doctrine 3.6 with migrations
- **Templates:** Twig
- **CSS:** Tailwind CSS v4.1 via PostCSS
- **JS:** Hotwire (Stimulus 3.x + Turbo 7/8)
- **Build:** Webpack Encore 5.1
- **Testing:** PHPUnit 12.5
- **Dev Mail:** Mailpit (SMTP on port 1025, UI on port 8025)

## Project Structure

```
src/
├── Controller/          # Route controllers (attribute-based routing)
├── Entity/              # Doctrine entities (empty - MVP)
├── Repository/          # Doctrine repositories (empty - MVP)
└── Kernel.php           # Symfony kernel

config/
├── packages/            # Bundle-specific configuration (23 files)
├── routes/              # Route configurations
├── bundles.php          # Bundle registration
├── services.yaml        # Service container (autowire + autoconfigure)
└── routes.yaml          # Auto-imports controllers with #[Route] attributes

templates/
├── base.html.twig       # Base layout (header, nav, footer)
└── home/
    └── index.html.twig  # Home page with "Coming Soon" overlay

assets/
├── app.js               # Main JS entry point
├── controllers/         # Stimulus controllers
├── controllers.json     # Stimulus controller registry
├── stimulus_bootstrap.js
└── styles/
    └── app.css          # Tailwind import (@import "tailwindcss")

migrations/              # Doctrine migrations (DoctrineMigrations namespace)
tests/                   # PHPUnit tests (empty - MVP)
translations/            # i18n files (empty - MVP)
public/                  # Web root (index.php front controller)
```

## Common Commands

All development commands are available via `make`:

```bash
make init              # Full setup: Docker, composer, npm, DB, fixtures
make start             # Start Docker + Symfony server + asset watcher
make stop              # Stop Symfony server and Docker
make restart           # Restart everything

make db                # Run Doctrine migrations
make migration         # Generate new migration from entity changes
make fixtures          # Reload test fixtures (destructive)
make db-reset          # Drop DB, recreate, migrate, load fixtures

make cc                # Clear Symfony cache
make assets            # Production asset build (npm run build)
make fix               # Run PHP-CS-Fixer
```

### NPM Scripts

```bash
npm run dev            # Development build
npm run watch          # Watch mode (continuous rebuild)
npm run build          # Production build (minified, hashed)
npm run dev-server     # Webpack dev server with HMR
```

### Direct Symfony Console

```bash
php bin/console <command>           # Run any Symfony command
php bin/console debug:router        # List all routes
php bin/console make:entity         # Generate a new Doctrine entity
php bin/console make:controller     # Generate a new controller
php bin/console make:migration      # Generate migration from entity diff
```

## Testing

```bash
php bin/phpunit                     # Run all tests
php bin/phpunit tests/              # Run specific directory
```

PHPUnit is configured in `phpunit.dist.xml` with strict mode: fails on deprecation, notices, and warnings. Bootstrap loads `.env.test` variables.

No test cases exist yet. When writing tests, use `App\Tests\` namespace (PSR-4 mapped to `tests/`).

## Architecture & Conventions

### Routing
Routes are defined using PHP attributes (`#[Route]`) on controller methods. Auto-discovery is enabled in `config/routes.yaml` - no manual route registration needed.

### Services
Autowiring and autoconfiguration are enabled by default in `config/services.yaml`. All classes under `src/` are automatically registered as services.

### Database
- PostgreSQL via Docker Compose (`compose.yaml`)
- Migrations namespace: `DoctrineMigrations` (not `App\Migrations`)
- Migration path: `migrations/` directory
- Connection: `postgresql://app:!ChangeMe!@database:5432/app`

### Frontend
- Entry point: `assets/app.js` (compiled by Webpack Encore to `public/build/`)
- Stimulus controllers go in `assets/controllers/` and are auto-discovered
- Tailwind CSS v4 uses PostCSS plugin (`postcss.config.mjs`)
- Templates use Tailwind utility classes throughout
- CSRF protection uses double-submit cookie pattern (see `csrf_protection_controller.js`)

### Webpack Encore Configuration
- Output: `public/build/`
- Features: PostCSS, Stimulus bridge, code splitting, source maps (dev), filename hashing (prod)
- Config: `webpack.config.js`

## Code Style

- **PHP:** 4-space indentation, PSR-4 autoloading, enforced by PHP-CS-Fixer (`make fix`)
- **YAML:** 2-space indentation
- **JS/CSS:** 4-space indentation
- **Line endings:** LF
- **Encoding:** UTF-8
- **Trailing whitespace:** trimmed (except `.md` files)

See `.editorconfig` for full formatting rules.

## Docker Services

Defined in `compose.yaml` and `compose.override.yaml`:

| Service   | Image              | Ports          | Purpose              |
|-----------|--------------------|----------------|----------------------|
| database  | postgres:16-alpine | 5432           | PostgreSQL database  |
| mailer    | axllent/mailpit    | 1025, 8025     | Dev email (SMTP+UI)  |

## Environment Files

| File            | Purpose                                    |
|-----------------|--------------------------------------------|
| `.env`          | Default config (committed, non-secret)     |
| `.env.dev`      | Dev-specific overrides                     |
| `.env.test`     | Test environment (`APP_ENV=test`)          |
| `.env.local`    | Local overrides (gitignored, secrets here) |

`APP_SECRET` must be set in `.env.local` for production. The `.env.dev` file provides a dev-only secret.

## Versioning

The project uses **CalVer** (Calendar Versioning): `vYYYY.MM.DD` (e.g., `v2026.01.13`). See `CHANGELOG.md`.

## CI/CD

No GitHub Actions workflows are configured yet. The `.github/` directory contains issue templates only (bug reports, feature requests, tasks).

## Key Files Reference

| File                  | Purpose                                    |
|-----------------------|--------------------------------------------|
| `composer.json`       | PHP dependencies and autoloading           |
| `package.json`        | NPM dev dependencies and build scripts     |
| `webpack.config.js`   | Webpack Encore build configuration         |
| `postcss.config.mjs`  | PostCSS with Tailwind CSS plugin           |
| `phpunit.dist.xml`    | PHPUnit test configuration                 |
| `compose.yaml`        | Docker services (PostgreSQL, Mailpit)      |
| `Makefile`            | Development workflow commands               |
| `importmap.php`       | Symfony AssetMapper module mapping         |
| `.editorconfig`       | Editor formatting rules                    |
