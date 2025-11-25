# Laravel Assessment – Advanced Level (Nina.care)

A comprehensive Laravel application built with Inertia.js and Vue.js for managing users with advanced search capabilities and real-time notifications.

## 🚀 Main Features

## 🔍 Powerful Search

* Search through 1M+ users
* Search by name, email, or address
* Optimized SQL queries for fast results
* Debounced search + instant search on Enter

## 👤 User Management

* Add, edit, and delete users
* Full address details for each user
* Pagination + form validation
* Clean UI built with TailwindCSS

## 🔔 Notifications

* Event-driven notifications on user updates
* Notification dropdown
* Mark notifications as read
* Toast messages for updates

## 🖥️ Dashboard (Inertia.js + Vue.js)

* Smooth and responsive interface
* Modals for details & delete confirmation
* Loading states and animations

## 🛠️ Tech Stack

### Backend
- **Laravel 12** - PHP Framework
- **SQLite** (default) or **MySQL** - Database
- **Inertia.js** - Server-side routing for SPAs
- **Laravel Events & Listeners** - Event-driven architecture

### Frontend
- **Vue.js 3** - Progressive JavaScript Framework
- **Inertia.js** - SPA framework
- **Tailwind CSS** - Utility-first CSS framework
- **Vite** - Build tool

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

**Note:** Seeding 1 million users may take several minutes. The seeder uses chunking and memory optimization to handle large datasets efficiently.

### 8. Build frontend assets

For development:
```bash
npm run dev
```

For production:
```bash
npm run build
```

### 9. Start the development server

```bash
php artisan serve
```

Visit `http://localhost:8000` in your browser.

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


