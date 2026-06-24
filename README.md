# digitalTolk

Laravel API for scalable translation management with authentication, versioned endpoints, repository/service layers, and cached exports.

## Overview

This project provides a clean-architecture style Laravel API for managing localized translations. It includes:

- API versioning under `/api/v1`
- Sanctum-based authentication
- Translation CRUD operations
- Search and export endpoints
- Repository pattern and service layer separation
- Cached export responses for performance
- Seeder support for large-scale testing

## Features

- Register, login, logout, and fetch the authenticated user
- Create, update, view, delete, search, and export translations
- Create locales automatically when a translation uses a new locale code
- Attach and sync tags with translations
- Cache translation exports per locale
- Support large datasets with batch seeding
- Feature and performance tests included

## Tech Stack

- PHP 8.2+
- Laravel 12
- Laravel Sanctum
- Eloquent ORM
- PHPUnit
- SQLite for local testing

## Installation

No Docker is required.

### 1) Clone and install dependencies

```bash
git clone <repo-url>
cd digitalTolk
composer install
```

### 2) Create your `.env`

Copy the example file:

```bash
cp .env.example .env
php artisan key:generate
```

Set your database and cache values in `.env`:

```env
APP_NAME=digitalTolk
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=digitaltolk
DB_USERNAME=root
DB_PASSWORD=

CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=sync
```

If you want to use SQLite locally, set:

```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite
```

## Database Migration

Run migrations:

```bash
php artisan migrate
```

This project includes tables for:

- `users`
- `personal_access_tokens`
- `locales`
- `tags`
- `translations`
- `tag_translation`

## Seeder for 100k Records

Seed a large translation dataset with:

```bash
php artisan db:seed --class=TranslationSeeder
```

The seeder:

- creates locales: `en`, `fr`, `es`
- creates tags: `mobile`, `desktop`, `web`
- generates `100,000+` translations
- attaches random tags
- uses batched inserts to avoid memory issues

## Authentication Usage

Authentication uses Laravel Sanctum tokens.

### Register

`POST /api/v1/register`

Request:

