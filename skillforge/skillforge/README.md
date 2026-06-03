# ⚡ SkillForge — Student Skill Tracker Portal
### BSCS 4th Semester | Web Technologies + DBMS Project

---

## 🗂️ Project Structure

```
skillforge/
│
├── index.php           ← Entry point (auto-redirects)
├── signup.php          ← Page 1: Registration
├── login.php           ← Page 2: Login
├── home.php            ← Page 3: Dashboard
├── profile.php         ← Page 4: Profile + Update
├── logout.php          ← Destroys session
│
├── includes/
│   ├── db.php          ← MySQL database connection
│   └── auth.php        ← Session helpers (login guard)
│
├── css/
│   └── style.css       ← All styles + media queries
│
├── js/
│   └── validate.js     ← JS validation library
│
└── database.sql        ← Run this to create the DB + tables
```

---

## 🛠️ Step-by-Step Setup Guide

### STEP 1 — Install XAMPP

1. Download XAMPP from https://www.apachefriends.org
2. Install it and open **XAMPP Control Panel**
3. Start **Apache** and **MySQL** (click the Start buttons)

---

### STEP 2 — Place the Project Files

1. Copy the entire `skillforge/` folder into:
   ```
   C:\xampp\htdocs\skillforge\       ← Windows
   /opt/lampp/htdocs/skillforge/     ← Linux
   /Applications/XAMPP/htdocs/skillforge/  ← Mac
   ```

---

### STEP 3 — Create the Database

**Option A — phpMyAdmin (easier)**
1. Open browser → go to `http://localhost/phpmyadmin`
2. Click **"New"** in the left sidebar
3. Type database name: `skillforge_db` → click **Create**
4. Click the database name → click **Import** tab
5. Click **Choose File** → select `database.sql` from the project
6. Click **Go** at the bottom

**Option B — MySQL Command Line**
```bash
mysql -u root -p < C:\xampp\htdocs\skillforge\database.sql
```

---

### STEP 4 — Configure Database Credentials

Open `includes/db.php` and update:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');      // Your MySQL username
define('DB_PASS', '');          // Your MySQL password (empty in XAMPP by default)
define('DB_NAME', 'skillforge_db');
```

---

### STEP 5 — Open the Project

Go to: **http://localhost/skillforge/**

The site auto-redirects to signup.php if you're not logged in.

---

## 📄 Pages Overview

| Page | File | What it does |
|------|------|-------------|
| 1. Sign Up | `signup.php` | Register new user with JS + PHP validation |
| 2. Login | `login.php` | Sign in with username or email |
| 3. Dashboard | `home.php` | View stats, add/delete skills |
| 4. Profile | `profile.php` | Edit info, password, avatar color |

---

## 🔐 How Security Works

| Feature | How |
|---------|-----|
| Passwords | Hashed with `password_hash()` (bcrypt) — never stored as plain text |
| SQL Injection | Prevented using **Prepared Statements** (`$stmt->bind_param`) |
| XSS | All output escaped with `htmlspecialchars()` |
| Auth Guard | `require_login()` redirects unauthenticated users |
| Duplicate check | Server-side check before INSERT |

---

## ✅ Validation — Two Layers

### Layer 1: JavaScript (Client-side) — `js/validate.js`
Runs instantly in the browser **before** form submits:
- Empty field check
- Email format check (regex)
- Password strength (uppercase + lowercase + digit + special)
- Password match check
- Username format (letters, numbers, underscore only)
- Shows red error messages under each field
- Shows green tick when valid

### Layer 2: PHP (Server-side) — in each `.php` file
Runs on the server as a safety net:
- Same checks as JS (in case JS is disabled)
- Database checks (duplicate email/username)
- Uses `filter_var()` for email
- Uses `preg_match()` for password and username

---

## 📱 Responsive Design — Media Queries

In `css/style.css`:

```css
/* Tablet — max 768px */
@media (max-width: 768px) {
  .navbar-nav { display: none; }   /* hide nav links */
  .grid-2 { grid-template-columns: 1fr; }  /* stack columns */
}

/* Mobile — max 480px */
@media (max-width: 480px) {
  .card { padding: 16px; }
  .auth-card { max-width: 100%; }
}
```

The layout automatically adjusts for phones, tablets, and desktops.

---

## 🗄️ Database Schema

### `users` table
| Column | Type | Description |
|--------|------|-------------|
| id | INT AUTO_INCREMENT PK | Unique user ID |
| full_name | VARCHAR(100) | User's real name |
| username | VARCHAR(50) UNIQUE | Login handle |
| email | VARCHAR(150) UNIQUE | Login email |
| password | VARCHAR(255) | bcrypt hashed password |
| department | VARCHAR(100) | CS, SE, IT… |
| bio | TEXT | Optional about-me |
| avatar_color | VARCHAR(7) | Hex color e.g. #6366f1 |
| created_at | TIMESTAMP | Auto set on insert |

### `skills` table
| Column | Type | Description |
|--------|------|-------------|
| id | INT AUTO_INCREMENT PK | Skill ID |
| user_id | INT FK → users.id | Owner (CASCADE DELETE) |
| skill_name | VARCHAR(100) | e.g. PHP, MySQL |
| level | ENUM | Beginner/Intermediate/Advanced/Expert |
| category | VARCHAR(50) | Programming, Database, etc. |

---

## 🧪 Test Account

After importing `database.sql`, a demo account is ready:

- **Username:** `ali_dev`
- **Password:** `Test@1234`

---

## 💡 Key Concepts You're Learning

1. **PHP Sessions** — `$_SESSION` stores logged-in user data server-side
2. **MySQLi Prepared Statements** — Safe way to run SQL with user input
3. **bcrypt Password Hashing** — Never store passwords as plain text
4. **Form Validation** — Two layers: JS (UX) + PHP (security)
5. **CSS Variables** — Reusable design tokens in `:root {}`
6. **Media Queries** — Responsive layouts for different screen sizes
7. **SQL Relationships** — `FOREIGN KEY` links skills to users
8. **CRUD Operations** — Create (INSERT), Read (SELECT), Update (UPDATE), Delete (DELETE)

---

Good luck with your project! ⚡
