---
tags: [frontend, vue]
---

# Frontend Components (`resources/js/components`)

One folder per feature, mirroring the API modules in [[API-Routes]]:

`auth`, `brand`, `care`, `care_data`, `care_warranty`, `category`, `craft`, `craft_inspection`, `customer`, `customer_progress`, `employee`, `expens`, `inv_care`, `inv_excl_serve`, `inventory_movement`, `master_sku`, `meeting`, `meeting_details`, `order`, `pos`, `product`, `product_warranty`, `salary`, `serve`, `serve_bek`, `serve_data`, `serve_mps`, `serve_pce`, `stock`, `sub_category`, `suppliers`.

`craft_inspection`, `inv_care`, `inv_excl_serve`, `master_sku` were added after the original 2026-07-02 snapshot of this vault — see [[Work-In-Progress]] and [[Domain-Models]] for their backing tables/migration history. `customer_progress` (2026-07-11) replaced the old `document` module outright, and `inventory_movement` (2026-07-11) turned the previously UI-less `inv_move` table into a real feature — see [[Domain-Models]] for what changed in both.

Shared code: `resources/js/Helpers` (likely Axios instance / formatting utilities — check before adding new HTTP calls to avoid duplicating the client setup).

Build: Laravel Mix (`webpack.mix.js`) compiles to `public/js/app.js`. That compiled file shows up as modified in `git status` — it's a build artifact; regenerate with `npm run dev`/`npm run production` rather than hand-editing, and don't be surprised if it diffs on every build.

## Related
- [[Architecture]]
- [[Project-Overview]]
