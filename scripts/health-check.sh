#!/bin/bash

echo "🏥 Docker Environment Health Check"
echo "=================================="

# Check if all containers are running
echo "📊 Container Status:"
docker-compose ps

echo ""
echo "💾 Resource Usage:"
docker stats --no-stream --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}"

echo ""
echo "🔍 Application Health:"
response=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8080)
if [ $response -eq 200 ]; then
    echo "✅ Application is responding (HTTP $response)"
else
    echo "❌ Application is not responding (HTTP $response)"
fi

echo ""
echo "🗄️  Database Connection:"
docker-compose exec php php artisan tinker --execute="
try {
    DB::connection()->getPdo();
    echo '✅ Database connection OK';
} catch (Exception \$e) {
    echo '❌ Database connection failed: ' . \$e->getMessage();
}
"

echo ""
echo "🗃️  Redis Connection:"
if docker-compose exec redis redis-cli ping > /dev/null 2>&1; then
    echo "✅ Redis connection OK"
else
    echo "❌ Redis connection failed"
fi
