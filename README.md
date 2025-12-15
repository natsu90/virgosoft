## About

A clone of trading platform with live reload using Pusher. No vibe coding.

## Tech Stacks

- Laravel Framework 12
- MySQL 8.4
- Vue 3
- Inertia 2

## Installation using Docker

````
// Copy Environment Variables
cp .env.example .env

// Install Dependencies
docker run --rm \
 -u "$(id -u):$(id -g)" \
 -v "$(pwd):/var/www/html" \
 -w /var/www/html \
 laravelsail/php84-composer:latest \
 composer install

// Generate App Key
docker run --rm \
 -u "$(id -u):$(id -g)" \
 -v "$(pwd):/var/www/html" \
 -w /var/www/html \
 laravelsail/php84-composer:latest \
 php artisan key:generate

// Run Laravel Sail
./vendor/bin/sail up

// Access into Docker container
docker exec -it virgosoft-laravel.test-1 sh

// Run DB Migration
php artisan migrate

// Build UI Assets
npm run build

````
The app should be accessible on `http://localhost`

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
