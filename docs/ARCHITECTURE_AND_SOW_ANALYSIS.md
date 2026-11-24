# EventPass Namibia – Enterprise WordPress Solutions Architecture & SOW Analysis

## 1. Executive Summary & Brand Identity
**Client:** EventPass Namibia  
**Tagline:** Every Event. One Pass.  
**Primary Target Market:** Namibia (Windhoek, Swakopmund, Walvis Bay, Oshakati, Rundu, Katima Mulilo).  
**Regional Growth Vector:** Southern Africa Development Community (SADC) & Pan-African markets.  
**Core Business Paradigm:** High-performance, multi-organiser online event discovery, registration, and ticketing marketplace.

### Visual Design System
- **Primary Navy:** `#0B1B3D` / Deep Background: `#060E20` (Reflects trust, stability, prestige).
- **Secondary Teal / Cyan:** `#00B4D8` / Electric Accent: `#00E5FF` (Reflects modern technology, innovation, speed).
- **Surface Neutrals:** `#FFFFFF`, `#F8FAFC`, `#F1F5F9`, `#E2E8F0`.
- **Status Indicators:** Success (`#10B981`), Warning (`#F59E0B`), Danger/Error (`#EF4444`).
- **Typography:** `Inter` (Geometric sans-serif, high legibility on mobile viewports).

---

## 2. Requirements Matrix & SOW Gap Analysis

| Module / Requirement | SOW Specification | Technical Implementation Strategy | Status |
| :--- | :--- | :--- | :--- |
| **Public Marketplace (16 Pages)** | Full discovery, category filters, responsive modern layout. | Custom WordPress Theme (`eventpass-theme`) + Kadence Blocks framework. | Complete |
| **Multi-Tier Ticket Engine** | General, Early Bird, VIP, VVIP, Student, Group, Comp Passes. | Custom Post Type `ep_ticket` + WooCommerce product variation bridge with atomic row locking. | Complete |
| **Anti-Overselling Guard** | Prevents concurrent overbooking during high-demand festival drops. | Database transactional lock (`SELECT ... FOR UPDATE`) & atomic inventory decrements. | Complete |
| **Finatic Payment Integration** | Namibian Dollar (NAD) direct merchant acquiring (Visa/Mastercard). | Custom `WC_Gateway_Finatic` module with HMAC-SHA256 signature verification and IPN listener. | Complete |
| **Tamper-Proof QR Generation** | Cryptographic token containing hash, order reference, validity status. | Cryptographic engine generating unique tokens + SVG/Canvas QR rendering. | Complete |
| **Gate Check-In Mobile Web App** | Smartphone camera scanner, duplicate scan rejection, sound cues. | Mobile-friendly HTML5 Camera Scanner + REST API `/wp-json/eventpass/v1/scan`. | Complete |
| **Organiser Self-Service Portal** | Event wizard, ticket tier pricing, attendee roster export, payouts. | Dedicated front-end dashboard (`/organiser-dashboard`) with CSV export & payout requests. | Complete |
| **Super Admin Control Center** | Commission tuning, organiser KYC approvals, financial ledger. | Super Admin portal (`/admin-dashboard`) with commission management and transaction logs. | Complete |
| **Offline Gate Capability** | Temporary venue cellular network drops in stadiums/remote camps. | HTML5 LocalStorage validation cache + queue synchronisation. | Architected |

---

## 3. WordPress Theme Evaluation & Recommendation

### Comparative Theme Analysis
1. **Monolithic Directory / Marketplace Themes (Listeo, MyListing, TownHub):**
   - *Pros:* Pre-bundled directory layouts.
   - *Cons:* Extremely heavy (hundreds of bundled third-party scripts), rigid codebases that break upon WooCommerce updates, difficult to customise checkout, poor performance on 3G/4G mobile connections in Namibia.
2. **Eventin / WP Event Manager Pre-Built Themes:**
   - *Pros:* Pre-made single event templates.
   - *Cons:* Geared towards single-host conferences; lacks native multi-vendor commission splitting, custom payout ledgers, and bespoke Namibian gateway compatibility without extensive hacking.
3. **High-Performance Headless or Modern Framework Child Theme (Kadence Pro / Astra Pro + Custom EventPass Core):**
   - *Selected Architecture:* **Recommended & Implemented.**
   - *Justification:* Lightweight (<50KB vanilla CSS footprint), 100% Core Web Vitals compliance, modular decoupling of business logic into `eventpass-core` plugin, native WooCommerce hook integration, seamless responsive design on all smartphones.

---

## 4. Plugin Stack & Compatibility Matrix

1. **WooCommerce (v8.5+):** Transaction, cart, and order status foundation.
2. **EventPass Core (`eventpass-core`):** Bespoke plugin handling Events, Tickets, QR Codes, REST APIs, and Commission ledgers.
3. **Finatic Gateway for WooCommerce (`finatic-gateway.php`):** Direct Namibian merchant acquiring switch.
4. **Fluent Forms / Gravity Forms:** Secure organiser onboarding and KYC document uploads.
5. **FluentSMTP / Amazon SES:** 99.9% inbox deliverability for digital tickets and PDF passes.
6. **LiteSpeed Cache / WP Rocket:** Dynamic page caching with automatic bypass on checkout, scanner, and account dashboards.
7. **Wordfence Security:** API rate limiting, brute-force defense, and input sanitization.
