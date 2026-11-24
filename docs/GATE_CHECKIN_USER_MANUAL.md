# EventPass Namibia – Gate Check-In & Scanner User Manual

## 1. Overview
The **EventPass Gate Check-In System** is a mobile-optimised web application engineered for entrance gate marshals, security staff, and venue coordinators across Namibian stadiums, exhibition halls, and concert arenas.

URL: `https://your-domain.com.na/gate-scanner`

---

## 2. Gate Staff Operating Procedure

### Step 1: Login & Event Selection
1. Open any mobile browser (Safari on iOS, Chrome on Android).
2. Navigate to `/gate-scanner`.
3. Select your assigned event from the dropdown menu (e.g. *Windhoek International Music Festival 2026*).

### Step 2: Camera Activation
1. Tap **Activate Camera**.
2. When prompted by your smartphone, tap **Allow** to grant camera permissions.
3. Align the camera viewfinder over the QR code on the attendee's smartphone or printed ticket pass.

### Step 3: Instant Status Verification

| Status Signal | Visual Appearance | Meaning & Gate Marshal Action |
| :--- | :--- | :--- |
| **ENTRY APPROVED** | **Green Alert Box** with Checkmark `✅` | Authentic pass. Admit attendee. Announces attendee name and tier category (e.g., General, VIP, VVIP). |
| **ALREADY USED** | **Amber Warning Box** with `⚠️` | **DUPLICATE SCAN DETECTED.** Pass was already scanned earlier. **DENY ENTRY** immediately to prevent pass sharing. |
| **CANCELLED / REFUNDED** | **Red Alert Box** with `🚫` | Ticket was cancelled, revoked, or refunded by administrator. **DENY ENTRY**. |
| **INVALID TICKET** | **Red Alert Box** with `❌` | Code is not recognised in EventPass database. Potential counterfeit. Direct attendee to EventPass Box Office desk. |

---

## 3. Manual Fallback Input
If an attendee's screen is heavily cracked, dimmed, or wet:
1. Locate the alphanumeric booking reference under the QR code (e.g., `EP-NAM-98234-VIP`).
2. Type the code into the **Manual Ticket / QR Code** input field.
3. Tap **Verify** to validate status immediately.

---

## 4. Offline Connectivity Fallback (Stadiums & Remote Venues)
When hosting events in areas with poor mobile reception (e.g. rural festivals or high-density stadiums with network congestion):
- The EventPass gate app automatically pre-caches the hashed attendee roster in browser `localStorage`.
- Scans are recorded locally with millisecond timestamps and synced back to the primary database the instant connection resumes.
