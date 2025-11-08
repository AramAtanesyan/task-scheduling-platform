#!/bin/bash

# Redis Queue Setup Script
# This script helps you activate Redis queue for the Task Scheduling Platform

echo "🚀 Redis Queue Setup"
echo "===================="
echo ""

# Check if .env exists
if [ ! -f .env ]; then
    echo "❌ Error: .env file not found!"
    echo "Please create a .env file from .env.example"
    exit 1
fi

echo "✅ Found .env file"
echo ""

# Backup .env
echo "📋 Creating backup: .env.backup"
cp .env .env.backup
echo "✅ Backup created"
echo ""

# Check if Redis config already exists
if grep -q "REDIS_CLIENT=predis" .env; then
    echo "⚠️  Redis configuration already exists in .env"
    echo ""
else
    echo "📝 Adding Redis configuration to .env..."
    
    # Add Redis configuration
    cat >> .env << 'EOF'

#-------------------------------------------------
# Queue Configuration
#-------------------------------------------------
QUEUE_CONNECTION=redis

#-------------------------------------------------
# Redis Configuration
#-------------------------------------------------
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CLIENT=predis
REDIS_DB=0
REDIS_CACHE_DB=1
EOF
    
    echo "✅ Redis configuration added"
    echo ""
fi

# Update QUEUE_CONNECTION if it exists
if grep -q "^QUEUE_CONNECTION=" .env; then
    echo "📝 Updating QUEUE_CONNECTION to redis..."
    sed -i.bak 's/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=redis/' .env
    echo "✅ Updated QUEUE_CONNECTION"
    echo ""
fi

echo "🎉 Configuration complete!"
echo ""
echo "Next steps:"
echo "1. Review your .env file"
echo "2. Run: docker-compose up -d"
echo "3. Run: docker-compose exec app php artisan migrate"
echo "4. Test: docker-compose exec app php artisan tinker"
echo "   >>> Redis::connection()->ping()"
echo ""
echo "📚 See ACTIVATE_REDIS.md for detailed instructions"

