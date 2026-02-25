# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Recipe Archive is a mobile-first web application for storing and organizing references to recipes (URLs or book references), not full recipe instructions. Built with **Symfony 8.0** and **PHP 8.4**.

## Tech Stack

- **Backend**: Symfony 8.0, Doctrine ORM, PHP 8.4
- **Database**: PostgreSQL (dev via Docker), MariaDB (production)
- **Frontend**: AssetMapper + Tailwind CSS (no Node.js), Stimulus, Turbo
- **Testing**: PHPUnit, Symfony Panther, DAMA Doctrine Test Bundle
- **Authentication**: SymfonyCasts VerifyEmailBundle, ResetPasswordBundle

## Development Commands

### Setup
```bash
composer install
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### Development Server
```bash
# Start PostgreSQL database
docker compose up -d

# Run Symfony dev server
symfony server:start
# OR
php -S localhost:8000 -t public/
```

### Assets
```bash
# Build Tailwind CSS (watch mode for development)
php bin/console tailwind:build --watch

# Production: minify and compile assets
php bin/console tailwind:build --minify
php bin/console asset-map:compile
```

**Important**: If compiled asset files exist in `public/assets/`, Symfony serves those instead of the source files in `assets/` — even in dev mode. After editing a JS file, delete the corresponding compiled file from `public/assets/controllers/` so the dev server picks up the changes immediately, or recompile with `asset-map:compile`. Stale compiled files are a common cause of JS changes appearing to have no effect.

### Testing
```bash
# Run all tests
php bin/phpunit

# Run specific test
php bin/phpunit tests/Functional/RecipeControllerTest.php

# Run tests with coverage
php bin/phpunit --coverage-html coverage/
```

### Database
```bash
# Create migration
php bin/console make:migration

# Run migrations
php bin/console doctrine:migrations:migrate

# Load fixtures
php bin/console doctrine:fixtures:load
```

### Cache
```bash
php bin/console cache:clear
php bin/console cache:warmup
```

## Architecture Overview

### Domain Model

**Core Entities:**
- **Recipe**: A reference to a recipe (URL or book). Belongs to a Dish and User. Tagged with Ingredients and Tags.
- **Dish**: A container for multiple Recipe versions (e.g., "Spaghetti Carbonara"). Belongs to a User.
- **Ingredient**: Used for filtering recipes. Can have cyclic relationships (a Dish can be an Ingredient).
- **Tag**: General ad-hoc labels for flexible grouping (e.g., "Quick", "Vegetarian").
- **User**: Owns Recipes and Dishes. Email-based authentication with verification.

**Key Relationships:**
- Recipe N:1 Dish
- Recipe N:M Ingredient
- Recipe N:M Tag
- Recipe N:1 User
- Dish 1:N Recipe
- Dish N:1 User

### Controllers

- **DashboardController**: Main landing page with search/filter interface
- **RecipeController**: CRUD for recipes with authorization via RecipeVoter
- **DishController**: CRUD for dishes with authorization via DishVoter
- **RecipeMetadataController**: Scrapes URL metadata for recipe creation
- **RegistrationController**: User registration with email verification
- **ResetPasswordController**: Password reset flow
- **SecurityController**: Login/logout
- **SettingsController**: User settings and email verification status

### Frontend

**Stimulus Controllers** (assets/controllers/):
- `recipe_creation_controller.js`: Handles recipe form metadata fetching
- `modal_controller.js`: Modal dialog interactions
- `csrf_protection_controller.js`: CSRF token handling

**Styling**:
- Tailwind CSS via SymfonyCasts TailwindBundle (standalone binary, no Node.js)
- Mobile-first responsive design

**UX Components**:
- Autocomplete fields for Dish, Ingredient, and Tag selection
- Custom `AbstractCreatableEntityAutocompleteField` allows creating entities inline

### Repository Layer

**RecipeFilterTrait**: Complex filtering logic for recipes using AND-logic for tags/ingredients. This trait generates subqueries with EXISTS to ensure ALL selected tags/ingredients are present on a recipe (not just any).

### Security

- Form-based authentication (email + password)
- Email verification required for new users
- Password reset functionality
- Authorization via Voters:
  - `RecipeVoter`: Ensures users can only edit/delete their own recipes
  - `DishVoter`: Ensures users can only edit/delete their own dishes
- Login throttling: max 5 attempts per 15 minutes

## Coding Standards

- **PSR-12** coding style
- All project classes must use `declare(strict_types=1);`
- Methods must be documented with PHPDoc
- Method parameters and return types must be type-hinted
- Never commit `.env.local` or sensitive data

## Git Workflow

- Main branch: `develop`
- Feature branches only - no direct commits to develop
- All changes merged via GitHub Pull Requests
- Small, atomic commits
- Always ask before adding new dependencies

## Testing

- **Functional tests**: Full HTTP request/response cycle tests in `tests/Functional/`
- **Unit tests**: Isolated component tests in `tests/Unit/`
- **DAMA Doctrine Test Bundle**: Wraps each test in a transaction for isolation
- **Panther**: Browser testing support for JavaScript-heavy features
- Test environment uses `.env.test` configuration

## Production Deployment

See DEPLOY.md for full deployment instructions. Key points:

1. Use `composer install --no-dev --optimize-autoloader`
2. Set `APP_ENV=prod` in `.env.local`
3. Build assets: `php bin/console tailwind:build --minify && php bin/console asset-map:compile`
4. **CRITICAL**: Ensure `www-data` owns `var/` and `public/assets/` directories
5. Run migrations: `php bin/console doctrine:migrations:migrate --no-interaction`

## Key Features

- **URL Scraping**: Automatic metadata extraction from recipe URLs
- **Advanced Filtering**: AND-logic filtering (recipes must have ALL selected tags/ingredients)
- **Mobile-First**: Optimized for use in grocery stores and kitchens
- **Email Verification**: Required for new user accounts
- **Authorization**: Users can only modify their own content
