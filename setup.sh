#!/bin/bash

# Clawra Setup Script

echo "Setting up Clawra development environment..."

# Install PHP dependencies
echo "Installing PHP dependencies..."
composer install

# Install Node dependencies
echo "Installing Node dependencies..."
npm install

# Create .env file
echo "Creating .env file..."
cp .env.example .env

# Generate application key
echo "Generating application key..."
php artisan key:generate

# Create database files
echo "Creating database files..."
touch database/database.sqlite
touch database/nativephp.sqlite

# Run migrations
echo "Running database migrations..."
php artisan migrate

# Run NativePHP migrations
echo "Running NativePHP migrations..."
echo "yes" | php artisan native:migrate

echo "Setup complete! You can now start the development server with: php artisan serve --port=8080"
echo "To refresh the AI file tree manually, run: php artisan clawra:update-file-tree"
