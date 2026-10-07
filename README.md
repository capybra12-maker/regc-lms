# REGC Fleet Management & Shipment Tracking

A modern, cloud-based fleet operations and shipment tracking platform built with PHP 8, MySQL/MariaDB, Leaflet maps, and Bootstrap 5.

---

## 🌟 Key Features

* **Real-Time GPS Telematics**: Driver mobile GPS transmitter (`route=mobile-gps`) with screen wake-lock, displacement-based data throttling, offline waypoint caching, and 1-tap milestone checkpoints.
* **Interactive Fleet GPS Map**: Admin fleet map (`route=driver-map`) showing live positions of all active drivers with speed, heading, and cargo status.
* **Public Cargo Tracking**: Real-time customer tracking page with map visualization, delivery milestone progress bar, and digital proof-of-delivery timestamps.
* **Automated Customer Notifications**:
  * **Email**: Professional HTML tracking emails sent via SMTP with live tracking link.
  * **SMS**: Automated text messages sent via Semaphore Philippine SMS gateway, plus native direct-device SMS opening.
* **Diesel & Fuel Logging**: Trip fuel consumption tracking, mileage logs, and baseline expense analytics.
* **Role-Based Access Control**:
  * `administrator`: Complete dispatch, fleet, driver, customer, fuel, report, and user administration.
  * `driver`: Mobile workspace, delivery schedules, active route details, and GPS transmitter.
  * `warehouse`: Incoming cargo manifests and arrival schedules.
* **Production-Ready Security**: PDO prepared statements, CSRF protection, strict session cookies, security headers, and activity audit logging.

---

## 🚀 Quick Setup (Local - XAMPP)

1. Clone or place this repository into `c:\xampp\htdocs\regc-lms`.
2. Start **Apache** and **MySQL** in XAMPP.
3. Copy `.env.example` to `.env`:
   ```bash
   cp .env.example .env
   ```
4. Create the MySQL database and import [`database/schema.sql`](database/schema.sql) in phpMyAdmin or via CLI:
   ```bash
   mysql -u root regc_lms < database/schema.sql
   ```
5. Open your browser: `http://localhost/regc-lms/`

### Default Staff Credentials

| Role | Email | Password |
|---|---|---|
| **Administrator** | `admin@regc.com` | `Admin@123456` |
| **Driver** | `driver1@regc.com` | `Driver@123456` |
| **Warehouse** | `warehouse@regc.com` | `Warehouse@123` |

---

## 🌐 Deploy to Live Hosting (InfinityFree / cPanel)

1. **Database**:
   * Create a MySQL database in your hosting control panel.
   * Open phpMyAdmin, select your database, and import [`database/schema.sql`](database/schema.sql).
2. **Environment**:
   * Update `.env` with your hosting domain and database credentials (`sqlXXX.infinityfree.com`, assigned DB name, username, and password).
3. **Upload**:
   * Upload all files to the server's `htdocs/` directory via FTP (FileZilla) or GitHub Actions.
   * Exclude `bin/`, `scratch/`, and temporary logs.
4. **Free SSL**:
   * Enable Free SSL in the hosting dashboard so mobile browser Geolocation works securely.

---

## 🧪 Testing & Verification

* **Unit & Integration Suite**:
  ```bash
  php tests/operations.php
  ```
* **HTTP Smoke Tests**:
  ```bash
  php tests/http-smoke.php
  ```
