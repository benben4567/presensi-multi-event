#!/bin/bash

echo "🔄 Resetting database..."

# Stop containers
docker-compose down

# Remove database volume
docker volume rm laravel-docker-app_mysql_data

# Start containers again
docker-compose up -d

# Wait for MySQL to be ready
sleep 30

# Run fresh migrations
docker-compose exec php php artisan migrate:fresh --seed

echo "✅ Database reset complete!"
