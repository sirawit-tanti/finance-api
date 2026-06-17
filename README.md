# Personal Finance Dashboard API

Backend API for the Personal Finance Dashboard application, built with Laravel 12 and MySQL.

## Overview

This project provides a RESTful API for managing personal finances, including income and expense tracking, category management, dashboard analytics, activity logs, and user administration.

The API is secured using Laravel Sanctum authentication and is deployed on Railway.

## Live Demo

Frontend Application:

https://finance-web-steel.vercel.app/

Backend API:

https://finance-api-production-dc56.up.railway.app

Health Check:

https://finance-api-production-dc56.up.railway.app/up

## Features

- User Authentication (Laravel Sanctum)
- Dashboard Analytics
- Income & Expense Tracking
- Category Management
- Activity Logging
- CSV Export
- User Management
- Role-Based Authorization
- Search & Filtering
- Pagination
- RESTful API Architecture

## Tech Stack

### Backend

- Laravel 12
- PHP 8.3
- MySQL
- Laravel Sanctum
- PHPUnit

### Infrastructure

- Railway
- GitHub Actions

## API Endpoints

### Authentication

- POST /api/register
- POST /api/login
- POST /api/logout

### Dashboard

- GET /api/dashboard/overview

### Transactions

- GET /api/transaction
- POST /api/transaction
- PUT /api/transaction/{id}
- DELETE /api/transaction/{id}

### Categories

- GET /api/category
- POST /api/category
- PUT /api/category/{id}
- DELETE /api/category/{id}

### Users

- GET /api/user
- POST /api/user
- PUT /api/user/{id}
- DELETE /api/user/{id}

## Installation

Clone the repository:

```bash
git clone https://github.com/sirawit-tanti/finance-api.git
```

Install dependencies:

```bash
composer install
```

Copy environment file:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

Configure database settings in .env

Run migrations:

```bash
php artisan migrate
```

Start development server:

```bash
php artisan serve
```

## Testing

Run automated tests:

```bash
php artisan test
```

## CI/CD

This project uses GitHub Actions for automated testing and deployment validation.

## Deployment

Production deployment is hosted on Railway.

## Author

Sirawit Tantiparinyakul

GitHub:
https://github.com/sirawit-tanti
