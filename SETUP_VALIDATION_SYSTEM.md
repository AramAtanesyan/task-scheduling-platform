# Availability Validation System - Setup & Usage Guide

## Overview

This document describes the enhanced task scheduling system with robust user availability validation, database locking mechanisms, asynchronous job processing, and comprehensive error handling.

## What Was Implemented

### 1. Database Tables

#### Jobs Table
- Stores Laravel queue jobs for asynchronous processing
- Used for background availability updates

#### User Availability Locks Table
- Prevents race conditions when multiple requests try to assign tasks simultaneously
- Tracks when a user's availability is being processed
- Automatically cleaned up by scheduled task

#### Enhanced Indexes
- Unique constraint on `user_availabilities.task_id`
- Composite indexes for optimized overlap queries
- Improved query performance for availability checks

### 2. Core Services

#### AvailabilityLockService
Location: `app/Services/AvailabilityLockService.php`

Methods:
- `acquireLock($userId, $taskId)` - Creates lock before job dispatch
- `releaseLock($userId, $taskId)` - Marks lock as completed
- `isLocked($userId)` - Checks if user has pending updates
- `clearStaleLocks()` - Removes locks older than 5 minutes
- `deleteOldLocks($days)` - Cleanup for completed locks

#### Enhanced AvailabilityService
Location: `app/Services/AvailabilityService.php`

New method:
- `validateAvailability()` - Returns detailed validation info with user-friendly messages

### 3. Form Requests (Validation)

Created three form request classes with custom validation rules and user-friendly error messages:

- `app/Http/Requests/StoreTaskRequest.php`
- `app/Http/Requests/UpdateTaskRequest.php`
- `app/Http/Requests/ReassignTaskRequest.php`

Each returns consistent JSON responses for frontend toast notifications.

### 4. Enhanced Task Controller

Location: `app/Http/Controllers/TaskController.php`

Features:
- Uses form requests for validation
- Checks for user locks before assignment
- Validates availability with detailed error messages
- Acquires locks in database transaction
- Dispatches background jobs only after transaction commit
- Consistent API responses using ApiResponseTrait

### 5. Background Job Processing

#### UpdateUserAvailabilityJob
Location: `app/Jobs/UpdateUserAvailabilityJob.php`

Enhancements:
- Releases lock after completion
- Handles failures gracefully with `failed()` method
- Retry logic (3 attempts with 3-second backoff)
- Proper error logging

### 6. Scheduled Cleanup

#### CleanupStaleLocksCommand
Location: `app/Console/Commands/CleanupStaleLocksCommand.php`

- Runs every minute via Laravel scheduler
- Cleans up stale locks (>5 minutes old)
- Deletes old completed locks (>7 days)

Scheduled in: `app/Console/Kernel.php`

### 7. API Response Standardization

#### ApiResponseTrait
Location: `app/Http/Traits/ApiResponseTrait.php`

Provides consistent response methods:
- `successResponse($data, $message, $statusCode)`
- `errorResponse($message, $errors, $statusCode)`
- `validationErrorResponse($message, $errors)`
- `notFoundResponse($message)`
- `serverErrorResponse($message, $error)`

All responses follow this structure:
```json
{
  "success": true|false,
  "message": "User-friendly message",
  "data": {...},
  "errors": {...}
}
```

## Setup Instructions

### 1. Update Environment Configuration

Edit your `.env` file:

```bash
# Change queue connection from 'sync' to 'database'
QUEUE_CONNECTION=database
```

### 2. Run Migrations

```bash
php artisan migrate
```

This creates:
- `jobs` table
- `user_availability_locks` table
- Adds constraints and indexes to existing tables

### 3. Start Queue Worker

In a separate terminal, start the queue worker to process background jobs:

```bash
php artisan queue:work
```

For development, you can use:
```bash
php artisan queue:work --tries=3 --timeout=90
```

### 4. Start Task Scheduler

The Laravel scheduler needs to be running to cleanup stale locks. Add this to your cron:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

Or for development, run:
```bash
php artisan schedule:work
```

### 5. Docker Setup (Optional)

If using Docker, ensure the docker-compose.yml includes a queue worker service:

```yaml
queue-worker:
  build: .
  command: php artisan queue:work --tries=3 --timeout=90
  volumes:
    - ./:/var/www/html
  depends_on:
    - mysql
```

## How It Works

### Task Creation Flow

1. **User submits task creation request**
2. **Form validation** (StoreTaskRequest)
3. **Lock check** - Is user's availability being updated?
   - If yes → Return error: "User's availability is being updated. Please wait."
4. **Availability validation** - Check for overlapping tasks
   - If overlap found → Return detailed error with conflicting task info
5. **Database transaction begins**
6. **Create task** in database
7. **Acquire lock** for the user
8. **Dispatch background job** to update availability
9. **Send notification** to assigned user
10. **Commit transaction**
11. **Return success response**

### Background Job Flow

1. **Job executes** (UpdateUserAvailabilityJob)
2. **Delete old availability** record for this task
3. **Create new availability** record
4. **Release lock** for the user
5. **Log success**

