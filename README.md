# Laravel 12 API-Only Project

API-only Laravel 12 application using Sanctum personal access tokens.

Made by **[revanza-git](https://github.com/revanza-git)**.

---

## Tech stack

- **Laravel 12** (PHP 8.3)
- **MySQL 8.4** (Docker Compose)
- **Auth**: Laravel Sanctum (personal access tokens)
- **Testing**: PHPUnit (Feature tests, RefreshDatabase)

---

## Setup

### 1) Install dependencies

```bash
composer install
```

### 2) Environment

```bash
copy .env.example .env
php artisan key:generate
```

This project is configured for a local Docker MySQL database by default. See `.env.example` for values.

### 3) Start MySQL (Docker)

```bash
docker compose up -d
```

### 4) Run migrations

```bash
php artisan migrate
```

### 5) Run tests

```bash
php artisan test
```

---

## Docker (app + nginx + mysql + mailhog)

This repository includes Docker Compose services for:
- `mysql` (MySQL 8.4)
- `app` (PHP-FPM running Laravel)
- `nginx` (reverse proxy serving `public/` and forwarding PHP to `app:9000`)
- `mailhog` (local SMTP server + web UI)

> Note: By default this repo forwards MySQL to **host port 3307** via `FORWARD_DB_PORT` in `.env.example`
> to avoid clashes with an existing local MySQL on 3306.

### Build & start

```bash
docker compose up -d --build
```

### Install PHP dependencies inside the container

```bash
docker compose exec app composer install
```

### Generate app key

```bash
docker compose exec app php artisan key:generate
```

### Run migrations

```bash
docker compose exec app php artisan migrate
```

### Run tests

```bash
docker compose exec app php artisan test
```

### Call the API via nginx

Nginx is exposed on `http://localhost:8080` by default.

```bash
curl -X POST http://localhost:8080/api/users \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com","password":"password123"}'
```

---

## Email testing (MailHog)

This repo uses **MailHog** for local SMTP testing.

- SMTP server: `mailhog:1025` (internal Docker network)
- Web UI: **http://localhost:8025**

When you create a user via `POST /api/users`, the app sends:
1) an email to the new user (account created)
2) an email to the system admin (`MAIL_ADMIN_ADDRESS` / `config('mail.admin_address')`)

Implementation note:
- The controller stays thin; these emails are dispatched via an event/listener:
  - Event: `App\Events\UserCreated`
  - Listener: `App\Listeners\SendUserCreatedEmails`

After calling the create user endpoint, open MailHog UI and you should see both messages.

### Configure admin email address

Set in `.env`:

```dotenv
MAIL_ADMIN_ADDRESS=admin@example.com
```

---

## Authentication (Sanctum personal access tokens)

This API uses **stateless** Bearer tokens (no sessions, no OAuth flows).

### Generate a token (local testing)

This repo includes an Artisan helper command:

```bash
# token name defaults to "local"
docker compose exec app php artisan app:create-sanctum-token test@example.com

# optional token name
docker compose exec app php artisan app:create-sanctum-token test@example.com --name=demo
```

> Note: If you run `php artisan ...` on your host machine while your `.env` has `DB_HOST=mysql`, you may see:
> `getaddrinfo for mysql failed`.
> The hostname `mysql` is a Docker Compose service name and is only resolvable **inside** the Docker network.

The command prints the **plain text token**. Use it as a Bearer token:

```bash
curl -H "Authorization: Bearer <token>" http://localhost:8080/api/users
```

---

## API endpoints

Routes live in `routes/api.php` only.

### 1) Create user (public)

**POST** `/api/users`

```bash
curl -X POST http://localhost:8080/api/users \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com","password":"password123"}'
```

Response: **201**

```json
{
  "id": 1,
  "email": "test@example.com",
  "name": "Test User",
  "created_at": "2026-01-11T03:00:00.000000Z"
}
```

### 2) List users (protected)

**GET** `/api/users` (requires `auth:sanctum`)

Query params:
- `search` (matches name OR email)
- `sortBy` (`name`, `email`, `created_at`; default `created_at`)
- `page`

```bash
curl http://localhost:8080/api/users?search=test&sortBy=email&page=1 \
  -H "Authorization: Bearer <token>"
```

Response:

```json
{
  "page": 1,
  "users": [
    {
      "id": 1,
      "email": "test@example.com",
      "name": "Test User",
      "role": "user",
      "created_at": "2026-01-11T03:00:00.000000Z",
      "orders_count": 0,
      "can_edit": true
    }
  ]
}
```

---

## Running tests (inside Docker)

Run the full test suite:

```bash
docker compose exec app php artisan test
```

Run only the main feature tests:

```bash
docker compose exec app ./vendor/bin/phpunit --colors=never \
  tests/Feature/UserStoreTest.php \
  tests/Feature/UserIndexTest.php \
  tests/Feature/UserAuthorizationTest.php
```

## Architecture decisions

This project favors Laravel-native conventions and keeps controllers thin.

### Form Requests (validation)
- `app/Http/Requests/StoreUserRequest.php`
- Validation is executed automatically before the controller runs; `$request->validated()` returns safe, validated input.

### Policies (authorization)
- `app/Policies/UserPolicy.php`
- Registered via `Gate::policy(...)` in `app/Providers/AppServiceProvider.php`
- Used in `UserResource` to compute `can_edit`.

### API Resources (response formatting)
- `app/Http/Resources/UserResource.php`
- Centralizes JSON shaping and policy-aware fields.

### Eloquent ORM (no service/repository layers)
- Models: `app/Models/User.php`, `app/Models/Order.php`
- Relationships:
  - `User` hasMany `Order`
  - `Order` belongsTo `User`

---
