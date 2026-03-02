#!/bin/bash

echo "🛠️  Starting development environment..."

# Start containers
docker-compose up -d

# Show status
echo "📊 Container Status:"
docker-compose ps

echo ""
echo "🌐 Available Services:"
echo "  - Laravel App: http://localhost:8080"
echo "  - MySQL: localhost:3306"
echo "  - Redis: localhost:6379"

echo ""
echo "💡 Useful commands:"
echo "  - View logs: docker-compose logs -f"
echo "  - PHP shell: docker-compose exec php sh"
echo "  - Artisan: docker-compose exec php php artisan [command]"
echo "  - Stop: docker-compose down"
