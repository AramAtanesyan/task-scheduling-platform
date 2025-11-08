# Switching to Redis Queue - Complete Guide

## Overview

This guide shows how to switch from database queue to Redis queue for better performance and scalability.

## Why Redis?

| Feature | Database Queue | Redis Queue |
|---------|---------------|-------------|
| **Performance** | ~100 jobs/sec | ~10,000 jobs/sec |
| **Latency** | 10-50ms | 1-5ms |
| **DB Load** | High (queries for each job) | None |
| **Scalability** | Limited | Excellent |
| **Reliability** | Good | Excellent |
| **Setup** | Simple | Requires Redis |

## Prerequisites

### Option 1: Install Redis Locally (Mac/Linux)

**Mac:**
```bash
brew install redis
brew services start redis
```

**Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install redis-server
sudo systemctl start redis
sudo systemctl enable redis
```

### Option 2: Use Docker (Recommended)

Already included in your docker-compose.yml setup below.

## Configuration Changes

### 1. Update `.env` File

Change these lines in your `.env`:

```bash
# Queue Configuration
QUEUE_CONNECTION=redis

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CLIENT=phpredis  # or 'predis' if phpredis not installed
```

### 2. Update Docker Compose (If Using Docker)

Your `docker-compose.yml` should include Redis:

```yaml
version: '3.8'

services:
  app:
    build: .
    ports:
      - "8000:80"
    volumes:
      - ./:/var/www/html
    depends_on:
      - mysql
      - redis
    environment:
      - DB_HOST=mysql
      - REDIS_HOST=redis
      - QUEUE_CONNECTION=redis

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: tsp
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data
    command: redis-server --appendonly yes

  queue-worker:
    build: .
    command: php artisan queue:work redis --tries=3 --timeout=90
    volumes:
      - ./:/var/www/html
    depends_on:
      - mysql
      - redis
    environment:
      - DB_HOST=mysql
      - REDIS_HOST=redis
      - QUEUE_CONNECTION=redis
    restart: unless-stopped

  scheduler:
    build: .
    command: php artisan schedule:work
    volumes:
      - ./:/var/www/html
    depends_on:
      - mysql
      - redis
    environment:
      - DB_HOST=mysql
      - REDIS_HOST=redis
      - QUEUE_CONNECTION=redis
    restart: unless-stopped

volumes:
  mysql_data:
  redis_data:
```

### 3. Install Redis PHP Extension (If Not Using Docker)

The Redis queue requires either `phpredis` or `predis` package.

**Option A: phpredis (Recommended - Faster)**
```bash
# Mac
pecl install redis

# Ubuntu/Debian
sudo apt install php-redis

# Then add to php.ini:
extension=redis.so
```

**Option B: predis (Pure PHP - Easier)**
```bash
composer require predis/predis
```

Then in `.env`:
```bash
REDIS_CLIENT=predis
```

### 4. Update Redis Configuration in Laravel

The `config/database.php` should already have Redis configured. Verify it looks like this:

```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    
    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
    ],

    'default' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
    ],

    'cache' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_CACHE_DB', '1'),
    ],
],
```

## No Code Changes Required!

✅ All your existing code works as-is
✅ The queue driver is abstracted by Laravel
✅ Your lock mechanism still works the same
✅ Background jobs run the same way

## Starting the Services

### Without Docker:

**Terminal 1: Start Redis**
```bash
redis-server
# or if using brew on Mac
brew services start redis
```

**Terminal 2: Queue Worker**
```bash
php artisan queue:work redis --tries=3 --timeout=90
```

**Terminal 3: Scheduler**
```bash
php artisan schedule:work
```

**Terminal 4: Application**
```bash
php artisan serve
```

### With Docker:

```bash
docker-compose up -d
```

That's it! Everything runs automatically.

## Verification

### 1. Test Redis Connection
```bash
php artisan tinker
>>> Redis::connection()->ping()
# Should return: "+PONG"
```

### 2. Test Queue
```bash
php artisan tinker
>>> dispatch(function () {
...     \Log::info('Test job executed!');
... });
# Check storage/logs/laravel.log for the message
```

### 3. Monitor Redis Queue
```bash
# Connect to Redis CLI
redis-cli

# Check queue status
> LLEN queues:default
> KEYS *

# Monitor in real-time
> MONITOR
```

### 4. Monitor Laravel Queue
```bash
# Check queue status
php artisan queue:monitor redis:default

# Failed jobs
php artisan queue:failed
```

## Performance Comparison

### Before (Database Queue)
```bash
# 1000 jobs
Time: ~10-15 seconds
DB Queries: ~3000
```

### After (Redis Queue)
```bash
# 1000 jobs
Time: ~1-2 seconds
DB Queries: ~0 (queue operations)
```

## Migration Steps (From Database to Redis)

### 1. Check Current Jobs
```bash
# See if any jobs are pending in database
php artisan queue:monitor database:default
```

### 2. Stop Workers
```bash
# Stop all queue workers
pkill -f "queue:work"
```

### 3. Process Remaining Jobs
```bash
# Process all pending database jobs
php artisan queue:work database --stop-when-empty
```

### 4. Update Configuration
```bash
# Update .env
QUEUE_CONNECTION=redis
```

### 5. Clear Config Cache
```bash
php artisan config:clear
php artisan cache:clear
```

### 6. Start Redis Workers
```bash
php artisan queue:work redis --tries=3
```

## Advanced Redis Configuration

### Multiple Queues

In your code, you can specify different queues:

```php
// High priority
UpdateUserAvailabilityJob::dispatch($task)->onQueue('high');

