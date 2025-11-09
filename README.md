# Task Scheduling & Notification Platform

## Setup

1. Install PHP dependencies:
```
composer install
```

2. Install Node dependencies:
```
npm install
```

3. Create environment file:
```
cp .env.example .env
```

4. Generate application key:
```
php artisan key:generate
```

5. Configure database in `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tsp
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

6. Configure Redis in `.env`:
```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

7. Run migrations:
```
php artisan migrate
```

8. Seed the database:
```
php artisan db:seed
```

9. Build frontend assets:
```
npm run dev
```

10. Start the queue worker:
```
php artisan queue:work
```

11. Start the development server:
```
php artisan serve
```

The application will be available at `http://localhost:8000`

## Admin Credentials

After running the seeders, you can login with:

- **Email**: admin@example.com
- **Password**: password

You can find other test users in the `UserSeeder.php` file.
