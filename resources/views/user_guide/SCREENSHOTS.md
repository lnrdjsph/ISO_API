# User Guide — Suggested Screenshots

Drop images into `public/images/guide/`. Filenames below match the `<x-guide.screenshot src="...">`
references in the partials. Items marked **NEW** currently render as dashed "Suggested screenshot"
placeholders until the asset is added — once the file exists, swap `placeholder` off in the partial.

Capture at ~16:9, 1600px wide, light theme, with a representative (non-sensitive) demo order.

## High priority (placeholders live now)

| Filename | Section | What to capture |
|---|---|---|
| `dashboard-warehouse.png` | Dashboard (warehouse) | A warehouse user's dashboard — the 3 KPI cards (Total / Approved / Completed). **NEW** |
| `dashboard-attention-required.png` | Dashboard | The "Attention Required" panel with a few stuck orders. **NEW** |
| `dashboard-inventory-snapshot.png` | Dashboard (personnel) | The Inventory Snapshot card (In/Low/Out of stock + last WMS sync). **NEW** |
| `orders-table-warehouse.png` | Orders List (warehouse) | Order table filtered to approved/completed for a warehouse. **NEW** |
| `od-warehouse-items.png` | Order Details (warehouse) | Ordered Items table, read-only, showing per-item transfer status. **NEW** |
| `warehouse-fulfillment-flow.png` | Fulfilment Tracking | Items table mid-fulfilment (Picking / Shipped statuses visible). **NEW** |
| `warehouse-received-banner.png` | Fulfilment Tracking | The amber "Items received at the store" banner on an approved order. **NEW** |

## Medium priority (refresh existing shots — UI has changed)

| Filename | Section | Why refresh |
|---|---|---|
| `dashboard-personnel.png` | Dashboard | Current layout = KPI cards + Quick Actions + Inventory Snapshot + Recent Orders (old shot predates this). |
| `dashboard-manager.png` | Dashboard | Should show the approval-queue banner + KPI cards. |
| `dashboard-full.png` | Dashboard | Admin view incl. the secondary revenue metric strip. |
| `sof-order-items.png` | Sales Order Form | Confirm it shows Sale Type, product search, and the live Item Breakdown. |
| `sof-header.png` | Sales Order Form | Dispatch now has 3 modes (Customer Pick-up / Pick-up at Warehouse / Delivery Direct). |

## Nice to have (currently text-only)

| Filename | Section | What to capture |
|---|---|---|
| `dashboard-quick-actions.png` | Dashboard | The Quick Actions shortcut row (with the manager approval count badge). |
| `dashboard-store-performance.png` | Dashboard | The Store Performance ranking panel. |
| `od-order-notes.png` | Order Details | The Order Notes timeline (already in repo — wire it into the Notes subsection if desired). |
