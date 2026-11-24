# Finatic Namibia Payment Gateway – Technical Integration Specification

## 1. Provider Profile & Regulatory Standing
- **Entity:** Finatic Technologies (Pty) Ltd.
- **Physical Office:** August Schmerenbeck Haus, 1st Floor, Office 7, cnr of Independence & Sam Nujoma Avenue, Windhoek, Namibia.
- **Support Contacts:** `sales@finatic.africa` | `support@finatic.africa` | Tel: +264 83 720 1500.
- **Regulatory Status:** Authorized National Payment System (NPS) participant regulated by the Bank of Namibia (BON).
- **Settlement Currency:** Namibian Dollar (NAD / N$) with 1:1 ZAR clearing parity.

---

## 2. Architecture & Security Requirements

### A. Non-Storage of Primary Account Numbers (PAN)
EventPass Namibia utilizes a **Hosted Redirect / Secure Payment Modal** pattern. The attendee is securely redirected to Finatic's PCI-DSS Level 1 certified payment environment to input debit or credit card credentials. **No card numbers, CVVs, or expiration dates ever touch or persist on the WordPress database.**

### B. Independent Verification Rule
Per Section 7 of the EventPass Namibia Scope of Work:
> *"The platform must not issue paid tickets based solely on a customer returning from a payment page. Payment must be independently verified."*

To satisfy this condition, EventPass implements a two-stage verification:
1. **Server-to-Server Instant Payment Notification (IPN / Webhook):** Finatic posts an encrypted status payload directly to `/wc-api/WC_Gateway_Finatic`.
2. **Cryptographic Signature Verification:** EventPass calculates an `HMAC-SHA256` digest of the transaction parameters using the merchant secret key. If the hash matches, the order transitions to `Processing` or `Completed`, and the digital ticket generation engine triggers.

---

## 3. Webhook Verification Formula

```php
$signature_payload = $merchant_id . '|' . $order_id . '|' . $amount . '|' . $timestamp;
$expected_signature = hash_hmac('sha256', $signature_payload, $secret_key);

if (!hash_equals($expected_signature, $received_signature)) {
    // REJECT - Fraud or payload tampering attempt
    status_header(403);
    exit('Signature verification failure.');
}
```

---

## 4. Merchant Onboarding & Go-Live Checklist for EventPass Admin
1. **Corporate KYC:** Submit EventPass Namibia Certificate of Incorporation, Founding Statement, and Windhoek commercial bank confirmation letter to Finatic onboarding.
2. **Merchant Account Credentials:** Receive `Merchant ID`, `Public API Key`, and `Secret HMAC Key`.
3. **Configure WooCommerce:** Enter credentials into **WordPress Admin &rarr; WooCommerce &rarr; Settings &rarr; Payments &rarr; Finatic Namibia**.
4. **Sandbox Simulation:** Run end-to-end test purchases using Finatic test cards (Successful, Insufficient Funds, 3D-Secure challenge).
5. **Production Switch:** Toggle off "Sandbox Mode" and enable live settlement.
