#!/bin/bash

echo "🔄 Performing fresh installation..."

# Stop and remove containers
docker-compose down -v

# Remove images (optional, uncomment if needed)
# docker-compose down --rmi all

# Start fresh
docker-compose up -d --build

# Wait for services
sleep 30

# Fresh migrations with seeding
echo "📊 Running fresh migrations..."
docker-compose exec php php artisan migrate:fresh --seed

# Clear everything
echo "🧹 Clearing caches..."
docker-compose exec php php artisan optimize:clear

echo "✅ Fresh installation complete!"
