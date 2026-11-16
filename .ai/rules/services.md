---
paths:
  - app/Services/SellerProductImages.php
  - app/Services/LogisticsDashboardService.php
  - app/Services/RiderEarnings.php
  - app/Services/OrderReturns.php
  - app/Services/OrderTransitions.php
---

# Services

## Duplicated product drafts share image files
Product duplication creates new image and variant records referencing the existing R2 paths. Delete a file only after its transaction commits and no product_images.path or product_variants.image_path still references it. Use SellerProductImages::deleteUnused for cleanup; deleting one product must not break its duplicate.

## Distinguish live workloads from dated activity
The dashboard range controls recorded event activity and delivered_at totals. Current queues, alerts, active sorting sessions and rider workloads stay live across ranges. No daily backlog snapshots exist: neutral trends compare recorded activity today versus yesterday, not reconstructed queue sizes. Count distinct parcels per day, exclude synthetic stale events, and scope each source to HubContext using existing heldBy, bag direction and hub-ID rules.

## Preserve delivery earnings snapshots
Rider pay is set by append-only per-hub rates at successful delivery, never by the latest global setting. OrderTransitions freezes the holding hub, courier, charged shipping fee and nullable rider pay in one transaction. Reuse RiderEarnings queries for web/API totals. Null snapshots and deliveries without rates must never be backfilled; zero pay is a valid recorded amount. The live-only legacy orders.rider_fee column is unused and is not evidence of a snapshot.

## Refund merchandise only after delivery
Delivered-order refunds repay merchandise after allocated vouchers and reverse its commission. Do not include outbound shipping in shipping_amount or refund_amount: hub shipping income and rider pay remain earned. The refund payable must agree with OrderLedger refunds_issued.

## Repair missing delivery proof without replaying delivery money
A delivery retry with no proof must store a validated photo from the original delivery rider, attributed to the frozen earning hub/courier. Keep existing proof records (including expired records) immutable. Photo repair must not replay lifecycle events, COD, ledger entries, or earnings snapshots; retain order locking and rollback file cleanup.
