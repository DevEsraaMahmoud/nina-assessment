# Laravel Assessment – Advanced Level (Nina.care)

![Dashboard](screenshots/1.png)

A comprehensive Laravel application built with Inertia.js and Vue.js for managing users with advanced search capabilities, real-time notifications, and production-ready architecture.

## 🚀 Main Features

### 🔍 Powerful Search

* **Fulltext Search**: MySQL fulltext indexes for fast searching across 1M+ users
* **Smart Query Detection**: Automatic email detection for exact matches
* **Optimized Performance**: Cached search results with 5-minute TTL
* **Boolean Mode**: Advanced search operators for complex queries
* **Debounced Search**: Instant search on Enter key press

### 👤 User Management

* **CRUD Operations**: Add, edit, and delete users with addresses
* **Transaction Safety**: Database transactions ensure data consistency
* **Form Validation**: Comprehensive request validation
* **Pagination**: Efficient pagination with configurable limits (10-20 per page)
* **Clean UI**: Modern interface built with TailwindCSS

### 🔔 Notifications System

* **Event-Driven Architecture**: Laravel events and queued listeners
* **Asynchronous Processing**: Non-blocking notification creation
* **Retry Logic**: Automatic retry with exponential backoff (3 attempts)
* **Real-time Updates**: Notification dropdown with unread count
* **Mark as Read**: Batch notification management

### 🖥️ Dashboard (Inertia.js + Vue.js)

* **SPA Experience**: Smooth navigation without page reloads
* **Modals**: User details and delete confirmation modals
* **Loading States**: Visual feedback during operations
* **Responsive Design**: Mobile-friendly interface

### ⚡ Performance & Optimization

* **Comprehensive Caching**: Multi-layer caching strategy with tag-based invalidation
* **Database Indexing**: Fulltext and composite indexes for optimal query performance
* **Query Optimization**: EXPLAIN query analysis and slow query detection
* **Memory Management**: Efficient chunking for large dataset operations

### 🏗️ Architecture

* **Repository Pattern**: Clean separation of data access logic
* **Service Layer**: Business logic abstraction (UserSearchService)
* **API Resources**: Consistent response formatting
* **Error Handling**: Comprehensive try-catch blocks with structured logging
* **Monitoring**: Database query logging in development

## 🛠️ Tech Stack

### Backend
- **Laravel 12** - Modern PHP Framework
- **MySQL** - Production database with fulltext search support
- **SQLite** - Development database (default)
- **Inertia.js** - Server-side routing for SPAs
- **Laravel Queues** - Asynchronous job processing
- **Laravel Events & Listeners** - Event-driven architecture
- **Laravel Cache** - Multi-driver caching (Redis, Memcached, Database, File)

### Frontend
- **Vue.js 3** - Progressive JavaScript Framework
- **Inertia.js** - SPA framework
- **Tailwind CSS** - Utility-first CSS framework
- **Vite** - Next-generation build tool

## 📋 Requirements

- PHP >= 8.2
- Composer
- Node.js >= 18.x
- npm or yarn
- SQLite (default) or MySQL >= 8.0
- Git

## 🔧 Installation

### 1. Clone the repository

```bash
git clone https://github.com/DevEsraaMahmoud/nina-assessment.git
cd nina-assessment
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Install JavaScript dependencies

```bash
npm install
```

### 4. Environment setup

```bash
cp .env.example .env
php artisan key:generate
```

### 5. Configure database

The project uses SQLite by default (no configuration needed). For MySQL, edit `.env` file and set your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nina-assessment
DB_USERNAME=your_username
DB_PASSWORD=your_password
```
**Note:** SQLite is configured by default and works out of the box. MySQL is recommended for production environments with large datasets.

### 6. Run migrations

```bash
php artisan migrate
```

### 7. Seed the database (1 million users)

```bash
php artisan db:seed
```

**Note:** Seeding 1 million users may take several minutes. The seeder uses:
- Chunked batch inserts (1000 records per chunk)
- Memory optimization (disabled query logging, garbage collection)
- Pre-generated data arrays to reduce Faker overhead
- Progress bar for visual feedback

### 8. Start queue worker (required for notifications)

Since notifications are processed asynchronously, you need to run the queue worker:

```bash
php artisan queue:work
```

Or use the development script that runs everything together:

```bash
npm run dev
```

This will start:
- Laravel development server
- Queue worker
- Log viewer (Pail)
- Vite dev server

### 9. Build frontend assets

For development:
```bash
npm run dev
```

For production:
```bash
npm run build
```

### 10. Start the development server

```bash
php artisan serve
```

Visit `http://localhost:8000` in your browser.

## 🎯 Architecture Highlights

### Repository Pattern
- **UserRepository**: Handles all user-related database operations
- **Transaction Safety**: All write operations wrapped in database transactions
- **Cache Integration**: Automatic cache invalidation on data changes