// Default priority
UpdateUserAvailabilityJob::dispatch($task)->onQueue('default');

// Low priority
SomeOtherJob::dispatch($data)->onQueue('low');
```

Start workers for each queue:
```bash
# High priority worker
php artisan queue:work redis --queue=high --tries=3

# Default worker
php artisan queue:work redis --queue=default --tries=3

# Low priority worker
php artisan queue:work redis --queue=low --tries=3
```

### Supervisor Configuration (Production)

Create `/etc/supervisor/conf.d/task-scheduling-queue.conf`:

```ini
[program:task-scheduling-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/queue-worker.log
stopwaitsecs=3600
```

Reload supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start task-scheduling-queue:*
```

### Redis Persistence

For production, ensure Redis persistence is enabled:

```bash
# In redis.conf or docker-compose.yml
appendonly yes
appendfsync everysec
```

### Redis Memory Management

```bash
# In redis.conf
maxmemory 256mb
maxmemory-policy allkeys-lru
```

## Troubleshooting

### Issue: "Class 'Redis' not found"

**Solution:**
```bash
# Install phpredis extension
pecl install redis

# Or use predis
composer require predis/predis
# Update .env: REDIS_CLIENT=predis
```

### Issue: "Connection refused [tcp://127.0.0.1:6379]"

**Solution:**
```bash
# Check if Redis is running
redis-cli ping

# Start Redis
redis-server
# or
brew services start redis
```

### Issue: Jobs not processing

**Solution:**
```bash
# Check queue worker is running
ps aux | grep "queue:work"

# Check Redis connection
php artisan tinker
>>> Redis::connection()->ping()

# Restart queue worker
php artisan queue:restart
php artisan queue:work redis --tries=3
```

### Issue: Memory issues with large queues

**Solution:**
```bash
# Use --timeout to restart workers periodically
php artisan queue:work redis --timeout=60 --max-time=3600

# Or use multiple workers with lower memory
php artisan queue:work redis --memory=128
```

## Lock Mechanism with Redis

### Optional: Switch to Redis Locks (Even Better!)

You can also use Redis for the availability locks instead of the database:

Update `app/Services/AvailabilityLockService.php`:

```php
use Illuminate\Support\Facades\Cache;

public function acquireLock(int $userId, int $taskId): bool
{
    $key = "user_availability_lock:{$userId}:{$taskId}";
    
    // Acquire lock with 5-minute expiration
    return Cache::store('redis')->add($key, true, 300);
}

public function releaseLock(int $userId, int $taskId): bool
{
    $key = "user_availability_lock:{$userId}:{$taskId}";
    return Cache::store('redis')->forget($key);
}

public function isLocked(int $userId): bool
{
    $pattern = "user_availability_lock:{$userId}:*";
    
    // Check if any lock exists for user
    $redis = Redis::connection();
    $keys = $redis->keys($pattern);
    
    return count($keys) > 0;
}
```

Benefits:
- ✅ Automatic expiration (no cleanup command needed)
- ✅ Atomic operations
- ✅ Faster than database
- ✅ Less database load

## Monitoring & Alerting

### Horizon (Optional - Laravel's Queue Dashboard)

Install Laravel Horizon for a beautiful queue dashboard:

```bash
composer require laravel/horizon
php artisan horizon:install
php artisan migrate
```

Access at: `http://localhost/horizon`

Features:
- Real-time queue monitoring
- Failed job management
- Job metrics and throughput
- Worker status
- Queue length graphs

## Summary

### To Switch to Redis:

1. **Install Redis**
   ```bash
   brew install redis  # Mac
   # or use Docker
   ```

2. **Update `.env`**
   ```bash
   QUEUE_CONNECTION=redis
   REDIS_HOST=127.0.0.1
   REDIS_PORT=6379
   ```

3. **Install PHP Extension** (if needed)
   ```bash
   composer require predis/predis
   ```

4. **Restart Workers**
   ```bash
   php artisan queue:restart
   php artisan queue:work redis --tries=3
   ```

### Benefits You Get:

✅ **10-100x faster** queue processing
✅ **Lower database load** - fewer queries
✅ **Better scalability** - handle more jobs
✅ **Production ready** - industry standard
✅ **Same code** - no application changes needed
✅ **Optional Horizon** - beautiful monitoring dashboard

### No Downsides:

❌ **No code changes required**
❌ **No migration needed**
❌ **No data loss risk**
❌ **Can switch back anytime**

---

**Recommendation:** Use Redis! It's the industry standard for Laravel queues and will give you much better performance with no code changes.

