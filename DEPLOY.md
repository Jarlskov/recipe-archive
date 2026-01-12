# Deployment Guide (Manual / VPS)

This guide covers deploying the application to a server running **PHP 8.4**, **Nginx**, and **MariaDB**.

## 1. System Requirements
Ensure your server has the following installed:
- PHP 8.4 (with extensions: `ctype`, `iconv`, `intl`, `pdo_mysql`, `xsl`, `mbstring`, `zip`, `opcache`)
- Nginx
- MariaDB (or MySQL)
- Composer
- Git

## 2. Setup Application Code
Navigate to your web root (e.g., `/var/www`) and clone the repository:

```bash
cd /var/www
git clone https://github.com/your-username/recipe-archive.git
cd recipe-archive
```

## 3. Install Dependencies
Install PHP dependencies optimized for production:

```bash
composer install --no-dev --optimize-autoloader
```

## 4. Configure Environment
Create a `.env.local` file to override default settings. **Do not modify `.env` directly.**

```bash
cp .env .env.local
nano .env.local
```

### Critical Settings to Change in `.env.local`:
1.  **APP_ENV**: Set to `prod`.
2.  **APP_SECRET**: Generate a random string (32 chars).
3.  **DATABASE_URL**: Update this for MariaDB.
    *   **Format**: `mysql://USER:PASSWORD@127.0.0.1:3306/DATABASE_NAME?serverVersion=10.11.2-MariaDB&charset=utf8mb4`
    *   *Note: Since the project was originally set up for Postgres, ensure you use the `mysql://` protocol.*

## 5. Build Assets
This project uses **AssetMapper** and **Tailwind CSS**. You must compile them on the server.

1.  **Build Tailwind CSS**:
    This commands downloads the standalone binary (if not present) and builds the CSS.
    ```bash
    php bin/console tailwind:build --minify
    ```

2.  **Compile Assets**:
    This copies assets to the `public/assets` directory and generates the import map.
    ```bash
    php bin/console asset-map:compile
    ```

## 6. Database Setup
1.  **Create the Database** (if the user has permission, otherwise create manually via SQL):
    ```bash
    php bin/console doctrine:database:create
    ```

2.  **Run Migrations**:
    ```bash
    php bin/console doctrine:migrations:migrate --no-interaction
    ```

## 7. Folder Permissions (CRITICAL)
Symfony MUST be able to write to the `var/` directory in production. If permissions are wrong, the site will return a 500 error with NO logs because the logger cannot start.

Always ensure the web server user (usually `www-data`) owns these directories **after** running any composer or console commands:

```bash
sudo chown -R www-data:www-data var/ public/assets/
sudo chmod -R 775 var/ public/assets/
```

*Note: If you run `composer install` or `cache:clear` as a different user, you MUST re-run the chown command.*

## 8. Web Server Configuration (Nginx)
1.  Copy the provided `nginx.conf` to your Nginx sites directory:
    ```bash
    sudo cp nginx.conf /etc/nginx/sites-available/recipe-archive
    ```
2.  Edit the file to set the correct `server_name` (domain) and `root` path.
3.  Enable the site:
    ```bash
    sudo ln -s /etc/nginx/sites-available/recipe-archive /etc/nginx/sites-enabled/
    ```
4.  Test and restart Nginx:
    ```bash
    sudo nginx -t
    sudo systemctl restart nginx
    ```

## 9. Performance Optimization (Optional but Recommended)
Enable OPcache Preloading in your `php.ini`:
```ini
opcache.preload=/var/www/recipe-archive/config/preload.php
opcache.preload_user=www-data
```
