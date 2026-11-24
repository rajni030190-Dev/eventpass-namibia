# EventPass Namibia – WordPress Production Deployment & Setup Guide

## 1. System Requirements
- **Web Server:** Nginx or Apache (with `mod_rewrite` enabled).
- **PHP Version:** PHP 8.1 or 8.2 (with `ext-curl`, `ext-mbstring`, `ext-openssl`, `ext-gd` or `ext-imagick`).
- **Database:** MySQL 8.0+ or MariaDB 10.6+.
- **WordPress Version:** 6.4 or higher.
- **SSL Certificate:** Strict HTTPS is mandatory for secure payment redirects and HTML5 camera permissions.

---

## 2. Theme & Plugin Deployment Steps

### Step 1: Upload Theme
1. Upload the directory `D:\2026\eventpass-namibia\wordpress\wp-content\themes\eventpass-theme` into your server's `wp-content/themes/` directory.
2. In the WordPress Admin, navigate to **Appearance &rarr; Themes** and activate **EventPass Namibia**.

### Step 2: Upload Core Marketplace Plugin
1. Upload `D:\2026\eventpass-namibia\wordpress\wp-content\plugins\eventpass-core` into `wp-content/plugins/`.
2. In the WordPress Admin, navigate to **Plugins &rarr; Installed Plugins** and activate **EventPass Namibia Core Platform**.

---

## 3. Page Templates Assignment (All 16 Required Pages)

In **Pages &rarr; Add New**, create the following pages and select the corresponding template from the **Page Attributes &rarr; Template** dropdown:

| Page Title | Slug | Assigned Template |
| :--- | :--- | :--- |
| **Home** | `/` | *Front Page Template* |
| **Explore Events** | `/explore-events` | *Explore Events* |
| **Event Categories** | `/categories` | *Event Categories* |
| **About Us** | `/about-us` | *About Us* |
| **How It Works** | `/how-it-works` | *How It Works* |
| **List Your Event** | `/list-your-event` | *List Your Event* |
| **Organiser Registration** | `/organiser-registration` | *Organiser Registration* |
| **Customer Registration** | `/customer-registration` | *Customer Registration* |
| **Customer Login** | `/customer-login` | *Customer Login* |
| **Contact Us** | `/contact-us` | *Contact Us* |
| **FAQs** | `/faqs` | *FAQs* |
| **Terms and Conditions** | `/terms-and-conditions` | *Terms and Conditions* |
| **Privacy Policy** | `/privacy-policy` | *Privacy Policy* |
| **Refund and Cancellation Policy**| `/refund-and-cancellation-policy` | *Refund and Cancellation Policy* |
| **Organiser Terms and Conditions** | `/organiser-terms` | *Organiser Terms and Conditions* |
| **Customer Dashboard** | `/customer-dashboard` | *Customer Dashboard* |
| **Organiser Dashboard** | `/organiser-dashboard` | *Organiser Dashboard* |
| **Gate Check-In Scanner** | `/gate-scanner` | *Gate Check-in & Scanner* |

---

## 4. WooCommerce Configuration
1. Navigate to **WooCommerce &rarr; Settings &rarr; General**:
   - Currency: **Namibian Dollar (N$)**
   - Currency Position: **Left with space (N$ 150.00)**
2. Navigate to **WooCommerce &rarr; Settings &rarr; Payments**:
   - Enable **Finatic Namibia Payments**.
   - Input your **Merchant ID** and **API Secret Key** provided by Finatic Windhoek.
   - Set Webhook URL to: `https://your-domain.com.na/wc-api/WC_Gateway_Finatic`.

---

## 5. Testing the Complete User Journey
1. **Browse Events:** Visit `/explore-events` and filter by category or town.
2. **Select Tier:** Visit any event, choose ticket quantities (e.g. 1x VIP).
3. **Checkout:** Fill attendee details, proceed to payment.
4. **Ticket Issuance:** Confirm digital pass with unique QR code is displayed and stored in `/customer-dashboard`.
5. **Gate Scan:** Open `/gate-scanner` on smartphone, scan the QR code. Confirm green `ENTRY APPROVED` banner.
6. **Duplicate Check:** Scan the same ticket again. Confirm amber `ALREADY USED (DUPLICATE)` alert.
