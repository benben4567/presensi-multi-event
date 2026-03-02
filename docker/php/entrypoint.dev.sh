#!/bin/sh
# Install composer deps jika vendor belum ada (pertama kali atau setelah reset)
if [ ! -f "vendor/autoload.php" ]; then
    echo "Installing Composer dependencies..."
    composer install --optimize-autoloader
fi
exec "$@"
