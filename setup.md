# Clawra Setup Instructions

## Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js and npm
- SQLite extension for PHP

## Initial Setup

1. **Clone and Install Dependencies**:
   ```bash
   composer install
   npm install
   ```

2. **Environment Configuration**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Database Setup**:
   ```bash
   touch database/database.sqlite
   touch database/nativephp.sqlite
   php artisan migrate
   php artisan native:migrate
   ```

> **Note**: If you encounter a "table already exists" error during migration, this is due to an automatic session table creation. The migration has been updated to check for table existence before creation.

## Running the Application

### For Web Development (Laravel Herd):
```bash
# Available at http://clawra.test/
```

### For NativePHP Desktop App:
```bash
php artisan native:serve
```

### Alternative Web Development (without Herd):
```bash
php artisan serve --port=8080
```

## Code Quality Tools

- **Linting**: `composer lint` (uses Laravel Pint)
- **Type Checking**: `composer test:types` (uses PHPStan)
- **Test Linting**: `composer test:lint`

## Development Workflow

1. All code must pass PHPStan level 9 type checking
2. All code must be formatted with Laravel Pint
3. All new features require tests
4. Follow the immutable-first, fail-fast principles

## Project Structure

- `app/` - Core application logic
- `resources/views/` - Blade templates
- `routes/` - Route definitions
- `tests/` - PHPUnit tests
- `config/` - Configuration files

## PRD Compliance Status

See `IMPLEMENTATION_STATUS.md` for current implementation status against the PRD.

### Currently Implemented (PRD Phase 0):
- ✅ Laravel 12.x with Livewire/Filament UI
- ✅ NativePHP desktop packaging
- ✅ SQLite database setup
- ✅ Laravel Boost for AI capabilities
- ✅ Strict code quality tooling
- ✅ Development environment

### Next Steps (Moving to Phase 1):
- Implementation of specialized agents (Planner, Developer, Test Writer, Reviewer, Researcher)
- Coordinator agent development
- Project state management system
- Inference-aware scheduling components