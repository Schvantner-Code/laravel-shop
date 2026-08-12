# Laravel E-Commerce API Showcase

[![Tests](https://github.com/Schvantner-Code/laravel-shop/actions/workflows/tests.yml/badge.svg)](https://github.com/Schvantner-Code/laravel-shop/actions/workflows/tests.yml)

A headless e-commerce API developed as a technical portfolio showcase. This repository demonstrates modern Laravel 13 architecture, versioned REST contracts, transaction-safe checkout, event-driven workflows, and automated testing.

## Key Features & Architecture

*   **Multi-Language Support**: Full database-level localization (English/Slovak) for Products and Categories using JSON columns.
*   **Role-Based Access Control (RBAC)**: Distinct permissions for Administrators and Customers using Policies and Sanctum.
*   **Strict State Management**: Order status logic enforced via PHP Enums and a centralized State Machine (handling COD vs. Standard payment flows).
*   **Inventory-Safe Checkout**: Server-calculated totals, product availability checks, row locking, atomic stock decrementing, and rollback-safe order creation.
*   **Versioned API Contracts**: A `/api/v1` API with consistent success and error envelopes, validated query parameters, and partial `PATCH` updates.
*   **Event-Driven Architecture**: Decoupled email notifications using Observers, Events, Listeners, and Queued Jobs.
*   **Clean Code Patterns**: Usage of the Action Pattern for complex business logic, API Resources for response transformation, and FormRequests for validation.
*   **Soft Deletes**: Implementation of restoration logic for Catalog management.
*   **Automated Testing**: Comprehensive Unit and Feature test suite using Pest PHP.

## Technology Stack

*   **Framework**: Laravel 13
*   **Language**: PHP 8.5
*   **Database**: MySQL 8.4
*   **Cache/Queue**: Redis
*   **Testing**: Pest PHP
*   **Documentation**: Scribe (OpenAPI/Swagger)
*   **Environment**: Docker (Laravel Sail)

## Setup Instructions

Prerequisites: Docker Desktop with WSL integration, or Docker Engine on Linux, must be installed and running. The commands below should be run from Linux, macOS, or WSL.

1. **Clone the repository**
   ```bash
   git clone https://github.com/Schvantner-Code/laravel-shop.git
   cd laravel-shop
   ```

2. **Create the local environment file**
   ```bash
   cp .env.example .env
   ```

3. **Bootstrap PHP dependencies using Docker**

   A fresh clone does not contain `vendor/bin/sail`, so the vendor directory must be bootstrapped before Sail can start. Composer scripts are deferred because this bootstrap image uses PHP 8.4 while the application requires PHP 8.5:

   ```bash
   docker run --rm \
       -u "$(id -u):$(id -g)" \
       -v "$(pwd):/var/www/html" \
       -w /var/www/html \
       laravelsail/php84-composer:latest \
       composer install --ignore-platform-reqs --no-scripts
   ```

4. **Start the application services**
   ```bash
   ./vendor/bin/sail up -d
   ```

5. **Initialize the application and database**
   ```bash
   ./vendor/bin/sail composer install
   ./vendor/bin/sail artisan key:generate
   ./vendor/bin/sail artisan migrate --seed
   ```

6. **Start the queue worker**

   In a separate terminal, run:

   ```bash
   ./vendor/bin/sail artisan queue:work
   ```

   Checkout and order-status emails are queued. API requests work without a
   worker, but email jobs remain pending until one is running.

The seeders create two demonstration accounts:

| Role | Email | Password |
|---|---|---|
| Administrator | `admin@example.com` | `password` |
| Customer | `customer@example.com` | `password` |

These credentials are for local development only.

### Local database access

Laravel connects to MySQL through the Docker service name `mysql`. A desktop database client connects through the published host port instead:

| Setting | Application container | Host database client |
|---|---|---|
| Host | `mysql` | `127.0.0.1` |
| Port | `3306` | `3306` |
| Database | `laravel` | `laravel` |
| Username | `sail` | `sail` |
| Password | `password` | `password` |

If port 3306 is already occupied, set `FORWARD_DB_PORT` in `.env` and use that value in the host database client. Do not change `DB_PORT`; container-to-container traffic still uses port 3306.

To inspect migration state or open the MySQL console:

```bash
./vendor/bin/sail artisan migrate:status
./vendor/bin/sail mysql
```

`./vendor/bin/sail down` stops and removes containers while preserving the named database volume. Avoid `sail down -v` and `migrate:fresh` when local data must be retained.

## Documentation & API Usage

This project uses **Scribe** to generate interactive API documentation.

*   **View Docs**: Navigate to `http://localhost/docs` in your browser.
*   **Interactive Testing**: You can send requests directly from the documentation page using the "Try It Out" button.
*   **Inspect without running the project**: View the tracked [OpenAPI specification](public/docs/openapi.yaml) or [Postman collection](public/docs/collection.json).
*   **View captured emails**: Open Mailpit at `http://localhost:8025` while Sail and the queue worker are running.

### Important Headers
To test the localization features, use the `Accept-Language` header in your requests:
*   `Accept-Language: en` (Default)
*   `Accept-Language: sk` (Slovak)

## Architecture Walkthrough

API requests enter through the versioned routes in `routes/api.php`. Form
Requests validate and normalize input, Policies enforce authorization, and API
Resources provide consistent response contracts. Exceptions are converted to a
shared JSON error envelope in `bootstrap/app.php`.

Checkout delegates its business rules to `CreateOrder`. Inside one database
transaction it rechecks the payment method, locks products in a stable order,
validates every requested quantity, decrements inventory, snapshots unit prices
on the order-product rows, and creates the order. Any failure rolls back the
entire operation.

After the transaction commits, `OrderPlaced` dispatches the queued confirmation
listener. Later status changes are observed after commit and queue their own
customer email. This keeps SMTP work out of API response time and prevents
rolled-back changes from publishing notifications.

## Testing & CI

The repository includes a GitHub Actions workflow that automatically runs tests on every push. Sail creates a separate `testing` database for the local suite, so tests do not use the development `laravel` database. To run the test suite locally:

```bash
./vendor/bin/sail test
```

The same code-quality checks enforced by CI can be run locally:

```bash
./vendor/bin/sail composer format:test
./vendor/bin/sail composer analyse
```

## Development Workflow

Enable the repository's versioned Git hooks once after cloning:

```bash
git config core.hooksPath .githooks
```

When staged API routes, controllers, requests, Scribe configuration, or the Scribe dependency change, the pre-commit hook regenerates and stages the Scribe files in `public/docs`. This keeps API documentation in the same commit as the related change without modifying docs during unrelated commits. Sail must be running for those commits; the commit is stopped if documentation generation fails.
