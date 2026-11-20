#!/bin/bash
set -e

echo "=========================================="
echo " Starting Daijo MES Deployment"
echo "=========================================="

COMPOSE_FILE="docker-compose.prod.yml"

# 1. Pull latest code (if git repository)
if [ -d .git ]; then
    echo ">> Pulling latest code from Git..."
    git pull origin main || echo "Git pull skipped or failed, proceeding with local files..."
fi

# 2. Build Docker images
echo ">> Building Docker images..."
docker compose -f $COMPOSE_FILE build

# 3. Bring up updated containers in background
echo ">> Starting containers..."
docker compose -f $COMPOSE_FILE up -d

# 4. Wait for database connection
echo ">> Waiting for application container to be ready..."
sleep 5

# 5. Run database migrations
echo ">> Running database migrations..."
docker compose -f $COMPOSE_FILE exec -T app php artisan migrate --force

# 6. Optimize Laravel Caches for Production
echo ">> Optimizing Laravel cache and routes..."
docker compose -f $COMPOSE_FILE exec -T app php artisan optimize:clear
docker compose -f $COMPOSE_FILE exec -T app php artisan config:cache
docker compose -f $COMPOSE_FILE exec -T app php artisan route:cache
docker compose -f $COMPOSE_FILE exec -T app php artisan view:cache
docker compose -f $COMPOSE_FILE exec -T app php artisan event:cache

# 7. Restart Queue Worker to apply new code
echo ">> Restarting queue worker..."
docker compose -f $COMPOSE_FILE restart queue

echo "=========================================="
echo " Deployment Successfully Completed!"
echo "=========================================="
