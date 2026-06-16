# Panda Payroll System
### Panda Consumer Products – v1.0.0

---

## Requirements
- XAMPP / WAMP (PHP 8.1+, MySQL 5.7+)
- Web browser (Chrome / Firefox / Edge)

---

## Installation Steps

### 1. Copy files
Place the entire `panda-payroll-1` folder inside:
```
C:\xampp\htdocs\panda-payroll-1\
```

### 2. Create the database
1. Start Apache and MySQL in XAMPP Control Panel
2. Open `http://localhost/phpmyadmin`
3. Click the **SQL** tab
4. Open `sql/schema.sql`, copy all contents, paste into phpMyAdmin and click **Go**

### 3. Configure database connection
Open `config/database.php` and set:
```php
define('DB_USER', 'root');    // your MySQL username
define('DB_PASS', '');        // your MySQL password (blank for default XAMPP)
```

### 4. Set uploads folder permission
Right-click `uploads/employees/` → Properties → uncheck "Read-only"

### 5. Open the app
Visit: `http://localhost/panda-payroll-1`

**Default login:**
- Username: `admin`
- Password: `Admin@1234`

> ⚠️ Change the admin password immediately after first login (Settings → Users).

---

## File Structure
```
panda-payroll-1/
├── assets/css/app.css          ← Stylesheet
├── assets/js/app.js            ← JavaScript (live pay calculation)
├── config/app.php              ← App settings (URL, timezone)
├── config/database.php         ← DB credentials ← EDIT THIS
├── core/Auth.php               ← Login / session / roles
├── core/Database.php           ← PDO database wrapper
├── core/PayrollEngine.php      ← All salary calculation logic
├── core/helpers.php            ← Utility functions
├── modules/auth/               ← Login & logout
├── modules/employees/          ← Employee CRUD
├── modules/timecard/           ← Daily time card entry
├── modules/payroll/            ← Monthly summary
├── modules/reports/            ← Pay slips & detail sheets
├── modules/settings/           ← Products, users, departments
├── sql/schema.sql              ← Database schema (run once)
├── templates/layout.php        ← Shared sidebar/topbar
├── uploads/employees/          ← Employee photos (auto-populated)
├── bootstrap.php               ← Loads all core classes
└── index.php                   ← Dashboard
```

---

## Production Rates (configurable in Settings → Products & Rates)

| Product    | Target Mon–Fri | Target Sat | Rate Above | Rate Below |
|------------|---------------|------------|------------|------------|
| Panda 100  | 65 units      | 33 units   | Rs. 40     | Rs. 15     |
| Leafy      | 70 units      | 35 units   | Rs. 40     | Rs. 15     |
| Panda 50   | 65 units      | 33 units   | Rs. 40     | Rs. 15     |
| Softfeel   | 75 units      | 38 units   | Rs. 40     | Rs. 15     |

- Sundays: no production pay
- Rates can be changed anytime from Settings without touching code

---

## User Roles
| Role          | Permissions                                      |
|---------------|--------------------------------------------------|
| Admin         | Full access — employees, payroll, settings, users |
| Payroll Staff | Time card entry, reports, summaries (no settings) |

---

## Support / Modifications
- To add a new product: Settings → Products & Rates → Add Product
- To add an employee: Employees → Add Employee
- To change rates: Settings → Products & Rates → Edit
- All business logic is in `core/PayrollEngine.php` — easy to update
