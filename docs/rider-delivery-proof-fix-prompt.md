# Rider team prompt: delivery photo upload

Fix the rider app's proof-of-delivery submission so the actual photo reaches the logistics backend and can be viewed by the owning hub. Trace the current camera, local file storage, API client, offline queue, and delivery success handling before editing. Confirm installed package versions and follow the rider repository's conventions.

## Backend contract

- Send `POST /api/courier/deliveries/{order_number}/delivered` to the configured API base URL. Use the public `order_number` (also returned as `order_id`), never a numeric database ID or waybill code.
- Authenticate with `Authorization: Bearer <token>` and request `Accept: application/json`.
- Send `multipart/form-data` with the actual photo bytes as the file part named **`proof_image`**, including a filename and matching MIME type. A local URI, base64 string, JSON object, or field called `photo`/`image` does not satisfy this contract. Let the HTTP library generate the multipart boundary.
- For a new delivery, include `amount`: a nonnegative numeric value representing the cash actually collected.
- Photos must be JPEG, PNG, or WebP, no larger than 5,120 KB, at least 640 pixels in both dimensions, and at most 40 megapixels. Convert HEIC/HEIF to JPEG before sending. Compress while preserving legibility and minimum dimensions; a target below 2 MB improves compatibility with current PHP limits.
- Optional fields: `captured_at` (ISO 8601 with timezone, for example `2026-11-15T16:30:00+08:00`), `latitude` (-90 to 90), `longitude` (-180 to 180), and `notes` (maximum 255 characters). Preserve the original capture timestamp during retries.

## Required app behavior

1. Persist the captured file in durable app storage while submission is pending. Verify that its bytes remain readable after app restart; do not queue only a temporary camera URI.
2. Submit the amount, photo, and metadata together. Show delivery success only after a successful HTTP response with `proof.uploaded === true`. A local preview or queued upload is not proof of server storage.
3. On timeout, lost connectivity, or HTTP 503, retain the file and retry the same order with the same photo and metadata. Use bounded backoff and prevent concurrent duplicate taps. Retry authentication only through the normal authentication flow.
4. Surface HTTP 422 validation messages, especially `errors.proof_image`. Handle HTTP 413 by reducing photo size. Show actionable messages for 403 (authorization) and 409 (wrong delivery stage); do not repeatedly retry these unchanged requests.
5. After the backend fix is deployed, the original delivery rider can repair a DELIVERED or COMPLETED order that has no proof by posting the photo and optional metadata to the same endpoint; `amount` is optional for this repair. The backend preserves existing COD, earnings, and delivery timestamps. Existing proof records, including expired records, are preserved on retries.
6. Refresh history/earnings after success and use the returned `proof.uploaded` value. Never manufacture an uploaded state or a public image URL. Logistics streams the private image through its authenticated, hub-scoped viewer.

Example multipart parts:

```text
amount: 140.00
proof_image: <binary JPEG file; filename=delivery-proof.jpg; type=image/jpeg>
captured_at: 2026-11-15T16:30:00+08:00
latitude: 10.3157
longitude: 123.8854
notes: Received by buyer.
```

The success response contains `message`, nullable `earning`, and `proof: { uploaded: true, captured_at: <ISO timestamp or null> }`.

## Acceptance checks

- Complete a new delivery from a real device, reload logistics, and open its proof in Deliveries and the delivery report. Confirm the image bytes are served successfully.
- Retry after a lost response and double tap: exactly one proof remains and COD/earnings are unchanged.
- Repair a missing proof on a delivered/completed order as its original rider. Verify another rider cannot upload it and another hub cannot view it.
- Test a missing file, local URI sent as text, unsupported format, undersized image, oversized upload, offline restart, and storage failure. None may produce a false uploaded state.
- Provide the changed files, tests, and a sanitized request/response trace showing method, order number, multipart field names, file MIME/size, HTTP status, and `proof.uploaded`. Exclude tokens, photo contents, and customer details.

## Backend/deployment coordination

The web backend patch must be deployed before relying on missing-proof repair. The backend stores a processed JPEG and thumbnail in private R2 storage and the record in `delivery_proofs`.

The inspected local PHP configuration has `upload_max_filesize=2M` and `post_max_size=8M`, while application validation permits 5 MB. Operations must verify the actual web PHP/FPM and proxy limits: permit at least a 5 MB individual file and more than the API's 6 MB total-request ceiling (for example, `upload_max_filesize=5M`, `post_max_size=8M`, and a proxy body limit of at least 8 MB). Verify GD, the delivery-proofs migration, and private R2 write/read access in the deployed environment. CLI configuration does not establish production limits.

Report the confirmed rider-side cause and actual device-to-logistics result; do not assume the local backend findings prove what the production rider app sends.
