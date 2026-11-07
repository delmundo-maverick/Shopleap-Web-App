---
paths:
  - 'app/Http/Controllers/Seller/Product*.php'
---

# Seller

## Preserve review requirements and variant identities
Rejected or pending products retain requires_review through draft saves and duplication; resubmission sets pending_review, and status actions cannot activate them. A future approval handler must clear requires_review when approving. Edit existing variants by their IDs and retain all rows because carts and order items reference them. Product discounts use one unique row per product; new bulk discounts must reject live or scheduled rows instead of silently replacing them.
