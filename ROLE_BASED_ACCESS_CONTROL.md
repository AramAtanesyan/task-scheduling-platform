# Role-Based Access Control Implementation

## Overview

This document describes the implementation of role-based access control (RBAC) for the task scheduling platform. The system now supports two user roles: **admin** and **user**, with different permissions for each role.

## User Roles

### Admin Role
Admins have full access to the system:
- Create new users
- Create tasks
- Edit all task fields (title, description, dates, assigned user, status)
- Delete tasks
- View all tasks and users

### User Role
Regular users have limited access:
- View all tasks
- Edit only the status of tasks (cannot modify title, description, dates, or reassignment)
- Cannot create new users
- Cannot create tasks
- Cannot delete tasks

## Database Changes

### 1. Users Table Migration
**File**: `database/migrations/2014_10_12_000000_create_users_table.php`

Added `role` column:
```php
$table->enum('role', ['admin', 'user'])->default('user');
```

### 2. Tasks Table Migration
**File**: `database/migrations/2025_11_05_163208_create_tasks_table.php`

Added soft delete support:
```php
$table->softDeletes();
```

### 3. User Seeder
**File**: `database/seeders/UserSeeder.php`

Created 5 users:
- **2 Admins**:
  - Admin User (admin@example.com)
  - Manager User (manager@example.com)
- **3 Regular Users**:
  - John Doe (john@example.com)
  - Jane Smith (jane@example.com)
  - Bob Wilson (bob@example.com)

All passwords: `password`

## Backend Changes

### 1. User Model
**File**: `app/Models/User.php`

- Added `role` to fillable array
- Added `role` to visible array (for API responses)
- Added helper methods:
  - `isAdmin()`: Returns true if user role is 'admin'
  - `isUser()`: Returns true if user role is 'user'

### 2. Task Model
**File**: `app/Models/Task.php`

- Added `SoftDeletes` trait for soft delete functionality
- Tasks are now soft-deleted instead of permanently removed

### 3. Task Controller
**File**: `app/Http/Controllers/TaskController.php`

Added authorization checks:

**store() method**:
- Only admins can create tasks
- Returns 403 error if non-admin attempts to create

**update() method**:
- Regular users can only update `status_id` field
- Admins can update all fields
- Returns 403 error if non-admin attempts to update non-status fields

**destroy() method**:
- Only admins can delete tasks
- Uses soft delete
- Returns 403 error if non-admin attempts to delete

### 4. User Controller
**File**: `app/Http/Controllers/UserController.php`

Added `store()` method:
- Only admins can create new users
- Validates required fields: name, email, password, role
- Creates user with hashed password
- Creates default availability record for new user
- Returns 403 error if non-admin attempts to create user

### 5. Routes
**File**: `routes/web.php`

Added route:
```php
Route::post('/users', [App\Http\Controllers\UserController::class, 'store']);
```

## Frontend Changes

### 1. API Service
**File**: `resources/js/services/api.ts`

Added user creation method:
```typescript
usersApi.create(data: { name, email, password, role })
```

### 2. App Header Component
**File**: `resources/js/components/app-header.js`

- Added "Add User" button that appears only for admin users
- Button triggers `add-user` event to parent component

### 3. User Modal Component (NEW)
**File**: `resources/js/components/user-modal.js`

New modal component for creating users:
- Form fields: name, email, password, role
- Validates input and displays errors
- Submits to POST /api/users
- Only accessible to admin users

### 4. Task Board Component
**File**: `resources/js/components/task-board.js`

- Fetches current user data on mount
- "Create Task" button only shown to admin users
- Passes `currentUser` to task-card components

### 5. Task Card Component
**File**: `resources/js/components/task-card.js`

- Accepts `currentUser` prop
- Edit button (✏️) shown to all users
- Delete button (🗑️) shown only to admin users

### 6. Task Modal Component
**File**: `resources/js/components/task-modal.js`

Enhanced for role-based editing:
- Accepts `currentUser` prop
- When editing, non-admin users:
  - See informational message about status-only editing
  - All fields except status are disabled
  - Can only update task status
- Admin users can edit all fields

### 7. Dashboard Page
**File**: `resources/js/pages/dashboard.js`

- Loads user-modal component
- Handles `add-user` event from app-header
- Shows user modal when "Add User" button clicked
- Refreshes task board after user creation

## Permission Matrix

| Action | Admin | User |
|--------|-------|------|
| View tasks | ✓ | ✓ |
| Create task | ✓ | ✗ |
| Edit task (all fields) | ✓ | ✗ |
| Edit task (status only) | ✓ | ✓ |
| Delete task | ✓ | ✗ |
| Create user | ✓ | ✗ |
| View users | ✓ | ✓ |

## Testing the Implementation

### Login Credentials

**Admin Users**:
```
Email: admin@example.com
Password: password

Email: manager@example.com
Password: password
```

**Regular Users**:
```
Email: john@example.com
Password: password

Email: jane@example.com
Password: password

Email: bob@example.com
Password: password
```

### Test Scenarios

1. **Admin User**:
   - Login as admin@example.com
   - Verify "Add User" button appears in header
   - Click "Add User" and create a new user
   - Verify "Create Task" button appears
   - Create a new task
   - Edit a task (all fields should be editable)
   - Delete a task (delete button should be visible)

2. **Regular User**:
   - Login as john@example.com
   - Verify "Add User" button does NOT appear
   - Verify "Create Task" button does NOT appear
   - Click edit on a task
   - Verify only status field is editable (other fields are disabled)
   - Verify informational message appears
   - Change task status and save
   - Verify delete button does NOT appear on task cards

3. **Backend Authorization**:
   - As a regular user, attempt to:
     - POST to /api/tasks (should return 403)
     - DELETE a task (should return 403)
     - PUT to /api/tasks with non-status fields (should return 403)
     - POST to /api/users (should return 403)

## Migration Commands

To apply the changes:

```bash
php artisan migrate:fresh --seed
```

To compile frontend assets:

```bash
npm run dev
```

## Security Considerations

1. **Backend Validation**: All permission checks are enforced on the backend, not just the frontend
2. **Frontend UX**: UI elements are hidden/disabled for better user experience, but security relies on backend validation
3. **Soft Deletes**: Tasks are soft-deleted, allowing recovery if needed
4. **Password Hashing**: All passwords are hashed using Laravel's Hash facade

## Future Enhancements

Potential improvements for the RBAC system:
1. Add more granular roles (e.g., manager, viewer)
2. Add permission to edit own tasks
3. Add user profile management
4. Add role-based dashboard customization
5. Add audit logging for admin actions
6. Add password reset functionality for new users

