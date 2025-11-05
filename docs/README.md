# Task Scheduling & Notification Platform

A lightweight internal Task Scheduling Tool where managers can create and assign tasks, set deadlines and statuses, and track user availability with strict no-overlap rules.

## Tech Stack

- **Backend**: Laravel 8
- **Frontend**: Vue.js 2 + TypeScript
- **Database**: MySQL
- **Authentication**: Laravel Sanctum

## Project Structure

```
task-scheduling-platform/
├── backend/
│   ├── app/              # Laravel application code
│   ├── resources/
│   │   └── js/          # Vue 2 + TypeScript SPA
│   ├── routes/          # API and web routes
│   └── ...
└── docs/                # Documentation
```

## Prerequisites

- PHP >= 7.3
- Composer
- Node.js >= 14
- MySQL >= 5.7
- npm or yarn

## Setup

1. Navigate to the backend directory:
```bash
cd backend
```

2. Install PHP dependencies:
```bash
composer install
```

3. Install Node dependencies:
```bash
npm install
```

4. Copy environment file:
```bash
cp .env.example .env
```

5. Generate application key:
```bash
php artisan key:generate
```

6. Configure database in `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=task_scheduling
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

7. Run migrations:
```bash
php artisan migrate
```

8. Seed the database:
```bash
php artisan db:seed
```

9. Build frontend assets:
```bash
npm run dev
```

10. Configure queue (for background jobs):
```env
QUEUE_CONNECTION=database
```

11. Start the queue worker (in a separate terminal):
```bash
php artisan queue:work
```

12. Start the development server:
```bash
php artisan serve
```

The application will be available at `http://localhost:8000`

## Admin Credentials

After running the seeders, you can login with:

- **Email**: admin@example.com
- **Password**: password

Other test users:
- manager@example.com / password
- john@example.com / password
- jane@example.com / password

## API Endpoints

### Authentication
- `POST /api/login` - Login user
- `POST /api/logout` - Logout user
- `GET /api/user` - Get authenticated user

### Tasks
- `GET /api/tasks` - List all tasks (supports query params: search, status_id, user_id)
- `POST /api/tasks` - Create a new task
- `GET /api/tasks/{id}` - Get a single task
- `PUT /api/tasks/{id}` - Update a task
- `DELETE /api/tasks/{id}` - Delete a task
- `POST /api/tasks/{id}/reassign` - Reassign a task to another user

### Users & Statuses
- `GET /api/users` - List all users
- `GET /api/task-statuses` - List all task statuses

## Features

### Task Management
- Create, read, update, and delete tasks
- Search tasks by title or description
- Filter tasks by status and assignee
- Kanban board view and list view
- Task reassignment with availability validation

### User Availability
- Strict no-overlap rule: Users cannot be assigned overlapping tasks
- Real-time availability validation on task creation/update
- Background job updates availability records asynchronously

### Authentication
- Token-based authentication using Laravel Sanctum
- Protected routes on frontend
- Automatic token refresh handling

## Database Schema

### Tables
- `users` - User accounts
- `tasks` - Task records
- `task_statuses` - Task status definitions (To Do, In Progress, Completed, Cancelled)
- `user_availabilities` - User availability tracking (prevents overlaps)

### Indexes
- Tasks table: indexes on title, description, user_id, start_date, end_date for search performance
- User availabilities table: indexes on user_id, start_date, end_date for overlap checking

## Background Jobs

The application uses Laravel queues to update user availability records asynchronously. Make sure to run the queue worker:

```bash
php artisan queue:work
```

## Development Notes

### Trade-offs and Assumptions

1. **Queue System**: Using database queue driver for simplicity. For production, consider Redis or RabbitMQ.

2. **Date Overlap Logic**: Overlap detection uses the logic: `(start_date <= task.end_date AND end_date >= task.start_date)`. This includes same-day start/end dates as overlapping.

3. **Vue 2**: Using Vue 2 (Options API) with TypeScript as specified. Vue 2 is in maintenance mode, but provides the requested functionality.

4. **Integrated Structure**: Frontend and backend are integrated - Vue.js code is in `backend/resources/js/` and compiled by Laravel Mix. This provides a simpler deployment model and eliminates CORS complexity.

5. **Authentication**: Using Sanctum for SPA authentication. The frontend and backend share the same domain, allowing stateful authentication.

6. **Error Handling**: Basic error handling implemented. Production applications should have more comprehensive error handling and logging.

## Docker Setup (Optional)

To use Docker, create a `docker-compose.yml` file in the root directory:

```yaml
version: '3.8'

services:
  app:
    build:
      context: ./backend
      dockerfile: Dockerfile
    container_name: task_app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./backend:/var/www
    networks:
      - task-network

  nginx:
    image: nginx:alpine
    container_name: task_nginx
    restart: unless-stopped
    ports:
      - "8000:80"
    volumes:
      - ./backend:/var/www
      - ./nginx/default.conf:/etc/nginx/conf.d/default.conf
    networks:
      - task-network

  db:
    image: mysql:8.0
    container_name: task_db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: task_scheduling
      MYSQL_ROOT_PASSWORD: root
      MYSQL_PASSWORD: password
      MYSQL_USER: task_user
    volumes:
      - dbdata:/var/lib/mysql
    ports:
      - "3306:3306"
    networks:
      - task-network

networks:
  task-network:
    driver: bridge

volumes:
  dbdata:
    driver: local
```

## Testing

Run backend tests:
```bash
cd backend
php artisan test
```

## Production Deployment

1. Set `APP_ENV=production` in `.env`
2. Set `APP_DEBUG=false`
3. Build frontend assets: `npm run production`
4. Run `php artisan config:cache`
5. Run `php artisan route:cache`
6. Configure web server (Apache/Nginx) to serve the application
7. Set up supervisor or systemd for queue worker

## License

This project is for internal use.

