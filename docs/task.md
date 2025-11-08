Task Scheduling & Notification Platform

Objective
Build a lightweight internal Task Scheduling Tool where managers can:
● Create and assign tasks
● Set deadlines and statuses
● Track user availability (with strict no-overlap rule)
The platform should include:
● A RESTful backend (Laravel or NestJS)
● A simple but responsive admin interface (Vue.js + TypeScript)
Task Breakdown
Backend Requirements (Laravel or NestJS)
Implement the following RESTful API endpoints:
1. Task Management
○ List all tasks
○ Create a new task (assign to a user)
○ Update task details or status
○ Reassign a task to another user (with availability validation)
○ Delete a task
○ Search tasks by title or description
2. User Availability
A user cannot be assigned overlapping tasks.
● If a user already has a task that overlaps with the given start–end date range (even
partially, including same-day start/end), task creation or reassignment must be
rejected.
Each task can only be assigned to one user.
User availability should be updated asynchronously (via jobs/events/workers).
● After a task is created/updated, trigger a background job/event to update availability
records in the database.
Frontend Requirements (Vue.js + TypeScript)
Create an admin dashboard interface with the following functionality:
1. Task Board View
○ Display tasks in a list view or Kanban-style columns by status.
○ Allow filtering by status and assignee.
○ Support searching tasks by title or description.
2. Task Assignment Modal
Form to create a task with validation:
■ Title (required)

■ Description (optional)
■ Start date (date picker, required)
■ End date (date picker, required)
■ Assigned user (dropdown, required)
Status (dropdown, required)

Validation rules:
A user cannot be assigned a task if they already have an overlapping task during the given
date range.
Show error message if assignment is invalid.
3. User Availability
○ If the user already has assigned a task during the selected period (start date
and end date), a new task couldn’t be created.

4. Basic Authentication
○ Simple login screen with validation

Technical Requirements
● Use MySQL to persist users, tasks and users availability, use database indexes for tasks
search
● Use seeders to populate users, statuses
Bonus Features (Optional but Appreciated)
● Add task filters (e.g., by status, assignee, due date)
● Use Docker to containerize backend and database
● When a task is created or reassigned, user should be notified asynchronously
(simulate notifications, or use websockets)
Deliverables
1. Public git repository with:
○ Complete project code
2. README.md containing:
○ Setup instructions (including Docker if applicable)
○ Admin credentials
○ Notes on any trade-offs or assumptions made
3. .env.example file and (if applicable) SQL dump or seeders