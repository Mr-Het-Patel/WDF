# CHARUSAT Student Hub Portal — WDF Practicals 1–14

> **Student ID:** 25DE082 | **Program:** B.Tech Computer Engineering | **University:** CHARUSAT (Charotar University of Science and Technology)

---

## Project Overview

**CHARUSAT Student Hub** is a full-stack university web portal developed as part of the **Web Development Fundamentals (WDF)** course. The project spans **Practicals 1–14** and covers the complete web development lifecycle — from requirement analysis and semantic HTML through to PHP/MySQL backend APIs and administrative CRUD.

**Live Stack:** PHP 8.2 · MariaDB 10.4 · Bootstrap 5.3 · Vanilla JS (ES6+)

---

## Features by Practical

| # | Practical | Key Features |
|---|-----------|-------------|
| 1 | Requirement Analysis & Sitemap | 16-page sitemap, wireframe, project structure |
| 2 | Semantic HTML5 Pages | `<header>`, `<nav>`, `<main>`, accessible forms, 10+ pages |
| 3 | Responsive UI | Bootstrap 5 Grid, CSS Flexbox, mobile-first, 3 breakpoints |
| 4 | JavaScript DOM & Interactivity | FAQ accordion, content slider, modal, dark/light theme toggle |
| 5 | Form Validation | Regex validation, real-time feedback, password strength meter |
| 6 | Fetch API + JSON | External JSON data, async rendering, search, filter, pagination |
| 7 | PHP Form Processing | Server-side validation, CSV file storage, XSS protection |
| 8 | MySQL Schema & PDO | 3NF normalized DB, ER model, prepared statements, PDO test |
| 9 | Registration + DB Insert | `password_hash()`, duplicate check, MySQLi transaction, audit log |
| 10 | Sessions & Authentication | `password_verify()`, RBAC, 30-min timeout, remember-me token |
| 11 | Student CRUD | Admin table, search/filter, add/edit/delete modals, CSV export |
| 12 | Event Management + Uploads | Poster upload, MIME/size validation, event CRUD |
| 13 | PHP JSON API + Fetch CRUD | RESTful endpoints, async create/update/delete without reload |
| 14 | Admin Dashboard | Live SQL KPIs, role-based menus, system audit trail |

---

## Project Structure

```
WDF/
├── api/
│   └── events.php                  # RESTful PHP JSON API
├── css/
│   ├── common.css                  # Design tokens, dark mode, nav
│   ├── dashboard.css               # KPI cards, slider styles
│   ├── profile.css                 # Profile page layout
│   └── events.css                  # Event badge & filter styles
├── data/
│   └── events.json                 # External events JSON dataset (16 events)
├── js/
│   ├── common.js                   # localStorage theme switcher
│   ├── dashboard.js                # Carousel slider & notice banner
│   ├── register.js                 # Real-time Regex form validation
│   ├── events.js                   # Fetch API search, filter, pagination
│   └── events_api.js               # Async CRUD client for JSON API
├── pages/
│   ├── home.html                   # Portal landing page
│   ├── about.html                  # About + FAQ accordion
│   ├── contact.html                # Accessible contact form
│   ├── profile.html                # Student profile
│   ├── attendance.html             # Attendance analytics
│   ├── timetable.html              # Weekly timetable
│   ├── register.html               # Registration form
│   ├── login.html                  # Authentication page
│   ├── faq.html                    # FAQ knowledgebase
│   ├── feedback.html               # Feedback system
│   ├── dashboard.php               # Student dashboard (protected)
│   ├── admin_dashboard.php         # Admin control panel (protected)
│   ├── manage_students.php         # Student CRUD management
│   ├── manage_events.php           # Event management + poster uploads
│   └── events_api.php              # Async API CRUD interface
├── php/
│   ├── db_pdo.php                  # PDO database connection helper
│   ├── db_mysqli.php               # MySQLi connection helper
│   ├── test_pdo.php                # PDO connectivity test
│   ├── auth.php                    # RBAC session middleware
│   ├── login_process.php           # Login processor (password_verify)
│   ├── logout.php                  # Secure logout
│   ├── contact.php                 # Contact form → CSV storage
│   ├── register.php                # Registration → DB transaction
│   └── seed.php                    # Database re-seed utility
├── sql/
│   └── schema.sql                  # 3NF normalized schema (5 tables)
├── storage/                        # CSV data storage
├── uploads/posters/                # Uploaded event poster images
├── P1-3/                           # Original P1–3 HTML files (preserved)
└── index.php                       # Root redirect gateway
```

---

## Database Schema (`studenthub_db`)

| Table | Purpose |
|-------|---------|
| `users` | Authentication — bcrypt hash, role (`admin`/`student`), remember-me token |
| `students` | Academic profile — enrollment no, course, year, gender |
| `events` | Campus events — title, date, venue, category, poster path |
| `registrations` | Student–Event enrollment with unique constraint |
| `audit_logs` | Security audit trail — all admin actions logged with timestamp |

---

## Local Installation

### Prerequisites

- [XAMPP](https://www.apachefriends.org/) (PHP 8.2 + MariaDB included)
- Node.js (for screenshot / PDF generation scripts)

### Setup Steps

```bash
# 1. Clone the repository
git clone https://github.com/Mr-Het-Patel/WDF.git
cd WDF

# 2. Start XAMPP Apache + MySQL (or just MySQL for CLI use)

# 3. Import database schema
"C:\xampp\mysql\bin\mysql.exe" -u root -e "source sql/schema.sql"

# 4. Re-seed passwords (run once after import)
"C:\xampp\php\php.exe" php/seed.php

# 5. Start PHP development server
"C:\xampp\php\php.exe" -S localhost:8000

# 6. Open in browser
# http://localhost:8000/pages/home.html
```

---

## Default Login Credentials

| Role | Username | Password |
|------|----------|----------|
| **Admin** | `admin` | `admin123` |
| **Student** | `candidate99` | `student123` |

> Alternatively, log in using the student enrollment number `STU2026001` as the username.

---

## Key Technologies

| Layer | Technology |
|-------|-----------|
| Frontend | HTML5 (semantic), Bootstrap 5.3, Vanilla JS (ES6+), Fetch API |
| Backend | PHP 8.2, PDO, MySQLi prepared statements |
| Database | MariaDB 10.4 (XAMPP) — 3NF normalized |
| Security | `password_hash()` / `password_verify()`, RBAC, session timeout, CSRF-safe tokens |
| Storage | MySQL + CSV flat-file dual persistence |

---

## Screenshots

Automated screenshots of all 14 practicals are available in `images/screenshots/`.

---

## Submission PDF

The complete practical file submission document is included at the root of this repository:

📄 `StudentHub_Practical_File_25DE082.pdf`

---

## License

This project is submitted for academic evaluation at CHARUSAT. All code is original work of **Het Patel (25DE082)**.
