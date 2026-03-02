#!/bin/bash

set -e

COMPOSE="docker-compose -f docker-compose.yml -f docker-compose.prod.yml"

echo "🚀 Starting production deployment..."

# Build and start containers
echo "🐳 Building and starting containers (production mode)..."
$COMPOSE up -d --build

# Wait for services to be ready
echo "⏳ Waiting for services to initialize..."
sleep 20

# Generate application key if not set
if ! grep -q "APP_KEY=base64:" .env; then
    echo "🔑 Generating application key..."
    $COMPOSE exec php php artisan key:generate --force
fi

# Run migrations
echo "📊 Running database migrations..."
$COMPOSE exec php php artisan migrate --force

echo "✅ Production deployment complete!"
echo "🌐 Your application is running at: http://localhost:80"
