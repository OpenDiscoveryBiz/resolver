# OpenDiscovery Resolver

Resolves OpenDiscovery business IDs by coordinating root and national providers.

## Requirements

- PHP 8.5+
- Composer 2.x

## Local development

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan serve
```

## Tests

```bash
php artisan test
composer audit
```

## Docker (FrankenPHP + Octane)

```bash
cp .env.example .env
php artisan key:generate
docker compose up --build
```

The app listens on http://localhost:18083.

## Local stack (all services)

Run all four OpenDiscovery services together on host ports 18081–18084. Each repo uses the shared Docker network `opendiscovery`.

Prerequisite in each repo: `cp .env.example .env && php artisan key:generate`. Host `.env` supplies `APP_KEY` via compose interpolation; app defaults come from config.

Start order (matches the deploy chain). `root-provider` creates the shared `opendiscovery` network; the others join it as external:

```bash
cd root-provider && docker compose up --build -d
cd ../dk-provider && docker compose up --build -d
cd ../resolver && docker compose up --build -d
cd ../website && docker compose up --build -d
```

Smoke URLs:

- Website: http://localhost:18084
- Resolver: http://localhost:18083/lookup?id=DK12345678
- Root: http://localhost:18081/.well-known/opendiscovery/DK123.json
- DK: http://localhost:18082/.well-known/opendiscovery/DK12345678.json

dk-provider CVR lookups need real `ERST_CVR_USER` / `ERST_CVR_PASS` in `.env`.

## Environment

| Variable | Description |
|----------|-------------|
| `PROVIDER_ROOT` | Comma-separated root provider URLs |
| `RESOLVER_TTL_MIN` | Minimum cache TTL (seconds) |
| `RESOLVER_TTL_MAX` | Maximum cache TTL (seconds) |
| `RESOLVER_TTL_DEFAULT` | Default cache TTL (seconds) |
