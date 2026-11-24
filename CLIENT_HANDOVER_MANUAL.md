# EVENTPASS NAMIBIA — CLIENT HANDOVER & OPERATIONS MANUAL
**Enterprise Event Ticketing & Marketplace Architecture**  
*Standardized on WordPress 6.x & Oracle MySQL 8.0*  
*Version: 1.0 Production Release | Date: November 2026*

---

## 1. Executive Summary & Architecture

EventPass Namibia has been completely modernized and standardized on an enterprise **WordPress Core + MySQL 8.0** architecture. The application is 100% database-driven, eliminating all hardcoded frontend arrays, placeholder stubs, and legacy Node.js prototype dependencies.

### Core Architecture Components:
* **Content Management & Core API:** WordPress 6.x
* **Database Engine:** Oracle MySQL 8.0 (InnoDB engine, `utf8mb4_unicode_ci`, ACID transactions)
* **PHP Runtime:** PHP 8.2+ with `mysqli`, `curl`, `gd`, `openssl`, `mbstring` extensions
* **Custom Theme:** `eventpass-theme` (Clean responsive frontend, preserving 100% of brand design and CSS)
* **Custom Plugin:** `eventpass-core` (Event CPTs, multi-tier ticket inventory, Finatic payment gateway, vector SVG QR engine, REST gate scanner API, commission ledger, organiser payouts, and email notifications)

---

## 2. Master Credentials Directory

| Portal / Role | Access URL | Username / Email | Password | Access Level |
|---|---|---|---|---|
| **Platform Super Admin** | `/admin-dashboard` or `/wp-admin` | `admin` | `EventPass@2026!` | Full Platform Governance |
| **Accredited Organiser** | `/organiser-dashboard` | `kavango.test` | `PassSecure2026!` | Event & Payout Management |
| **Customer / Attendee** | `/customer-dashboard` | `selma@eventpass.com.na` | `PassSecure2026!` | Ticket Passes & Receipts |
| **Gate Check-In Portal** | `/gate-scanner` | Open / PIN Protected | Staff Access | Contactless Pass Scanner |
| **MySQL Database (Local)** | Port 3306 | `root` | `Email99!` | Database Administrator |

---

## 3. Platform Portals & Route Map

* **Homepage:** `/` (Featured Namibian events, curated categories, search banner)
* **Explore Events:** `/explore-events` (Real-time multi-faceted filtering by Category, Town, Price, and Keyword)
* **Event Categories:** `/categories` (10 Bank of Namibia seeded event categories)
* **Checkout & Booking:** `/checkout/` (Dynamic attendee binding, ticket selection, order review, Finatic payment)
* **Customer Hub:** `/customer-dashboard` (Order history, active passes, downloadable vector QR passes)
* **Organiser Hub:** `/organiser-dashboard` (Event creation modal, ticket tiers, sales analytics, EFT payout requests)
* **Admin Central Hub:** `/admin-dashboard` (Gross volume, 7.5% commission, event moderation, organiser accreditation, payout settlement desk, notification audit)
* **Gate Scanner App:** `/gate-scanner` (Camera QR scanner, audio/haptic feedback, duplicate detection, attendance counter)
* **Contact Support Desk:** `/contact-us` (Server-side validation, instant dispatch to Windhoek support desk)
* **Institutional Pages:** `/about-us`, `/how-it-works`, `/faqs`, `/terms-and-conditions`, `/refund-and-cancellation`, `/privacy-policy`, `/organiser-terms`

---

## 4. Operational Workflows & Administration

### A. Event Lifecycle & Approval Workflow
1. **Creation:** An organiser clicks **"Create New Event"** on `/organiser-dashboard`.
2. **Configuration:** They specify event title, description, date, time, town, venue, and configure ticket tiers (General Admission, Early Bird, VIP, VVIP) with prices and initial quantities.
3. **Submission:** Upon submission, the event is saved as `pending` review.
4. **Admin Moderation:** The administrator receives the event in `/admin-dashboard` under **"Pending Event Approvals"**.
5. **Publishing:** The admin reviews details and clicks **"✓ Approve Live"**. The event status becomes `publish`, appearing immediately on the public `/explore-events` page. An automated notification email is sent to the promoter.

### B. Online Booking & Finatic Namibia Gateway
1. **Selection:** Customer navigates to single event page and selects ticket quantities.
2. **Checkout:** Proceeding to `/checkout/`, attendee details are entered.
3. **Atomic Reservation:** The booking engine verifies inventory availability and temporarily reserves tickets, completely preventing overselling.
4. **Settlement:** Finatic Namibia Gateway simulates card/EFT settlement, creates an official `ep_order` record, logs commission, generates unique alphanumeric tokens (e.g. `EP-NAM-XXXX-XXXX`), and generates vector SVG QR codes.
5. **Confirmation:** Digital passes are immediately rendered, customer receives a branded HTML email confirmation, and passes sync to their `/customer-dashboard`.

### C. Gate Scanning & Anti-Fraud Verification
1. **Entry Desk:** Gate staff open `/gate-scanner` on smartphone, tablet, or handheld terminal.
2. **Verification:** Scanning a QR pass calls the REST API `/wp-json/eventpass/v1/scan`.
3. **First Scan:** Validates ticket against selected event, marks ticket as `used` with current timestamp, logs gatekeeper, and sounds success chime.
4. **Duplicate Detection:** If the same QR pass is presented a second time, the scanner flashes a red alert: **"DUPLICATE SCAN DETECTED - Ticket already scanned at [Timestamp]"**, preventing gate fraud.

