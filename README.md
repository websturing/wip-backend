# WIP Backend - Enterprise Resource API

This is the core API service for the WIP System, built with **Laravel 11**. It manages business logic, data persistence, and secure communication for the frontend application.

## 🚀 Key Features
- **Modular Architecture:** Organized by features for high maintainability.
- **RESTful API:** Clean and predictable resource endpoints.
- **Authentication:** Secure access using Laravel Sanctum/Passport.
- **Automated Seeding:** Custom scripts for rapid development environment setup.

## 🛠 Tech Stack
- **Framework:** Laravel 11 (PHP 8.3)
- **Database:** MySQL / PostgreSQL
- **Architecture:** Feature-based Design Pattern
- **Tools:** Docker, PHPUnit for Testing

## ⚙️ Installation
1. Clone the repository
2. Run `composer install`
3. Copy `.env.example` to `.env` and configure your database
4. Run `php artisan migrate --seed`
5. Start server: `php artisan serve`