# Project Management System - Multi-Tenant Architecture

![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat&logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat&logo=php)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat&logo=mysql)

A Laravel-based multi-tenant project management system with database-level tenant isolation.

## ✨ Features
- **🔒 Database-level tenant isolation** - Separate database per company
- **🔑 Token-based tenant identification** - Secure authentication
- **👥 Role-based access control** - Admin and member roles
- **📊 Project & task management** - Complete PM features
- **🚀 One-command setup** - `php artisan tenant:seed-all`

## 🏗️ Architecture
1. **Central Database** (`ProjectManagement`)
   - `users` - System administrators
   - `tenants` - Company configurations

2. **Tenant Databases** (Separate per company)
   - `members` - Company employees
   - `projects` - Company projects
   - `tasks` - Project tasks
   - `personal_access_tokens` - API tokens

## 🎯 Tenant Identification
**Token format**: `"tenant_id|member_id|random_token"`
- `1` = tenant_id (database selector)
- `123` = member_id (user in tenant DB)
- `abc123...` = secure random token

**Authentication Flow**:
1. Extract `tenant_id` from token
2. Switch to tenant database
3. Validate token in tenant DB
4. Authenticate member

## 🚀 Quick Start
```bash
git clone https://github.com/azhar11022/ProjectManager.git
cd ProjectManager
composer install
cp .env.example .env
php artisan key:generate

# 1. Run central database migrations
php artisan migrate

# 2. Seed all data (creates tenants, tenant databases, runs migrations, and seeds demo data)
php artisan tenant:seed-all

php artisan serve


# demo data

🧪 Demo Data
After php artisan tenant:seed-all:

Tenants Created:
Tech Corp (db_tech)

Admin: azhar@example.com / password123

Members: member1@tech.com, member2@tech.com

2 projects, 10 tasks

Market Pro (db_market)

Admin: ali@example.com / password123

Members: member1@market.com, member2@market.com

2 projects, 10 tasks