If job fails:
- Lock is released automatically
- Job retries up to 3 times
- On permanent failure, lock is released in `failed()` method

### Race Condition Prevention

**Problem:** Two simultaneous requests could both pass availability check before either creates a lock.

**Solution:**
1. All operations happen in a database transaction
2. Lock acquisition is part of the transaction
3. Jobs only dispatch after transaction commits (`after_commit: true`)
4. Lock uniqueness is enforced at database level

### Stale Lock Cleanup

**Problem:** If a job crashes, the lock might never be released.

**Solution:**
- Scheduled command runs every minute
- Releases locks older than 5 minutes
- Assumes job failed if lock exists that long

## API Response Examples

### Success Response

```json
{
  "success": true,
  "message": "Task created successfully",
  "data": {
    "id": 1,
    "title": "Task Title",
    "start_date": "2025-11-10",
    "end_date": "2025-11-15",
    "user": {...},
    "status": {...}
  }
}
```

### Validation Error Response

```json
{
  "success": false,
  "message": "Validation failed. Please check your input.",
  "errors": {
    "title": ["Task title is required."],
    "end_date": ["End date must be on or after the start date."]
  }
}
```

### Availability Error Response

```json
{
  "success": false,
  "message": "User is unavailable during this period. They have an overlapping task: \"Important Meeting\" (Nov 10, 2025 - Nov 12, 2025)",
  "errors": {
    "overlapping_task": {
      "id": 5,
      "title": "Important Meeting",
      "start_date": "2025-11-10",
      "end_date": "2025-11-12"
    }
  }
}
```

### User Locked Error Response

```json
{
  "success": false,
  "message": "This user's availability is currently being updated. Please wait a moment and try again."
}
```

## Testing

### Manual Testing Steps

1. **Test basic task creation:**
```bash
curl -X POST http://localhost/api/tasks \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test Task",
    "description": "Test description",
    "start_date": "2025-11-10",
    "end_date": "2025-11-15",
    "user_id": 1,
    "status_id": 1
  }'
```

2. **Test overlapping task rejection:**
   - Create a task for user 1 from Nov 10-15
   - Try to create another task for user 1 from Nov 12-18
   - Should receive overlap error

3. **Test race condition prevention:**
   - Send two simultaneous requests to assign tasks to the same user with overlapping dates
   - Only one should succeed

4. **Test lock cleanup:**
```bash
php artisan locks:cleanup
```

### Automated Testing

Create feature tests in `tests/Feature/TaskAvailabilityTest.php`:

```php
public function test_prevents_overlapping_task_assignment()
{
    // Create first task
    $response = $this->postJson('/api/tasks', [
        'title' => 'First Task',
        'start_date' => '2025-11-10',
        'end_date' => '2025-11-15',
        'user_id' => 1,
        'status_id' => 1,
    ]);
    
    $response->assertStatus(201);
    
    // Try to create overlapping task
    $response = $this->postJson('/api/tasks', [
        'title' => 'Overlapping Task',
        'start_date' => '2025-11-12',
        'end_date' => '2025-11-18',
        'user_id' => 1,
        'status_id' => 1,
    ]);
    
    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'message' => expect()->stringContains('overlapping task'),
    ]);
}
```

## Troubleshooting

### Jobs Not Processing

**Symptoms:** Tasks created but availability not updated

**Solutions:**
1. Check if queue worker is running: `ps aux | grep "queue:work"`
2. Check queue connection in .env: `QUEUE_CONNECTION=database`
3. Check jobs table: `SELECT * FROM jobs;`
4. Check failed_jobs table: `SELECT * FROM failed_jobs;`

### Locks Not Releasing

**Symptoms:** Users always showing as locked

**Solutions:**
1. Run cleanup manually: `php artisan locks:cleanup`
2. Check if scheduler is running
3. Check lock table: `SELECT * FROM user_availability_locks WHERE is_processing = 1;`

### Database Connection Issues

**Symptoms:** Migration fails

**Solutions:**
1. Verify database credentials in .env
2. Ensure database service is running
3. Check database exists: `CREATE DATABASE IF NOT EXISTS tsp;`

## Performance Considerations

1. **Database Indexes:** Composite indexes optimize overlap queries
2. **Queue Worker:** Scale horizontally by running multiple workers
3. **Lock Cleanup:** Runs every minute with minimal overhead
4. **Transaction Scope:** Kept minimal for better concurrency

## Security Considerations

1. **Form Request Validation:** All inputs validated before processing
2. **Database Transactions:** ACID compliance ensures data integrity
3. **Unique Constraints:** Database-level enforcement prevents duplicates
4. **Error Messages:** User-friendly but don't expose sensitive information

## Future Enhancements

1. **Redis Queue:** For better performance in high-load scenarios
2. **Pessimistic Locking:** Use SELECT FOR UPDATE for critical sections
3. **Event Broadcasting:** Real-time updates via WebSockets
4. **Caching:** Cache user availability for faster lookups
5. **Monitoring:** Add metrics for lock acquisition times and queue depths

