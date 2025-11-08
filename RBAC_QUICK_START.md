# Role-Based Access Control - Quick Start Guide

## What's New

Your task scheduling platform now has role-based access control with two user types:
- **Admin**: Full access to create/edit/delete tasks and create users
- **User**: Can only view tasks and update task statuses

## Quick Setup

### 1. Run Migrations
```bash
cd /Users/aramatanesyan/Documents/self_use/task-scheduling-platform
php artisan migrate:fresh --seed
```

### 2. Compile Assets
```bash
npm run dev
```

### 3. Start the Application
The database is now seeded with test users.

## Test Accounts

### Admin Accounts (Full Access)
```
Email: admin@example.com
Password: password

Email: manager@example.com
Password: password
```

### Regular User Accounts (Limited Access)
```
Email: john@example.com
Password: password

Email: jane@example.com
Password: password

Email: bob@example.com
Password: password
```

## What Admins Can Do

1. **Create Users**: Click "Add User" button in the header
2. **Create Tasks**: Click "Create Task" button on dashboard
3. **Edit All Task Fields**: Click edit (✏️) on any task to modify all fields
4. **Delete Tasks**: Click delete (🗑️) on any task
5. **View Everything**: Access to all tasks and users

## What Regular Users Can Do

1. **View Tasks**: See all tasks on the dashboard
2. **Update Task Status**: Click edit (✏️) on a task, but only the status field is editable
   - Other fields (title, description, dates, assignee) are disabled
   - An informational message explains the limitation

## What Regular Users Cannot Do

- ❌ Create new users (button not visible)
- ❌ Create new tasks (button not visible)
- ❌ Edit task details (fields are disabled)
- ❌ Delete tasks (button not visible)

## UI Changes Summary

### For Admin Users
- "Add User" button appears in header (next to "Manage Statuses")
- "Create Task" button appears on dashboard
- Delete button (🗑️) visible on all task cards
- All fields editable in task modal

### For Regular Users
- No "Add User" button
- No "Create Task" button
- No delete button on task cards
- Only status field editable in task modal (with info message)

## Backend Security

All permissions are enforced on the backend with proper HTTP status codes:
- `403 Forbidden`: When a user tries an unauthorized action
- `422 Unprocessable Entity`: For validation errors

## File Changes Summary

### Database
- ✅ Added `role` column to users table
- ✅ Added `deleted_at` column to tasks table (soft deletes)
- ✅ Seeded 2 admins and 3 regular users

### Backend
- ✅ User model: Added role helpers (`isAdmin()`, `isUser()`)
- ✅ Task model: Added soft delete support
- ✅ TaskController: Added admin-only checks for create/delete
- ✅ TaskController: Restricted non-admins to status-only updates
- ✅ UserController: Added store method with admin-only check
- ✅ Routes: Added POST /api/users endpoint

### Frontend
- ✅ Created user-modal.js component
- ✅ Updated app-header.js: Added "Add User" button for admins
- ✅ Updated task-board.js: Hide "Create Task" for non-admins
- ✅ Updated task-card.js: Hide delete button for non-admins
- ✅ Updated task-modal.js: Disable non-status fields for non-admins
- ✅ Updated dashboard.js: Handle user creation modal
- ✅ Updated api.ts: Added user creation endpoint

## Troubleshooting

### "Add User" button not showing for admin
1. Clear browser cache and reload
2. Verify you're logged in as an admin user
3. Check browser console for errors

### Regular user can create tasks
1. This should not happen due to backend validation
2. Check that migrations ran successfully
3. Verify the user's role in the database

### Changes not visible
1. Run `npm run dev` to recompile assets
2. Clear browser cache
3. Do a hard refresh (Cmd+Shift+R or Ctrl+Shift+R)

## Need More Details?

See `ROLE_BASED_ACCESS_CONTROL.md` for comprehensive documentation including:
- Detailed code changes
- Permission matrix
- Security considerations
- Testing scenarios
- Future enhancement ideas

