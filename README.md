# SatuForm Laravel FormBuilder

Reusable Form Builder package for Laravel 10.

## Installation

Install from VCS repository:

```bash
composer require satuform/laravel-formbuilder:dev-main
```

If package discovery is disabled, register provider manually:

```php
SatuForm\\FormBuilder\\FormBuilderServiceProvider::class,
```

## Publish Assets (Optional)

```bash
php artisan vendor:publish --tag=formbuilder-config
php artisan vendor:publish --tag=formbuilder-views
php artisan vendor:publish --tag=formbuilder-migrations
```

## Migrate

```bash
php artisan migrate
```

## Routes

By default:

- `/formbuilder`
- `/formbuilder/api/*`

Configure in `config/formbuilder.php`:

- `route_prefix`
- `api_prefix`
