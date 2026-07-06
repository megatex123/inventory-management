---
tags: [frontend, vue]
---

# Frontend Components (`resources/js/components`)

One folder per feature, mirroring the API modules in [[API-Routes]]:

`auth`, `brand`, `care`, `care_data`, `care_warranty`, `category`, `craft`, `customer`, `employee`, `expens`, `meeting`, `meeting_details`, `order`, `pos`, `product`, `product_warranty`, `salary`, `serve`, `serve_bek`, `serve_data`, `serve_mps`, `serve_pce`, `stock`, `sub_category`, `suppliers`.

Shared code: `resources/js/Helpers` (likely Axios instance / formatting utilities — check before adding new HTTP calls to avoid duplicating the client setup).

Build: Laravel Mix (`webpack.mix.js`) compiles to `public/js/app.js`. That compiled file shows up as modified in `git status` — it's a build artifact; regenerate with `npm run dev`/`npm run production` rather than hand-editing, and don't be surprised if it diffs on every build.

## Related
- [[Architecture]]
- [[Project-Overview]]
