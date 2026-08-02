# Portal Pemetaan

## Requirements

- PHP 8.3+
- Composer
- Node.js & npm
- MySQL

## Setup

1. Clone repository
   ```bash
   git clone <repo-url>
   cd disdik-pemetaan
   ```

2. Install dependencies
   ```bash
   composer install
   npm install
   ```

3. Copy dan konfigurasi environment
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Buat database MySQL bernama `service-pulpen`, lalu sesuaikan kredensial di `.env`:
   ```
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=service-pulpen
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. Jalankan migrasi
   ```bash
   php artisan migrate
   ```

6. Build assets
   ```bash
   npm run build
   ```

7. Jalankan server
   ```bash
   composer run dev
   ```

   Atau hanya PHP server:
   ```bash
   php artisan serve
   ```
