# 🚧 Road Complaint and Resolution Tracking System (RoadFix Portal)

A full-stack PHP & MySQL based Road Complaint and Resolution Tracking System that enables citizens to report road infrastructure issues, track complaint progress, and supports staff/admin workflows through role-based dashboards, SLA monitoring, reporting, file uploads, and API integration.

---

## ✨ Features

### 👤 User (Citizen)

* User Registration & Login
* Submit Road Complaints
* Upload Complaint Images
* Track Complaint Status
* View Complaint History
* Reopen Complaints

### 👷 Staff

* View Assigned Complaints
* Update Complaint Status
* Add Resolution Remarks
* Upload Resolution Proof
* Resolve Complaints

### 👨‍💼 Admin

* Manage Users & Staff
* Assign Complaints
* Monitor Complaint Lifecycle
* View Statistics & Reports
* Track SLA Breaches
* Manage Complaint Resolution Process

### 📊 Reporting & Analytics

* Area-wise Pending Complaint Reports
* Complaint Status Tracking
* Escalation Monitoring
* Complaint History Timeline

### 🔌 API Integration

* Complaint Status API
* Area Pending API
* Dynamic Area API
* Dynamic Spot API
* Duplicate Complaint Detection API

### 🎨 Additional Features

* Dark/Light Theme Toggle
* AJAX-based Dynamic Dropdowns
* Responsive UI Design
* Secure File Upload Handling
* Session Management
* Role-Based Access Control (RBAC)

---

## 🛠 Tech Stack

### Frontend

* HTML5
* CSS3
* JavaScript
* AJAX
* jQuery
* Font Awesome

### Backend

* PHP 8+
* PDO

### Database

* MySQL / MariaDB

### Tools

* XAMPP
* phpMyAdmin
* Apache Server
* VS Code

---

## 📂 Project Structure

```text
road-complaint-system/
│
├── api/
├── assets/
├── config/
├── includes/
├── modules/
├── reports/
├── uploads/
│
├── index.php
├── login.php
├── register.php
├── logout.php
├── unauthorized.php
├── about.php
└── database.sql
```

---

## 🚀 Installation & Setup

1. Clone the repository.

```bash
git clone https://github.com/your-username/road-complaint-management-system.git
```

2. Move the project to XAMPP htdocs folder.

3. Start Apache and MySQL from XAMPP.

4. Create a database:

```sql
road_complaint_db
```

5. Import:

```text
database.sql
```

6. Configure database credentials in:

```text
config/db.php
```

7. Open:

```text
http://localhost:8082/road-complaint-system/
```

---

## 👥 Demo Accounts

### Admin

```text
Email: admin@roadcomplaint.in
Password: Admin@123
```

### Staff

```text
Email: staff@roadcomplaint.in
```

### User

```text
Email: user@roadcomplaint.in
```

---

## 📋 Database Modules

* Roles Management
* User Management
* Categories Management
* Ward Management
* Area Management
* Spot Management
* Complaint Management
* Complaint History
* Assignments
* Attachments
* User Preferences

---

## 🎯 Academic Project

---