---
paths:
  - 'app/**'
---

# App

## Independent accounts are identified by email and role
Treat normalized email plus role as account identity; never merge or share account data because emails match. Scope authentication to the portal's permitted roles, and resolve ownership and notifications by user ID. Recovery uses role-scoped OTPs; do not enable Laravel's email-only password broker.

## Public order identifiers and printed-label compatibility
Use order_number for public order references and URL binding, and waybill_code to resolve scans. Keep numeric order IDs for internal relations and updates. Pre-migration printed labels retain their stored numeric waybill code, legacy QR, and printed order number; never renumber these labels or accept arbitrary database IDs as scan codes.
