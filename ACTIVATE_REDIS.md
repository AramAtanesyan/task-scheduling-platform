# 🚀 Redis Queue Activation - Final Steps

## ✅ What's Already Done

- ✅ Predis package installed (v3.2.0)
- ✅ Docker Compose configured with Redis
- ✅ Queue worker set to use Redis
- ✅ Scheduler configured
- ✅ All code ready

## 📝 Step 1: Update Your .env File

Open your `.env` file and make these changes:

```bash
# CHANGE THIS LINE:
QUEUE_CONNECTION=sync

# TO THIS:
QUEUE_CONNECTION=redis

# ADD THESE LINES (if not already present):
REDIS_HOST=redis          # For Docker
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_CLIENT=predis
REDIS_DB=0
REDIS_CACHE_DB=1
```

### Complete Redis Configuration Block

Add this complete block to your `.env` file:

```bash
#-------------------------------------------------
# Queue Configuration
#-------------------------------------------------
QUEUE_CONNECTION=redis

#-------------------------------------------------
# Redis Configuration
#-------------------------------------------------
REDIS_HOST=redis           # Use 'redis' for Docker, '127.0.0.1' for local
REDIS_PASSWORD=null        # Set a password for production
REDIS_PORT=6379
REDIS_CLIENT=predis
REDIS_DB=0
REDIS_CACHE_DB=1
```

## 🐳 Step 2: Start Docker Services

```bash
# Stop existing containers (if running)
docker-compose down

# Start all services including Redis
docker-compose up -d

# Check all services are running
docker-compose ps
```

You should see these containers running:
- ✅ task_app - Laravel application
- ✅ task_nginx - Web server
- ✅ task_db - MySQL database
- ✅ **task_redis** - Redis server
- ✅ **task_queue** - Queue worker
- ✅ **task_scheduler** - Lock cleanup

## 🧪 Step 3: Test Redis Connection

```bash
# Test Redis connection
docker-compose exec app php artisan tinker

# In tinker, run:
>>> Redis::connection()->ping()
# Should return: "+PONG"

>>> exit
```

## 🗄️ Step 4: Run Migrations (If Not Done Yet)

```bash
docker-compose exec app php artisan migrate
```

This creates:
- `jobs` table (for queue)
- `user_availability_locks` table (for lock mechanism)
- Adds indexes and constraints

## ✅ Step 5: Clear Cache

```bash
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan queue:restart
```

## 🎯 Step 6: Verify Everything Works

### Test 1: Check Services
```bash
# Check queue worker is processing
docker-compose logs -f queue

# You should see:
# "Processing jobs from the [redis] queue"
```

### Test 2: Check Redis
```bash
# Connect to Redis
docker-compose exec redis redis-cli

# In Redis CLI:
> PING
# Should return: PONG

> KEYS *
# Should show your app's Redis keys

> exit
```

### Test 3: Create a Test Task
```bash
curl -X POST http://localhost:8000/api/tasks \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "Redis Test Task",
    "description": "Testing Redis queue",
    "start_date": "2025-11-20",
    "end_date": "2025-11-25",
    "user_id": 1,
    "status_id": 1
  }'
```

Expected response:
```json
{
  "success": true,
  "message": "Task created successfully",
  "data": {
    "id": 1,
    "title": "Redis Test Task",
    ...
  }
}
```

### Test 4: Verify Job Processed
```bash
# Check queue worker logs
docker-compose logs queue --tail=50

# You should see:
# "User availability updated successfully for Task ID: 1"
```

### Test 5: Test Overlap Prevention
```bash
# Try to create overlapping task (should fail)
curl -X POST http://localhost:8000/api/tasks \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "Overlapping Task",
    "description": "This should fail",
    "start_date": "2025-11-22",
    "end_date": "2025-11-28",
    "user_id": 1,
    "status_id": 1
  }'
```

Expected error:
```json
{
  "success": false,
  "message": "User is unavailable during this period. They have an overlapping task: \"Redis Test Task\" (Nov 20, 2025 - Nov 25, 2025)",
  "errors": {
    "overlapping_task": {
      "id": 1,
      "title": "Redis Test Task",
      ...
    }
  }
}
```

## 📊 Monitoring Commands

### View Queue Worker Logs
```bash
docker-compose logs -f queue
```

### View Scheduler Logs
```bash
docker-compose logs -f scheduler
```

### View Application Logs
```bash
docker-compose logs -f app
# or
docker-compose exec app tail -f storage/logs/laravel.log
```

### Check Redis Queue Status
```bash
docker-compose exec redis redis-cli

> LLEN queues:default  # Number of pending jobs
> KEYS queues:*        # All queue keys
> MONITOR              # Watch Redis commands in real-time
```

### Check Failed Jobs
```bash
docker-compose exec app php artisan queue:failed
```

### Retry Failed Jobs
```bash
docker-compose exec app php artisan queue:retry all
```

## 🔧 Troubleshooting

### Issue: "Connection refused" error

**Solution:**
```bash
# Restart Redis
docker-compose restart redis

# Check Redis is running
docker-compose ps redis
```

### Issue: Jobs not processing

**Solution:**
```bash
# Restart queue worker
docker-compose restart queue

# Check queue worker logs
docker-compose logs queue
```

### Issue: "Class 'Redis' not found"

**Solution:**
```bash
# Verify predis is installed
docker-compose exec app php -r "echo class_exists('Predis\Client') ? 'OK' : 'Missing';"

# If missing, install
docker-compose exec app composer require predis/predis
```

### Issue: Configuration not updating

**Solution:**
```bash
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose restart
```

## 📈 Performance Metrics

With Redis, you should see:

- **Job Processing Speed:** ~1-5ms per job (vs 10-50ms with database)
- **Throughput:** ~10,000 jobs/second (vs ~100 with database)
- **Database Load:** Near zero for queue operations
- **API Response Time:** Unchanged (~50-100ms)

## 🎉 Success Indicators

You'll know it's working when:

✅ `docker-compose ps` shows all services "Up"
✅ `Redis::connection()->ping()` returns "PONG"
✅ Queue worker logs show "Processing jobs from the [redis] queue"
✅ Task creation returns success
✅ Overlapping tasks are rejected with detailed error
✅ Jobs are processed within 1-5 seconds

## 🔄 Quick Reference

### Start Services
```bash
docker-compose up -d
```

### Stop Services
```bash
docker-compose down
```

### Restart Queue Worker
```bash
docker-compose restart queue
```

### View All Logs
```bash
docker-compose logs -f
```

### Access Redis CLI
```bash
docker-compose exec redis redis-cli
```

### Access Laravel Tinker
```bash
docker-compose exec app php artisan tinker
```

## 📚 Next Steps

Once everything is running:

1. ✅ Test all API endpoints
2. ✅ Verify validation works
3. ✅ Check error messages in frontend
4. ✅ Monitor queue performance
5. ✅ Set up production monitoring (optional)

## 🎯 Summary

**To activate Redis:**

1. **Update .env:** `QUEUE_CONNECTION=redis`, add Redis config
2. **Start Docker:** `docker-compose up -d`
3. **Run migrations:** `docker-compose exec app php artisan migrate`
4. **Test:** Create tasks and verify overlap prevention

**That's it!** Your availability validation system is now running with Redis queue.

---

**Current Status:** ✅ Predis installed, Docker configured, Ready to activate

**Next Step:** Update your `.env` file and run `docker-compose up -d`

