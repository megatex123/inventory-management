---
tags: [module, thread, bom, cables]
---

# QuiviThread

Custom PSU-sleeved-cable configurator — the only one of the three new 2026-07-15 modules (alongside [[QuiviMerch]], [[QuiviPlus]]) with a genuine BOM-resolution engine rather than flat CRUD. Built from `BOM_QVTD_Asus.csv` / `BOM_QVTD_Corsair.csv` / `BOM_QVTD_SeaSonic.csv` and `Inv_QVTD_I_QVTD.csv`.

## Models

- **`ThreadBomHeader`** — one row per `(psu_brand, cable_type)`: `psu_brand` is a plain string (Asus/Corsair/SeaSonic), deliberately **not** a FK to the `brand` table — that table is scoped to PC-component brands, a different concern from PSU-cable-compatibility brands. `cable_type` is one of `24pin`/`8eps`/`8pcie`/`12v2x6pcie`.
- **`ThreadBomLine`** — the components under a header: `sku_code` (→ `MasterSku`), `qty_per_cable`, `unit_cost`.
- **`InvThread`** — connector/sleeve/terminal stock pool, same shape as `InvMerch`/`InvCare` (no exclusive split — the source CSVs only have one `I_QVTD` pool).
- **`ThreadOrder`** + **`ThreadOrderItem`** — the customer order: `status` follows the Overview spec's workflow (`pending → cutting → sleeving → qc → complete`), `warranty_ends_at` is set to `completed_at + 90 days` when status hits `complete` (matches the spec's 90-day QuiviThread warranty). Each item snapshots `resolved_components` as JSON at order time — same pattern as `CraftInspectionItem.fields` — so a later edit to `ThreadBomLine` doesn't retroactively change historical orders' cost basis.

## BOM resolution

`GET /api/thread-bom/resolve?psu_brand=&cable_type=&colour_variant=` — the one genuinely new kind of endpoint in this batch of modules, with no existing precedent in the codebase to copy. Looks up the matching header(s) + lines and returns the component list plus total cost. The order-creation frontend calls this live as staff pick brand/cable-type per line, to preview cost before confirming; `ThreadOrderController::store()`/`update()` call the same resolution logic server-side (via a private `resolveComponents()` helper) to build the JSON snapshot, so the preview and the persisted snapshot can never drift from each other.

**Verified against source data**: resolving Asus 24-pin returns `total_cost: 58.1`, matching `BOM_QVTD_Asus.csv`'s own cost-summary matrix exactly.

## Known simplifications (v1)

- **Colour variants aren't modeled as separate BOMs yet.** The source CSVs mark each component row with a `Default` True/False flag picking between alternatives (e.g. MOLEX Blue vs MDPC-X Black connector) — v1 only seeds the `Default=True` line per component, so every header resolves to one clean, unambiguous parts list. A real sleeve/connector colour picker (the CSVs' "Default"/"Upgrade Premium" cost tracks) would need multiple headers per `(brand, cable_type)` keyed by `colour_variant`, which the schema already supports — just not populated yet.
- **`qty_per_cable` is the CSV's raw per-cable quantity**, not multiplied by "Total Cables Per Package" (the source data bundles some cable types for sale, e.g. "2 x 8 EPS" — an EPS order is typically 2 physical cables). This multiplier isn't modeled; ordering 2 EPS cables today means creating a line with `qty: 2`, not a single "EPS bundle" line.
- **Asus and SeaSonic's source CSVs are byte-identical** (only the Brand column differs) — confirmed by comparing the raw files, not assumed. Corsair genuinely differs: it needs 2x the EPS-connector quantity per cable and its 24-pin BOM has no heatshrink line (its source row is marked `Default=False` there, unlike Asus/SeaSonic).
- **No auto stock deduction.** `inv_thread.current_stock` isn't automatically drawn down when a `ThreadOrder` completes — consistent with how every other inventory table in this app (`InvCare`, `InvExclServe`, `InvMerch`) is only ever adjusted via its own CRUD `update()`, never auto-drawn-down by another module.

## Related
- [[Workflow]]
- [[Inventory-Movement]] — the `MasterSku` catalog every BOM line references
- [[Domain-Models]]
- [[API-Routes]]
