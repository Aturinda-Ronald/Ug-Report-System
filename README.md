# Uganda High School Results & Report Card System

A web-based academic records management system designed for Ugandan secondary schools. It handles student mark entry, grade calculation, report card generation, and provides role-based dashboards for administrators, teachers, and students.

## Features

- **Mark Entry** — Record BOT, MID, and EOT assessment marks per subject per term
- **Grading** — Uganda-specific grading scales (UCE O-Level D1–F9, UACE A-Level A–F)
- **Report Cards** — Generate and export student report cards with positions and divisions
- **Role-Based Access** — Super Admin, School Admin, Staff, and Student portals
- **Multi-School Support** — Isolated data per school (multi-tenant architecture)
- **Analytics Dashboard** — Grade distributions, subject averages, class performance
- **Student Portal** — Students can view their own results and report cards
- **Bulk Import** — Import students in bulk
- **Mobile Responsive** — Works on phones and tablets

## Tech Stack

- **Backend:** PHP 8.0+
- **Database:** MySQL (via PDO)
- **Frontend:** HTML5, CSS3, Vanilla JavaScript
- **Server:** Apache (XAMPP)

## Requirements

- PHP 8.0 or higher
- MySQL 5.7 or higher
- Apache with mod_rewrite enabled
- XAMPP (recommended for local development)

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/Aturinda-Ronald/Ug-Report-System.git
```

Place the folder inside your XAMPP `htdocs` directory and rename it to `reports_system`:

```
C:\xampp\htdocs\reports_system\
```

### 2. Set up the database

1. Start XAMPP and open **phpMyAdmin** at `http://localhost/phpmyadmin`
2. Create a new database named `uganda_results`
3. Import the schema:
   - Go to **Import** → select `database/schema-fixed.sql` → click **Go**
4. Import the seed data (optional, for demo data):
   - Import `database/seed-fixed.sql`

### 3. Configure the application

Open `config/config.php` and update the database settings if needed:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'uganda_results');
define('DB_USER', 'root');
define('DB_PASS', ''); // your MySQL password
```

### 4. Access the application

Open your browser and go to:

```
http://localhost/reports_system/public/
```

## User Roles

| Role | Access |
|------|--------|
| `SUPER_ADMIN` | Manages all schools and system-wide settings |
| `SCHOOL_ADMIN` | Manages students, staff, subjects, and reports for their school |
| `STAFF` | Enters and views marks for assigned subjects/classes |
| `STUDENT` | Views their own results and report cards |

## Project Structure

```
reports_system/
├── api/              # JSON endpoints for AJAX requests
├── app/Models/       # ORM models (BaseModel, Student, User)
├── assets/           # CSS, JS, and static images
├── config/           # App config, constants, helper functions
├── database/         # SQL schema and seed files
├── lib/              # Grade calculation, auth guards, result computation
├── public/           # Web root
│   ├── admin/        # Admin dashboard pages
│   ├── auth/         # Login/logout pages
│   ├── student/      # Student portal
│   └── super/        # Super admin pages
└── views/            # Shared layout templates
```

## VirtualHost Setup (Optional)

To run the app on a clean URL like `http://localhost:8082`, follow the instructions in `VIRTUALHOST_SETUP.md`.

## License

See [LICENSE](LICENSE) for details.
