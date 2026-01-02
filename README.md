
# Laravel E-Commerce API Showcase

A headless e-commerce API developed as a technical portfolio showcase. This repository demonstrates modern Laravel 12 architecture, strict type safety, and enterprise-grade patterns including Domain-Driven Design (Actions), Event-Driven Architecture, and Automated Testing.

## Key Features & Architecture

*   **Multi-Language Support**: Full database-level localization (English/Slovak) for Products and Categories using JSON columns.
*   **Role-Based Access Control (RBAC)**: Distinct permissions for Administrators and Customers using Policies and Sanctum.
*   **Strict State Management**: Order status logic enforced via PHP Enums and a centralized State Machine (handling COD vs. Standard payment flows).
*   **Event-Driven Architecture**: Decoupled email notifications using Observers, Events, Listeners, and Queued Jobs.
*   **Clean Code Patterns**: Usage of the Action Pattern for complex business logic, API Resources for response transformation, and FormRequests for validation.
*   **Soft Deletes**: Implementation of restoration logic for Catalog management.
*   **Automated Testing**: Comprehensive Unit and Feature test suite using Pest PHP.

## Technology Stack

*   **Framework**: Laravel 12
*   **Language**: PHP 8.4
*   **Database**: MySQL 8
*   **Cache/Queue**: Redis
*   **Testing**: Pest PHP
*   **Documentation**: Scribe (OpenAPI/Swagger)
*   **Environment**: Docker (Laravel Sail)

## Setup Instructions

Prerequisites: Docker Desktop must be installed and running.

1. **Clone the repository**
   ```bash
   git clone https://github.com/Schvantner-Code/laravel-shop.git
   cd laravel-shop
   ```

2. **Start the Docker container**
   ```bash
   ./vendor/bin/sail up -d
   ```

3. **Install dependencies and setup database**
   ```bash
   ./vendor/bin/sail composer install
   ./vendor/bin/sail artisan key:generate
   ./vendor/bin/sail artisan migrate --seed
   ```

## Documentation & API Usage

This project uses **Scribe** to generate interactive API documentation.

*   **View Docs**: Navigate to `http://localhost/docs` in your browser.
*   **Interactive Testing**: You can send requests directly from the documentation page using the "Try It Out" button.
*   **Postman Collection & OpenAPI spec**: Automatically generated and available via the docs interface.

### Important Headers
To test the localization features, use the `Accept-Language` header in your requests:
*   `Accept-Language: en` (Default)
*   `Accept-Language: sk` (Slovak)

## Testing & CI

The repository includes a GitHub Actions workflow that automatically runs tests on every push. To run the test suite locally:

```bash
./vendor/bin/sail test
```