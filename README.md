# CU Student — ID Card Replacement

Student-facing PHP module for replacement applications, status tracking, invoices, and payment history. It uses plain JavaScript and CSS, with no frontend build or Composer dependencies in this folder.

## Shared workflow

All three projects use the same `idcard_system` MySQL/MariaDB database:

1. This module creates a `submitted` application.
2. [CU Student Affairs](../cu_studentaffairs/README.md) approves it as `awaitingpayment` or rejects it.
3. This module records payment and changes the application to `paid`.
4. [ID Card System](../idcard-system/README.md) generates cards and records physical printing as `printed`.

Keep the project folders as siblings so Student Affairs can resolve their uploaded files.

## Setup

1. Use PHP 8.1+ with `pdo_mysql` and `fileinfo`, MySQL/MariaDB, and a PHP-capable server such as Apache/XAMPP.
2. Follow the [shared database setup](../idcard-system/README.md#database-setup). The printing dump alone lacks application settings and payment tables.
3. Configure [include/config.php](include/config.php) to use the shared database.
4. Ensure `students.matric_no` contains the applicant and `idcardsettings` has active `damaged` and `loststolen` rows.
5. Allow PHP to create/write `uploads/idcard/`. Set PHP upload and POST limits to accommodate two files under the configured size limit.
6. Open `http://localhost/REFACTOR/cu_student/idcard/applyforidcard.php`; adjust `/REFACTOR/` for your deployment.

## Folder guide

| Path | Purpose |
| --- | --- |
| `idcard/applyforidcard.php` | Eligibility check and replacement application. |
| `idcard/checkappstatus.php` | Application history; accepts `?identifier=...`. |
| `idcard/paymentcenter.php` | Invoice, payment options, and history; accepts `?ref=...`. |
| `index.php` | JSON API router, not a landing page. |
| `class/IDCard.php` | Application rules, uploads, invoices, and payment recording. |
| `class/General.php` | Student lookup, PDO helpers, sanitization, and responses. |
| `include/` | Database configuration and class loading. |
| `assets/js/` | Application, status, and payment page scripts. |
| `assets/css/` | Application layout and status/payment styling. |
| `assets/images/` | University branding. |
| `database/payment_setup.sql` | Payment tables and seeded option charges. |
| `uploads/idcard/` | Runtime replacement photos and supporting documents. |

## Application rules

- Reasons are `loststolen` and `damaged`; both require a photo and supporting document.
- The matriculation number must match a student record.
- An existing `submitted`, `awaitingpayment`, `paid`, `printed`, `readyforpickup`, or `acknowledged` application blocks another submission.
- Configured `cooldowndays` blocks reapplication after recently closed/cancelled requests.
- MIME allowlists and maximum file sizes come from `idcardsettings`. The upload implementation supports JPEG, PNG, and PDF, subject to each field's allowlist.
- Uploads receive generated filenames and relative database paths. Successful submission returns an `IDC-...` reference.
- Invoice lookup and payment processing expire overdue unpaid applications. Status-history lookup does not itself run expiry checks.

## API

Routes are relative to `index.php`. Responses contain `success`, `message`, and optional `data`.

| Method | Query | Input / behavior |
| --- | --- | --- |
| GET | `?identifier=...` | Student and application history; 404 for unknown student or no applications. |
| GET | `?action=settings` | Active settings; optional `applicationtype=damaged` or `loststolen`. |
| GET | `?action=paymentinvoice&ref=...` | Application and options with calculated charges/totals. |
| GET | `?action=paymenthistory&ref=...` | Recorded transactions for the reference. |
| POST | `?action=submit` | Multipart: `matricnumber` (or `identifier`), `applicationtype`, `photo`, `document` (or `supportingDocument`). |
| POST | `?action=processpayment` | JSON: `referencenumber`, `paymentoptioncode`, optional `gatewayreference`. |

Use `ref` for payment GET requests. The generic identifier handler runs first and intercepts requests containing `identifier`, even with a payment action.

## Payment behavior and limitations

Charges are `approvedfee * chargepercentage + fixedcharge`, rounded to two decimals. Percentages are fractions: `0.0150` means 1.5%. The SQL seed supplies Paystack, Flutterwave, and Remita option names; re-running it updates their charges and active flags.

These options are not connected payment gateways. The Pay button generates a local reference, and the API records a successful transaction and changes `awaitingpayment` to `paid` in a database transaction. No payment provider is contacted and receipt of money is not verified.

There is no login or application-ownership authorization in this folder. Host-portal authentication and verified gateway processing remain integration work. Pickup, collection, cancellation, and closure actions are not implemented here, although their statuses can be displayed.

## Manual verification

With disposable development data, submit for an existing student, check duplicate blocking and invalid/oversized uploads, approve in Student Affairs, load the invoice by reference, record a local test payment, and check the printing queue. Also check an expired deadline. These steps write application/payment data. No automated test suite is supplied.
