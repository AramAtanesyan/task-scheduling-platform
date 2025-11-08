# Availability Validation System - Activation Checklist

## Quick Setup Steps

Follow these steps to activate the new availability validation system:

### 1. Update Environment Variable

Edit `.env` file and change:

```bash
QUEUE_CONNECTION=sync
```

to:

```bash
QUEUE_CONNECTION=database
```

### 2. Run Database Migrations

```bash
php artisan migrate
```

This will create:
- `jobs` table (for queue processing)
- `user_availability_locks` table (for race condition prevention)
- Add indexes and constraints to existing tables

### 3. Start the Queue Worker

Open a new terminal and run:

```bash
php artisan queue:work --tries=3
```

**Important:** Keep this terminal open. The queue worker must be running to process availability updates.

For production, use a process manager like Supervisor:

```ini
[program:task-scheduling-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/logs/queue-worker.log
```

### 4. Start the Task Scheduler

The scheduler cleans up stale locks every minute.

**For Development:**
Open another terminal and run:

```bash
php artisan schedule:work
```

**For Production:**
Add to crontab:

```bash
* * * * * cd /path/to/task-scheduling-platform && php artisan schedule:run >> /dev/null 2>&1
```

### 5. Verify Everything is Working

#### Test Queue Processing:
```bash
# Check if jobs table exists
php artisan tinker
>>> DB::table('jobs')->count();
```

#### Test Lock System:
```bash
# Run cleanup command manually
php artisan locks:cleanup
```

#### Test API Endpoint:
```bash
curl -X POST http://localhost/api/tasks \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "Test Task",
    "description": "Testing availability validation",
    "start_date": "2025-11-10",
    "end_date": "2025-11-15",
    "user_id": 1,
    "status_id": 1
  }'
```

Expected response:
```json
{
  "success": true,
  "message": "Task created successfully",
  "data": {...}
}
```

## Docker Setup

If using Docker, update `docker-compose.yml`:

```yaml
services:
  app:
    # ... existing config
    
  queue-worker:
    build: .
    command: php artisan queue:work --tries=3 --timeout=90
    volumes:
      - ./:/var/www/html
    depends_on:
      - mysql
    environment:
      - DB_CONNECTION=mysql
      - DB_HOST=mysql
      - QUEUE_CONNECTION=database
      
  scheduler:
    build: .
    command: php artisan schedule:work
    volumes:
      - ./:/var/www/html
    depends_on:
      - mysql
```

Then run:
```bash
docker-compose up -d
```

## What Changed?

### Backend Changes:

1. **New Database Tables:**
   - `jobs` - Queue job storage
   - `user_availability_locks` - Lock mechanism

2. **New Services:**
   - `AvailabilityLockService` - Manages user locks
   - Enhanced `AvailabilityService` - Better validation

3. **New Form Requests:**
   - `StoreTaskRequest`
   - `UpdateTaskRequest`
   - `ReassignTaskRequest`

4. **Enhanced Controller:**
   - `TaskController` - Lock checking, better error handling

5. **New Command:**
   - `locks:cleanup` - Automated stale lock cleanup

### API Response Changes:

All API endpoints now return consistent JSON structure:

```json
{
  "success": true|false,
  "message": "User-friendly message for frontend toast",
  "data": {...},
  "errors": {...}
}
```

### Frontend Impact:

No frontend changes required! The API contract remains the same, but:
- Error messages are now more user-friendly
- Validation errors have consistent structure
- Can display toast notifications using the `message` field

## Validation Features

### 1. User Availability Validation

✅ Prevents overlapping task assignments
✅ Returns detailed error with conflicting task information
✅ Checks availability before any database writes

### 2. Race Condition Prevention

✅ Database-level locking mechanism
✅ Transactions ensure consistency
✅ Automatic stale lock cleanup

### 3. Asynchronous Processing

✅ Availability updates happen in background
✅ No impact on API response time
✅ Automatic retry on failure

### 4. Error Handling

✅ User-friendly error messages
✅ Validation errors with field-level details
✅ Graceful failure handling

## Testing the System

### Test 1: Normal Task Creation
Should succeed and return task data.

### Test 2: Overlapping Task Assignment
Create task for User A from Nov 10-15, then try to create another for User A from Nov 12-18.
Should return error: "User is unavailable during this period..."

### Test 3: User Lock Check
While a background job is processing, try to assign another task to the same user.
Should return error: "User's availability is currently being updated..."

### Test 4: Task Reassignment
Reassign an existing task to a different user.
Should validate new user's availability.

### Test 5: Validation Errors
Submit invalid data (e.g., end_date before start_date).
Should return validation error with specific field messages.

## Monitoring

### Check Queue Status:
```bash
# See pending jobs
php artisan queue:monitor

# Check failed jobs
php artisan queue:failed
```

### Check Lock Status:
```bash
php artisan tinker
>>> App\Models\UserAvailabilityLock::where('is_processing', true)->count()
```

### View Logs:
```bash
tail -f storage/logs/laravel.log
```

## Rollback (If Needed)

If you need to temporarily disable the new system:

1. Set `.env`: `QUEUE_CONNECTION=sync`
2. Stop queue worker (Ctrl+C)
3. System will work synchronously (no background jobs)

The validation logic still works, but availability updates happen immediately instead of in the background.

## Need Help?

See the full documentation: `SETUP_VALIDATION_SYSTEM.md`

## Summary

✅ **Database:** Add two new tables with migrations
✅ **Queue:** Switch from sync to database driver
✅ **Workers:** Run queue worker and scheduler
✅ **Validation:** Automatic - happens on every request
✅ **Frontend:** No changes needed - same API, better errors

