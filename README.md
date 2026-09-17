# CU Student — ID Card Replacement

Student-facing replacement application, payment simulation, request history, and refund request module.

## Current flow

1. The student checks eligibility using the authenticated student identity.
2. The student selects Lost / Stolen or Damaged / Faded and uploads a JPEG/PNG passport photo.
3. Checkout creates a payment attempt only; no application exists until terminal payment completion.
4. A successful local simulation creates one `paid` application, payment transaction, and `payment_paid` event.
5. A failed terminal result creates one `failed` application. Cancelled or abandoned attempts create no application.
6. Before 72 hours, a paid unprinted application may request a refund. The refund amount is the stored base fee only.

Pages:

- `idcard/applyforidcard.php`
- `idcard/paymentcenter.php`
- `idcard/checkappstatus.php`

## Authentication and local testing

Production requires `CU_STUDENT_IDENTITY_RESOLVER`, a trusted PHP resolver returning `CU\IdCard\Identity` for the portal session `loginid`.

For local testing, root `.env` can enable the local-only bypass:

```env
CU_AUTH_BYPASS=1
CU_AUTH_BYPASS_STUDENT_MATRIC=EXISTING_STUDENT_MATRIC
```

The development matric stays server-side and all requests remain ownership-scoped to that student. Do not enable the bypass in production.

## Configuration

- `CU_STUDENT_DB_HOST`, `CU_STUDENT_DB_NAME`, `CU_STUDENT_DB_USER`, `CU_STUDENT_DB_PASS`
- `CU_STUDENT_UPLOAD_PATH` — replacement upload path override
- `CU_STUDENT_SIMULATOR_RESULT=paid|failed` — local simulator result
- `CU_STUDENT_PAYMENT_OPTION` — active `paymentoptions` row; it does not call an external gateway

POST requests require actor-bound CSRF tokens. The shared lifecycle service owns payment completion, cancellation, refund eligibility, event history, and locking. Students cannot approve refunds, credit refunds, print cards, collect cards, or access another student’s records.