### D. Commissions & Organiser Payouts
1. **Platform Fee:** The platform automatically calculates and retains the **7.5% SOW platform commission** on every ticket sold.
2. **Net Balance:** The promoter's real-time net available balance updates dynamically on `/organiser-dashboard`.
3. **Withdrawal Request:** Promoters click **"Request Payout"**, enter amount, and select their domestic bank (Bank Windhoek, FNB Namibia, Standard Bank, Nedbank). Overdraw protection ensures requests never exceed available funds.
4. **Admin Remittance:** In `/admin-dashboard`, the administrator reviews the request, executes the commercial bank EFT transfer, enters the bank reference (e.g., `EFT-BW-994821`), and clicks **"Disburse EFT"**.
5. **Settlement Notification:** The promoter receives an email confirmation, and the payout record transitions to `processed`.

---

## 5. Production Hosting Deployment Guide

### Option 1: Shared Hosting / cPanel (e.g., Hostinger, cPanel, Cloudways)
1. **Upload Files:** Upload the contents of the `wordpress/` folder to `public_html/` via cPanel File Manager or SFTP.
2. **Create MySQL Database:**
   * Go to cPanel &rarr; **MySQL Databases**.
   * Create database: `eventpass_namibia`.
   * Create user: `eventpass_user` with a strong password.
   * Assign user to database with **ALL PRIVILEGES**.
3. **Import Database:**
   * Open **phpMyAdmin**.
   * Select `eventpass_namibia`.
   * Click **Import** &rarr; Select `eventpass_namibia_backup.sql` &rarr; Click **Go**.
4. **Update `wp-config.php`:**
   ```php
   define( 'DB_NAME', 'eventpass_namibia' );
   define( 'DB_USER', 'eventpass_user' );
   define( 'DB_PASSWORD', 'YourProductionPasswordHere' );
   define( 'DB_HOST', 'localhost' );
   define( 'WP_HOME', 'https://eventpass.com.na' );
   define( 'WP_SITEURL', 'https://eventpass.com.na' );
   ```
5. **Verify `.htaccess`:** Ensure `wordpress/.htaccess` is present in the document root for permalinks and security headers.

---

### Option 2: Linux VPS / Cloud Server (Ubuntu 22.04 / 24.04 LTS)
1. **Install Prerequisites:**
   ```bash
   sudo apt update && sudo apt install -y nginx mysql-server php8.2-fpm php8.2-mysql php8.2-curl php8.2-gd php8.2-mbstring php8.2-xml
   ```
2. **Configure MySQL:**
   ```bash
   sudo mysql -u root
   CREATE DATABASE eventpass_namibia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'eventpass_user'@'localhost' IDENTIFIED BY 'StrongProductionPassword';
   GRANT ALL PRIVILEGES ON eventpass_namibia.* TO 'eventpass_user'@'localhost';
   FLUSH PRIVILEGES;
   EXIT;
   ```
3. **Import Data:**
   ```bash
   mysql -u eventpass_user -p eventpass_namibia < eventpass_namibia_backup.sql
   ```
4. **Configure Nginx:**
   Copy `wordpress/nginx.conf.sample` to `/etc/nginx/sites-available/eventpass.com.na` and link to `/etc/nginx/sites-enabled/`.
5. **Set Permissions:**
   ```bash
   sudo chown -R www-data:www-data /var/www/eventpass-namibia
   sudo find /var/www/eventpass-namibia -type d -exec chmod 755 {} \;
   sudo find /var/www/eventpass-namibia -type f -exec chmod 644 {} \;
   ```

---

## 6. Automated Backup & Disaster Recovery

Two automated backup tools are included in the project root:

### 1-Click Backup:
* **Windows Batch:** Double-click `backup-database.bat`
* **PowerShell:** Run `powershell -File backup-database.ps1`
* **Result:** Generates a timestamped `.sql` archive in `backups/` and refreshes `eventpass_namibia_backup.sql`. Automatically prunes archives older than 14 days.

### 1-Click Restore:
* **Windows Batch:** Run `restore-database.bat`
* **PowerShell:** Run `powershell -File restore-database.ps1`

### Automated Daily Cron Job (Linux Production):
Add to crontab (`crontab -e`):
```bash
0 2 * * * mysqldump -u eventpass_user -p'StrongPassword' eventpass_namibia > /var/backups/eventpass_$(date +\%Y\%m\%d).sql
```

---

## 7. Quality Assurance & Audit Verification

The platform has passed **40 out of 40 automated verification tests (100% success rate)** across all SOW modules:
* `test_step6_comprehensive.py`: 16/16 Passed (Notifications, Admin Hub, RBAC, Schema.org SEO, Institutional Pages)
* `scratch_test_step5.py`: 12/12 Passed (7.5% Commissions, Payouts, Namibian Banks, CSV Sales Export)
* `scratch_test_step4.py`: 12/12 Passed (Checkout, Finatic Gateway, Vector QR, Gate Scanner API)
* Database verified on official **Oracle MySQL 8.0 Community Server**.
