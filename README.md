# CU Student: replacement requests (Phase 3)

Plain PHP/PDO, existing dedicated pages, shared layout, vanilla JavaScript and CU styling. Requires the Phase 2 migration already supplied under `../idcard-system/database/migrations/002_replacement_lifecycle.sql`. Phase 3 adds no schema migration and does not rewrite legacy records.

## Flow

Authenticated student -> own matric validation -> shared active/cooldown checks -> Lost / Stolen or Damaged / Faded -> required JPEG/PNG passport photo -> checkout attempt -> local simulated terminal paid/failed result -> application, transaction and event created atomically by the shared Lifecycle service.

`damaged` remains the stored reason for Damaged / Faded. Supporting documents have no input, validation or processing in the new flow. Historical files and columns are retained. Photo validation checks real upload origin, MIME, image dimensions and configured byte limits. New files use random names. The attempt snapshots the photo path and it is copied to the application only at payment completion. Redundant photos from repeated checkout submissions are removed; photos owned by cancelled/abandoned attempts remain for later retention policy, without application creation.

The application form and payment page never create placeholder applications. Explicit cancellation uses the shared cancel operation. Navigating via a same-tab page link opens the cancellation dialog; browser refresh/close/back uses the browser's standard unload prompt when supported. Browser abandonment never calls a terminal payment operation. The student's pending checkout can be resumed from Payment or the eligibility gate. Refreshing a completed checkout displays its terminal result rather than paying again. Failed applications have no payment retry button; a new application may be started subject to the shared rules.

## Identity and local setup

All APIs require `currentStudent()` from `include/session.php`, which uses the Phase 2 PortalSessionAdapter. URLs/POST matric numbers are checked against the trusted identity, not used to establish it. Page shells contain no student data; anonymous API calls return 401 and pages display a sign-in notice.

Production integration: set server environment variable `CU_STUDENT_IDENTITY_RESOLVER` to a trusted PHP file returning a Closure. It receives the existing session's `loginid` and must query the authoritative CU identity/role source, returning `CU\IdCard\Identity` (including the student's actual matric number and `student` role) or null. Keep the resolver outside the public web root. No guessed numeric role mapping or separate login/password system is introduced. The host portal remains responsible for login, session regeneration and logout.

For isolated local development ONLY, set `CU_STUDENT_DEV_MODE=1` and `CU_STUDENT_DEV_MATRIC` to an existing student matric. This is server configuration, never a form or query parameter. It works only for loopback requests, only without a real session loginid, and never overrides a configured portal resolver. It is off by default. Do not enable it on a deployed/reverse-proxied portal.

Example PowerShell, from REFACTOR (replace the placeholder matric):

```powershell
$env:CU_STUDENT_DEV_MODE = '1'
$env:CU_STUDENT_DEV_MATRIC = 'EXISTING_STUDENT_MATRIC'
$env:CU_STUDENT_SIMULATOR_RESULT = 'paid'
& C:/xampp/php/php.exe -S 127.0.0.1:8088 -t .
```

Open `http://127.0.0.1:8088/cu_student/idcard/applyforidcard.php`. Ensure PHP's configured session.save_path is writable. To test terminal failure, restart the development server with `CU_STUDENT_SIMULATOR_RESULT=failed`. The browser cannot choose or override the result. Clear development environment variables before production use.

Database environment overrides: `CU_STUDENT_DB_HOST`, `CU_STUDENT_DB_NAME`, `CU_STUDENT_DB_USER`, `CU_STUDENT_DB_PASS`; defaults preserve the existing local database configuration. `CU_STUDENT_UPLOAD_PATH` is intended for isolated tests; the default preserves `uploads/idcard` and its existing database-relative paths.

## Simulated payment

The provider is explicitly `local-simulator`; no money moves and no external verification is claimed. `CU_STUDENT_SIMULATOR_RESULT` defaults to paid and also accepts failed. All monetary calculations remain in the Phase 2 service.

`CU_STUDENT_PAYMENT_OPTION` chooses the existing paymentoptions tariff row (default `paystack`) solely to reuse configured charges. It does not invoke that gateway. The UI labels the method Local simulator and shows the actual base fee, charges and total snapshot before completion. No new payment option rows or prices are seeded. A missing/inactive configured tariff is rejected.

## API

All routes are under `index.php`; responses retain success/message/data. POST requires the session's `X-CSRF-Token`, fetched through GET action=session. Tokens are random, session-bound and rotated if the resolved identity changes. Session cookies are HttpOnly and SameSite=Lax, and Secure under HTTPS. The portal owns session lifetime. Errors never return SQL, credentials or raw provider payloads.

| Method/action | Purpose |
| --- | --- |
| GET session | Current student display fields and CSRF token |
| GET eligibility, matricnumber, optional applicationtype | Shared validation plus pending-checkout reference |
| GET settings | Active reasons, fee and photo rules |
| GET applications | Own requests only; legacy ?identifier is accepted only for the same student |
| GET checkout, optional ref | Own specified or pending attempt; paymentinvoice remains an alias |
| GET paymenthistory, ref | Own application and safe stored payment fields |
| GET history, ref | Own real application events, chronologically |
| POST begincheckout | Multipart matricnumber, applicationtype, photo; submit remains a checkout-only alias |
| POST processpayment | paymentreference; shared atomic terminal completion |
| POST cancelcheckout | paymentreference; pending attempt cancellation |
| POST requestrefund | referencenumber; shared eligibility and refund creation |

No student endpoints approve refunds, credit refunds or mark cards printed/collected. Unknown and another student's application references return the same neutral error. Even student identities with additional staff roles remain ownership-scoped in this module's detail endpoints.

## Requests, modals and legacy records

`idcard/checkappstatus.php` keeps its URL and becomes Application Requests. The table has reference, clickable payment status, history action and operational status. A refund stage is shown separately when present. The right-aligned Request Refund button submits the entered reference through Lifecycle.requestRefund. Amount is the base fee only; charges remain excluded.

Payment modal fields are explicitly selected from stored transactions. Missing currency/provider/timestamps remain N/A; a legacy `successful` transaction is described as Recorded successful (legacy), not retroactively externally verified. Legacy application paymentstatus=NULL is displayed as Not available (legacy), without inferring Paid from card status.

History reads idcardapplicationevents only. No history is synthesized from statuses or updatedat. Empty legacy history displays a clear message. All dates/times display explicitly in Africa/Lagos. Native dialogs support keyboard focus and Escape; tables scroll within their container on mobile.

## Tests and phase boundary

From REFACTOR:

```powershell
python -B tests/student_phase3.py
$env:CU_STUDENT_BROWSER_TEST = '1'
python -B tests/student_phase3.py
& C:/xampp/php/php.exe tests/lifecycle_phase2.php
python -B tests/navigation_smoke.py
```

HTTP tests use a random disposable schema and temporary photo/session storage, verify live records are unchanged, and remove only their own fixtures. Optional browser checks use installed Edge in headless mode and save screenshots with fake test data under tests/artifacts. Phase 2 covers row-lock contention and duplicate completion. Navigation tests retain the original three-project route coverage, with the student heading updated.

Student Affairs and Account Officer interfaces are unchanged. Phase 4 must connect Awaiting Printing and the renderer to the shared selection/batch-item/physical-confirmation services, add the before/after filter and collection UI, while retaining the v2 guards. New paid student requests remain excluded from the legacy printing UI until that phase. Production use still requires the authoritative identity resolver; real payment integration remains intentionally out of scope.
