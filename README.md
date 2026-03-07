# Clawra - Personal AI Orchestration System

## Overview
Clawra is a personal AI orchestration system built with Laravel + NativePHP for Windows. It accepts voice/text requests, decomposes them into tasks, and delegates to specialized agents.

## Technology Stack
- **Backend**: Laravel 12.x with Livewire/Filament
- **Frontend**: Livewire with Filament admin panel
- **AI Orchestration**: NativePHP for desktop packaging, Laravel Boost for AI capabilities
- **Database**: SQLite
- **Code Quality**: PHPStan (level 9), Laravel Pint for formatting
- **Testing**: PHPUnit with strict type checking

## Setup Instructions

1. **Install Dependencies**:
   ```bash
   composer install
   npm install
   ```

2. **Environment Setup**:
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

4. **Development Server**:
   ```bash
   php artisan serve --port=8080
   ```

5. **Code Quality Tools**:
   - Linting: `composer lint` (uses Pint)
   - Type checking: `composer test:types` (uses PHPStan)
   - Test linting: `composer test:lint`

## Project Structure
- `app/` - Core application logic
- `resources/views/` - Blade templates
- `routes/` - Route definitions
- `tests/` - PHPUnit tests
- `config/` - Configuration files

## Quality Assurance
This project follows Nuno Maduro's ultra-strict approach to code quality:
- 100% type coverage
- Zero tolerance for code smells
- Immutable-first architecture
- Fail-fast philosophy

## NativePHP Desktop App
This application is packaged as a desktop application using NativePHP, which allows it to run as a standalone Windows application with system tray integration.

## Laravel Boost
Laravel Boost provides AI capabilities for the application, including:
- AI-powered task decomposition
- Natural language processing
- Machine learning integrations