```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

Response:

```json
{
  "success": true,
  "message": "Registered",
  "data": {
    "user": {
      "id": 1,
      "name": "Jane Doe",
      "email": "jane@example.com"
    },
    "token": "1|plain-text-token",
    "token_type": "Bearer"
  }
}
```

### Login

`POST /api/v1/login`

Request:

```json
{
  "email": "jane@example.com",
  "password": "password123"
}
```

Response:

```json
{
  "success": true,
  "message": "Logged in",
  "data": {
    "user": {
      "id": 1,
      "name": "Jane Doe",
      "email": "jane@example.com"
    },
    "token": "1|plain-text-token",
    "token_type": "Bearer"
  }
}
```

### Using the token

Send the token in the `Authorization` header:

```bash
Authorization: Bearer <token>
```

Protected endpoints:

- `POST /api/v1/logout`
- `GET /api/v1/me`
- all translation management endpoints

## API Endpoints

### Auth

- `POST /api/v1/register`
- `POST /api/v1/login`
- `POST /api/v1/logout`
- `GET /api/v1/me`

### Translations

- `GET /api/v1/translations`
- `POST /api/v1/translations`
- `GET /api/v1/translations/{translation}`
- `PUT /api/v1/translations/{translation}`
- `DELETE /api/v1/translations/{translation}`
- `GET /api/v1/translations/search`
- `GET /api/v1/translations/export/{locale}`

### Users

User routes exist in the current API skeleton as examples and are also versioned under `/api/v1`.

## Example Requests and Responses

### Create translation

`POST /api/v1/translations`

Request:

```json
{
  "translation_key": "welcome",
  "locale": "en",
  "content": "Welcome",
  "tags": ["web", "mobile"]
}
```

Response:

```json
{
  "success": true,
  "message": "Created",
  "data": {
    "id": 1,
    "translation_key": "welcome",
    "content": "Welcome",
    "locale": {
      "id": 1,
      "code": "en",
      "name": "en"
    },
    "tags": [
      {
        "id": 1,
        "name": "web"
      },
      {
        "id": 2,
        "name": "mobile"
      }
    ],
    "created_at": "2026-06-24T00:00:00.000000Z",
    "updated_at": "2026-06-24T00:00:00.000000Z"
  }
}
```

### View translation

`GET /api/v1/translations/1`

### Update translation

`PUT /api/v1/translations/1`

Request:

```json
{
  "content": "Welcome back",
  "tags": ["desktop"]
}
```

### Delete translation

`DELETE /api/v1/translations/1`

### Search translations

`GET /api/v1/translations/search`

Supported filters:

- `locale` — locale code
- `tag` — tag name
- `key` — translation key
- `content` — content search text
- `q` — general search term
- `per_page` — page size

Example:

```bash
/api/v1/translations/search?locale=en&tag=web&per_page=20
```

### Export translations

`GET /api/v1/translations/export/{locale}`

Example:

```bash
/api/v1/translations/export/en
```

Response format:

```json
{
  "welcome": "Welcome",
  "login": "Login"
}
```

## Cache Strategy

The export endpoint uses Laravel cache and a versioned key:

```text
translations_export_{locale}_{latest_updated_timestamp}
```

Why this works:

- export requests avoid rebuilding the full locale map every time
- the cache key changes when a translation for that locale changes
- direct database updates are also reflected because the key includes the latest `updated_at` timestamp
- stale export cache is cleared after create, update, and delete operations
- old locale export keys are explicitly removed when a new version is generated

This keeps export behavior predictable while remaining fast for large datasets.

## Repository Pattern

Repositories isolate database access from the rest of the application.

Implemented repositories:

- `TranslationRepositoryInterface` → `EloquentTranslationRepository`
- `LocaleRepositoryInterface` → `EloquentLocaleRepository`
- `TagRepositoryInterface` → `EloquentTagRepository`

Benefits:

- easier to swap persistence logic later
- smaller controllers and services
- easier testing and mocking
- cleaner separation of concerns

## Service Layer

`TranslationService` contains the business logic for:

- creating translations
- updating translations
- deleting translations
- viewing translations
- listing translations
- searching translations
- exporting locale maps

Controllers stay thin and only:

- validate requests
- call the service
- return a formatted response

## Testing Instructions

Run the full test suite:

```bash
php artisan test
```

Run only auth tests:

```bash
php artisan test --filter=AuthTest
```

Run translation API tests:

```bash
php artisan test --filter=TranslationApiTest
```

Run performance tests:

```bash
php artisan test --filter=TranslationPerformanceTest
```

Measure code coverage locally:

```bash
composer test:coverage
```

The command requires a local coverage driver such as Xdebug or PCOV. In this container, `phpdbg` is available but does not provide coverage data, so the project documents the workflow and keeps the test suite ready for a real coverage environment.

## Performance Notes

- Export responses are cached and returned as a raw locale map
- Export queries select only `translation_key` and `content`
- Search and list endpoints use eager loading and scoped filters
- Seeder and performance tests use batch inserts to limit memory usage
- Performance thresholds in tests depend on local machine and CI load
- Performance tests cover list, search, and export timing behavior

## Design Choices

- API versioning is under `/api/v1`
- Sanctum protects write and sensitive read endpoints
- Form Requests keep validation out of controllers
- API Resources standardize response payloads
- Clean architecture keeps dependencies flowing inward
- Batch seeding supports realistic dataset sizes for profiling
- MySQL full-text search is used when available

## API Documentation

Swagger/OpenAPI is configured through `l5-swagger` and the annotated classes in `app/OpenApi` and the API controllers.

Generate the documentation:

```bash
php artisan l5-swagger:generate
```

View the interactive docs in the browser:

```text
/api/documentation
```

The docs include:

- `/api/v1` versioned endpoints
- Sanctum Bearer authentication
- request and response examples
- validation, unauthorized, and not found responses

If you update controllers or schemas, regenerate the docs so the JSON stays current.

## Response Format

The API uses a consistent envelope for standard endpoints:

```json
{
  "success": true,
  "message": "Success",
  "data": {}
}
```

Errors use:

```json
{
  "success": false,
  "message": "Error",
  "errors": {}
}
```
