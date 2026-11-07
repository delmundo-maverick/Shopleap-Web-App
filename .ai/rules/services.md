---
paths:
  - app/Services/SellerProductImages.php
  - app/Services/LogisticsDashboardService.php
---

# Services

## Duplicated product drafts share image files
Product duplication creates new image and variant records referencing the existing R2 paths. Delete a file only after its transaction commits and no product_images.path or product_variants.image_path still references it. Use SellerProductImages::deleteUnused for cleanup; deleting one product must not break its duplicate.

## Distinguish live workloads from dated activity
The dashboard range controls recorded event activity and delivered_at totals. Current queues, alerts, active sorting sessions and rider workloads stay live across ranges. No daily backlog snapshots exist: neutral trends compare recorded activity today versus yesterday, not reconstructed queue sizes. Count distinct parcels per day, exclude synthetic stale events, and scope each source to HubContext using existing heldBy, bag direction and hub-ID rules.