### Service Layer
- **UserSearchService**: Encapsulates search logic with caching
- **CacheHelper**: Utility class for safe cache tag operations (handles drivers that don't support tags)

### Event-Driven Notifications
- **UserUpdated Event**: Dispatched when users are updated
- **Queued Listener**: `SendUserUpdateNotification` processes notifications asynchronously
- **Retry Mechanism**: Failed jobs retry up to 3 times with 60-second backoff

### Caching Strategy
- **Search Results**: 5-minute TTL with tag-based invalidation
- **Index Data**: 1-minute TTL for dashboard data
- **Notifications**: 30-second TTL for unread notifications
- **Cache Tags**: `users`, `user-search`, `index`, `notifications`
- **Smart Invalidation**: Automatic cache clearing on data mutations

### API Resources
- **UserResource**: Consistent user data formatting
- **AddressResource**: Address data transformation
- **NotificationResource**: Notification response structure

## 🔧 Configuration

### Environment Variables

```env
# Database
DB_CONNECTION=mysql  # or sqlite
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nina-assessment
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Cache
CACHE_STORE=database  # or redis, memcached, file, array
CACHE_PREFIX=nina-cache-

# Queue
QUEUE_CONNECTION=database  # or redis, sqs, etc.

# Logging
APP_LOG_QUERIES=true  # Enable query logging in development
LOG_CHANNEL=stack
LOG_LEVEL=debug
```

### Cache Drivers

The application works with any cache driver:
- **Redis/Memcached**: Full support for cache tags (recommended for production)
- **Database/File/Array**: Automatic fallback to regular cache (tags not supported)

### Queue Configuration

For production, configure your queue connection in `.env`:

```env
QUEUE_CONNECTION=redis  # Recommended for production
```

## 📊 Database Schema

### Indexes
- **Fulltext Index**: `users(first_name, last_name, email)` for search
- **Composite Index**: `users(first_name, last_name)` for filtering
- **Foreign Keys**: `addresses(user_id)`, `notifications(user_id)`
- **Performance Indexes**: `notifications(read, created_at)`

### Tables
- `users`: User information with fulltext search support
- `addresses`: User addresses (one-to-one relationship)
- `user_activity_notifications`: Activity notifications
- `cache`: Cache storage (if using database cache driver)
- `jobs`: Queue jobs (if using database queue driver)

## 🧪 Testing

Run the test suite:

```bash
php artisan test
```

### Test Coverage
- **Feature Tests**: UserController, NotificationsController, SearchController
- **Unit Tests**: UserSearchService, User, Address, Notification models
- **Event Tests**: UserUpdated event and listener

## 📝 Development Features

### Query Logging
In development, all database queries are logged with:
- SQL statements
- Query bindings
- Execution time
- Slow query warnings (>100ms)

Disable with: `APP_LOG_QUERIES=false`

### Error Handling
- **Structured Logging**: Context arrays with relevant data
- **Error Recovery**: Graceful fallbacks on cache/database errors
- **User-Friendly Messages**: Clear error messages for end users
- **Production Safety**: No sensitive data in production logs

## 🚀 Performance Optimizations

1. **Fulltext Search**: MySQL fulltext indexes for fast text searching
2. **Caching**: Multi-layer caching reduces database load
3. **Chunked Operations**: Efficient handling of large datasets
4. **Query Optimization**: Indexed columns and optimized queries
5. **Asynchronous Processing**: Queued jobs for non-critical operations

## 📦 Project Structure

```
app/
├── Console/Commands/      # Artisan commands
├── Events/                # Event classes
├── Http/
│   ├── Controllers/       # Request handlers
│   ├── Middleware/        # HTTP middleware
│   ├── Requests/          # Form request validation
│   └── Resources/         # API resources
├── Listeners/             # Event listeners (queued)
├── Models/                # Eloquent models
├── Providers/             # Service providers
├── Repositories/          # Data access layer
├── Services/              # Business logic layer
└── Helpers/               # Utility classes

database/
├── migrations/            # Database migrations
├── seeders/               # Database seeders
└── factories/             # Model factories

resources/
├── js/                    # Vue.js components
└── views/                 # Blade templates

tests/
├── Feature/               # Feature tests
└── Unit/                  # Unit tests
```

## 🔍 Key Features Explained

### Search Implementation
- Uses MySQL `MATCH...AGAINST` with boolean mode
- Special handling for email queries (exact match)
- Cached results with intelligent invalidation
- Fallback to non-cached search on errors

### Notification System
- Event-driven: `UserUpdated` event triggers notification
- Queued processing: Non-blocking user updates
- Retry logic: Automatic retry on failures
- Cache invalidation: Notifications cache cleared on updates

### Caching Strategy
- **Tag-based**: Easy invalidation of related data
- **Driver-agnostic**: Works with any cache driver
- **Error-tolerant**: Graceful fallback on cache failures
- **TTL-based**: Time-based expiration for freshness

## 🐛 Troubleshooting

### Queue Jobs Not Processing
Make sure the queue worker is running:
```bash
php artisan queue:work
```

### Cache Tags Not Working
If using `file` or `array` cache driver, tags aren't supported. The application automatically falls back to regular cache operations.

### Slow Queries
Check the logs for slow query warnings. Ensure indexes are created:
```bash
php artisan migrate
```

### Memory Issues During Seeding
The seeder is optimized for memory, but if issues persist:
- Increase PHP memory limit: `ini_set('memory_limit', '512M')`
- Reduce chunk size in `UserSeeder.php`

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 👤 Author

**DevEsraaMahmoud**

- GitHub: [@DevEsraaMahmoud](https://github.com/DevEsraaMahmoud)

## 🙏 Acknowledgments

- Laravel Framework
- Inertia.js Team
- Vue.js Community
- Tailwind CSS

---


