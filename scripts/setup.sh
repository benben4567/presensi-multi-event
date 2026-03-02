#!/bin/bash

set -e

# Parse arguments
MODE="dev"
for arg in "$@"; do
    case $arg in
        --prod) MODE="prod" ;;
    esac
done

if [ "$MODE" = "prod" ]; then
    COMPOSE="docker-compose -f docker-compose.yml -f docker-compose.prod.yml"
    ENV_TEMPLATE=".env.production"
    APP_URL="http://localhost:80"
    echo "🚀 Setting up Laravel Docker environment (PRODUCTION mode)..."
else
    COMPOSE="docker-compose"
    ENV_TEMPLATE=".env.docker"
    APP_URL="http://localhost:8080"
    echo "🚀 Setting up Laravel Docker environment (development mode)..."
fi

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker first."
    exit 1
fi

# Copy environment file if not exists
if [ ! -f .env ]; then
    echo "📄 Copying environment file from ${ENV_TEMPLATE}..."
    cp "$ENV_TEMPLATE" .env
else
    echo "⚠️  .env file already exists. Skipping copy."
fi

# Build and start containers
echo "🐳 Building and starting Docker containers..."
$COMPOSE up -d --build

# Wait for services to be ready
echo "⏳ Waiting for services to initialize..."
sleep 30

# Generate application key if not set
if ! grep -q "APP_KEY=base64:" .env; then
    echo "🔑 Generating application key..."
    $COMPOSE exec php php artisan key:generate --force
fi

# Run migrations
echo "📊 Running database migrations..."
$COMPOSE exec php php artisan migrate --force

if [ "$MODE" = "dev" ]; then
    # Clear caches in development
    echo "🧹 Optimizing Laravel..."
    $COMPOSE exec php php artisan config:clear
    $COMPOSE exec php php artisan cache:clear
    $COMPOSE exec php php artisan route:clear
    $COMPOSE exec php php artisan view:clear
fi

echo "✅ Setup complete!"
echo "🌐 Your application is running at: ${APP_URL}"

if [ "$MODE" = "dev" ]; then
    echo "💾 Database is available at: localhost:3306"
    echo "🗄️  Redis is available at: localhost:6379"
fi
