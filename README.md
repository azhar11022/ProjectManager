echo '# Project Management System - Multi-Tenant Architecture

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

---

## 📖 API Documentation

### Authentication APIs

#### **POST /login** - Login to get access token
```http

POST /api/login
Content-Type: application/json

{
  "email": "azhar@example.com",
  "password": "password123"
}

POST /api/register
Content-Type: application/json

{
  "name": "Admin Name",
  "email": "admin@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "company_name": "Company Name",
  "company_short_code": "company"
}

GET /api/projects
Authorization: Bearer {tenant_id}|{member_id}|{token}

POST /api/projects
Authorization: Bearer {tenant_id}|{member_id}|{token}
Content-Type: application/json

{
  "name": "Project Name"
}

GET /api/projects/1
Authorization: Bearer {tenant_id}|{member_id}|{token}

PUT /api/projects/1
Authorization: Bearer {tenant_id}|{member_id}|{token}
Content-Type: application/json

{
  "name": "Updated Project Name"
}

DELETE /api/projects/1
Authorization: Bearer {tenant_id}|{member_id}|{token}

POST /api/projects/1/tasks
Authorization: Bearer {tenant_id}|{member_id}|{token}
Content-Type: application/json

{
  "name": "Task Name",
  "duration": "5 days"
}

GET /api/projects/1/tasks/1
Authorization: Bearer {tenant_id}|{member_id}|{token}

PUT /api/projects/1/tasks/1
Authorization: Bearer {tenant_id}|{member_id}|{token}
Content-Type: application/json

{
  "name": "Updated Task Name",
  "duration": "3 days"
}

DELETE /api/projects/1/tasks/1
Authorization: Bearer {tenant_id}|{member_id}|{token}

GET /api/members
Authorization: Bearer {tenant_id}|{member_id}|{token}

GET /api/members/1
Authorization: Bearer {tenant_id}|{member_id}|{token}

POST /api/members
Authorization: Bearer {tenant_id}|{member_id}|{token}
Content-Type: application/json

{
  "name": "Member Name",
  "email": "simpleusername",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "member"
}

Note: Email should be simple username (without @domain.com)

PUT /api/members/1
Authorization: Bearer {tenant_id}|{member_id}|{token}
Content-Type: application/json

{
  "name": "Updated Name",
  "email": "updatedusername",
  "password": "newpassword123",
  "password_confirmation": "newpassword123",
  "role": "admin"
}

DELETE /api/members/1
Authorization: Bearer {tenant_id}|{member_id}|{token}

POST /api/logout
Authorization: Bearer {tenant_id}|{member_id}|{token}



# Clone repository
git clone https://github.com/azhar11022/ProjectManager.git
cd ProjectManager

# Install dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Setup databases and seed demo data
php artisan migrate
php artisan tenant:seed-all

# Start development server
php artisan serve

🧪 Demo Data
After running php artisan tenant:seed-all, you get:

Tech Corp (db_tech)
Admin: azhar@example.com / password123

Members: member1@tech.com, member2@tech.com

Data: 2 projects, 10 tasks

Market Pro (db_market)
Admin: ali@example.com / password123

Members: member1@market.com, member2@market.com

Data: 2 projects, 10 tasks


# Complete setup
php artisan migrate
php artisan tenant:seed-all

# Clear caches
php artisan optimize:clear

# View routes
php artisan route:list
📄 License
MIT License

👤 Author
azhar11022 - GitHub' > README.md
