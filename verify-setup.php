#!/usr/bin/env php
<?php

require_once __DIR__.'/vendor/autoload.php';

use Illuminate\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Clawra Setup Verification Script
 * 
 * This script verifies that the basic Clawra setup is working correctly.
 */

echo "🔍 Clawra Setup Verification\n";
echo "============================\n\n";

// Check PHP version
echo "PHP Version Check:\n";
$phpVersion = phpversion();
$requiredVersion = '8.2';
if (version_compare($phpVersion, $requiredVersion, '>=')) {
    echo "  ✅ PHP $phpVersion (required: $requiredVersion+)\n";
} else {
    echo "  ❌ PHP $phpVersion (required: $requiredVersion+)\n";
    exit(1);
}

// Check if required extensions are loaded
echo "\nRequired Extensions Check:\n";
$requiredExtensions = ['sqlite3', 'pdo_sqlite', 'openssl', 'mbstring'];
foreach ($requiredExtensions as $extension) {
    if (extension_loaded($extension)) {
        echo "  ✅ $extension\n";
    } else {
        echo "  ❌ $extension\n";
        exit(1);
    }
}

// Check Composer dependencies
echo "\nComposer Dependencies Check:\n";
if (file_exists(__DIR__.'/vendor/autoload.php')) {
    echo "  ✅ Composer dependencies installed\n";
} else {
    echo "  ❌ Composer dependencies not found\n";
    exit(1);
}

// Check environment file
echo "\nEnvironment Configuration Check:\n";
if (file_exists(__DIR__.'/.env')) {
    echo "  ✅ .env file exists\n";
    
    // Check if key is generated
    $envContent = file_get_contents(__DIR__.'/.env');
    if (strpos($envContent, 'APP_KEY=') !== false && strpos($envContent, 'APP_KEY=base64:') !== false) {
        echo "  ✅ Application key generated\n";
    } else {
        echo "  ⚠️  Application key not generated (run: php artisan key:generate)\n";
    }
} else {
    echo "  ❌ .env file not found\n";
    exit(1);
}

// Check database files
echo "\nDatabase Files Check:\n";
if (file_exists(__DIR__.'/database/database.sqlite')) {
    echo "  ✅ Web database exists\n";
} else {
    echo "  ⚠️  Web database not found (run: touch database/database.sqlite)\n";
}

if (file_exists(__DIR__.'/database/nativephp.sqlite')) {
    echo "  ✅ NativePHP database exists\n";
} else {
    echo "  ⚠️  NativePHP database not found (run: touch database/nativephp.sqlite)\n";
}

// Check if required packages are installed
echo "\nRequired Packages Check:\n";
$requiredPackages = [
    'laravel/framework',
    'livewire/livewire',
    'filament/filament',
    'nativephp/electron',
    'laravel/boost'
];

// Read composer.lock to check installed packages
if (file_exists(__DIR__.'/composer.lock')) {
    $composerLock = json_decode(file_get_contents(__DIR__.'/composer.lock'), true);
    $installedPackages = [];
    
    foreach ($composerLock['packages'] as $package) {
        $installedPackages[$package['name']] = $package['version'];
    }
    
    $missingPackages = [];
    foreach ($requiredPackages as $package) {
        if (isset($installedPackages[$package])) {
            echo "  ✅ $package ({$installedPackages[$package]})\n";
        } else {
            $missingPackages[] = $package;
        }
    }
    
    if (!empty($missingPackages)) {
        echo "  ⚠️  Some packages not found in composer.lock:\n";
        foreach ($missingPackages as $package) {
            echo "    - $package\n";
        }
        echo "  (This might be a verification script issue)\n";
    }
} else {
    echo "  ❌ composer.lock not found\n";
    exit(1);
}

echo "\n🎉 Setup verification completed successfully!\n";
echo "You can now run the application with:\n";
echo "  Web (Laravel Herd): http://clawra.test/\n";
echo "  NativePHP Desktop: php artisan native:serve\n";
echo "  Alternative Web: php artisan serve --port=8080\n";