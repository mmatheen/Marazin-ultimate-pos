# 📊 SALE ORDER WORKFLOW DIAGRAM

## Complete Visual Guide

```
════════════════════════════════════════════════════════════════════════════════
                            SALE ORDER LIFECYCLE
════════════════════════════════════════════════════════════════════════════════

┌──────────────────────────────────────────────────────────────────────────────┐
│                         STEP 1: ORDER CREATION                                │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                               │
│  👤 Sales Rep                 📱 Mobile/Web                                   │
│      │                              │                                         │
│      └─── Visits Customer ──────────┤                                         │
│                                     │                                         │
│                              Takes Order Request                              │
│                                     │                                         │
│                                     ▼                                         │
│                         ┌───────────────────────┐                            │
│                         │   CREATE SALE ORDER   │                            │
│                         └───────────┬───────────┘                            │
│                                     │                                         │
│                                     ▼                                         │
│                         Database Record Created:                              │
│                         ┌───────────────────────┐                            │
│                         │ transaction_type: SO  │                            │
│                         │ order_number: SO-001  │                            │
│                         │ sales_rep_id: 5       │                            │
│                         │ order_status: draft   │                            │
│                         │ payment_status: Due   │                            │
│                         └───────────────────────┘                            │
│                                     │                                         │
│                                     ▼                                         │
│                         ┌───────────────────────┐                            │
│                         │  ADD PRODUCTS/ITEMS   │                            │
│                         └───────────┬───────────┘                            │
│                                     │                                         │
│                         sales_products table:                                 │
│                         - Product A × 10 pcs                                  │
│                         - Product B × 5 pcs                                   │
│                                     │                                         │
│                                     ▼                                         │
│                         ┌───────────────────────┐                            │
│                         │ SUBMIT FOR APPROVAL   │                            │
│                         └───────────┬───────────┘                            │
│                                     │                                         │
│                         order_status: pending                                 │
│                                                                               │
└──────────────────────────────────────────────────────────────────────────────┘


┌──────────────────────────────────────────────────────────────────────────────┐
│                         STEP 2: APPROVAL PROCESS                              │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                               │
│  👔 Manager Dashboard                                                         │
│      │                                                                        │
│      ├─── View Pending Orders ────┐                                          │
│      │                             │                                          │
│      │                     ┌───────▼───────┐                                 │
│      │                     │ PENDING LIST  │                                 │
│      │                     │ - SO-001: ₹5K │                                 │
│      │                     │ - SO-002: ₹3K │                                 │
│      │                     └───────┬───────┘                                 │
│      │                             │                                          │
│      ├─── Review Details ──────────┤                                          │
│      │                             │                                          │
│      │                     ┌───────▼───────┐                                 │
│      │                     │ CHECK ITEMS   │                                 │
│      │                     │ CHECK STOCK   │                                 │
│      │                     │ CHECK CUSTOMER│                                 │
│      │                     └───────┬───────┘                                 │
│      │                             │                                          │
│      └─── Decision ─────────────┬──┴──┬─────────                             │
│                                 │     │                                       │
│                         ✅ APPROVE  ❌ REJECT                                 │
│                                 │     │                                       │
│                                 │     └─── order_status: cancelled            │
│                                 │                                             │
│                                 ▼                                             │
│                     order_status: confirmed                                   │
│                                 │                                             │
│                                 │                                             │
│                     Email/Notification Sent                                   │
│                     "Order SO-001 Approved"                                   │
│                                                                               │
└──────────────────────────────────────────────────────────────────────────────┘


┌──────────────────────────────────────────────────────────────────────────────┐
│                         STEP 3: WAREHOUSE PROCESSING                          │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                               │
│  📦 Warehouse Team                                                            │
│      │                                                                        │
│      ├─── View Confirmed Orders ──┐                                          │
│      │                             │                                          │
│      │                     ┌───────▼────────┐                                │
│      │                     │ CONFIRMED LIST │                                │
│      │                     │ - SO-001       │                                │
│      │                     └───────┬────────┘                                │
│      │                             │                                          │
│      ├─── Pick Items ──────────────┤                                          │
│      │                             │                                          │
│      │                     order_status: processing                           │
│      │                             │                                          │
│      │                     ┌───────▼────────┐                                │
│      │                     │  PICK PRODUCTS │                                │
│      │                     │  □ Product A   │                                │
│      │                     │  □ Product B   │                                │
│      │                     └───────┬────────┘                                │
│      │                             │                                          │
│      ├─── Pack & Quality Check ────┤                                          │
│      │                             │                                          │
│      │                     ┌───────▼────────┐                                │
│      │                     │  QC CHECK      │                                │
│      │                     │  ✓ Quantities  │                                │
│      │                     │  ✓ Quality     │                                │
│      │                     │  ✓ Packaging   │                                │
│      │                     └───────┬────────┘                                │
│      │                             │                                          │
│      └─── Mark Ready ──────────────┤                                          │
│                                    │                                          │
│                        order_status: ready                                    │
│                                    │                                          │
│                        Notification: "SO-001 Ready"                           │
│                                                                               │
└──────────────────────────────────────────────────────────────────────────────┘


┌──────────────────────────────────────────────────────────────────────────────┐
│                      STEP 4: INVOICE CONVERSION 🎯                            │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                               │
│  🧾 Billing/Manager                                                           │
│      │                                                                        │
│      └─── Click "Convert to Invoice" ─────┐                                  │
│                                            │                                  │
│                                            ▼                                  │
│                              ┌─────────────────────┐                         │
│                              │ $saleOrder->        │                         │
│                              │ convertToInvoice()  │                         │
│                              └─────────┬───────────┘                         │
│                                        │                                      │
│                            ┌───────────▼───────────┐                         │
│                            │  TRANSACTION START    │                         │
│                            └───────────┬───────────┘                         │
│                                        │                                      │
│         ┌──────────────────────────────┼──────────────────────────────┐     │
│         │                              │                              │     │
│         ▼                              ▼                              ▼     │
│  ┌─────────────┐              ┌────────────────┐           ┌──────────────┐│
│  │CREATE NEW   │              │ COPY ITEMS     │           │UPDATE STOCK  ││
│  │INVOICE      │              │ from SO → INV  │           │              ││
│  │RECORD       │              │                │           │Product A: -10││
│  │             │              │sales_products  │           │Product B: -5 ││
│  │ID: 999      │              │sale_id: 999    │           │              ││
│  │type: invoice│              │                │           │location_     ││
│  │invoice_no:  │              │                │           │batches       ││
│  │INV-042      │              │                │           │updated       ││
│  └──────┬──────┘              └────────┬───────┘           └──────┬───────┘│
│         │                              │                          │        │
│         └──────────────────┬───────────┴──────────────────────────┘        │
│                            │                                                │
│                            ▼                                                │
│                  ┌──────────────────┐                                       │
│                  │ UPDATE ORIGINAL  │                                       │
│                  │ SALE ORDER:      │                                       │
│                  │                  │                                       │
│                  │ order_status:    │                                       │
│                  │   completed ✅   │                                       │
│                  │                  │                                       │
│                  │ converted_to_    │                                       │
│                  │ sale_id: 999     │                                       │
│                  └────────┬─────────┘                                       │
│                           │                                                 │
│                           ▼                                                 │
│                  ┌──────────────────┐                                       │
│                  │ COMMIT           │                                       │
│                  │ TRANSACTION ✅   │                                       │
│                  └────────┬─────────┘                                       │
│                           │                                                 │
│                           ▼                                                 │
│                    ┌──────────────┐                                         │
│                    │ NOTIFICATION │                                         │
│                    │ "Invoice     │                                         │
│                    │  Created"    │                                         │
│                    └──────────────┘                                         │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘


┌──────────────────────────────────────────────────────────────────────────────┐
│                        STEP 5: PAYMENT COLLECTION                             │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                               │
│  💰 Sales Rep / Cashier                                                       │
│      │                                                                        │
│      ├─── View Invoice ────────────┐                                         │
│      │                              │                                         │
│      │                    ┌─────────▼─────────┐                              │
│      │                    │ INVOICE INV-042   │                              │
│      │                    │ Total: ₹5,000     │                              │
│      │                    │ Status: Due       │                              │
│      │                    └─────────┬─────────┘                              │
│      │                              │                                         │
│      └─── Collect Payment ──────────┤                                         │
│                                     │                                         │
│                         ┌───────────▼───────────┐                            │
│                         │  PAYMENT OPTIONS      │                            │
│                         │  • Cash               │                            │
│                         │  • Card               │                            │
│                         │  • Cheque             │                            │
│                         │  • Bank Transfer      │                            │
│                         └───────────┬───────────┘                            │
│                                     │                                         │
│                                     ▼                                         │
│                         ┌───────────────────────┐                            │
│                         │ CREATE PAYMENT RECORD │                            │
│                         │ reference_id: 999     │                            │
│                         │ amount: 5000          │                            │
│                         └───────────┬───────────┘                            │
│                                     │                                         │
│                                     ▼                                         │
│                         ┌───────────────────────┐                            │
│                         │ UPDATE INVOICE        │                            │
│                         │ total_paid: 5000      │                            │
│                         │ total_due: 0          │                            │
│                         │ payment_status: Paid✅│                            │
│                         └───────────┬───────────┘                            │
│                                     │                                         │
│                                     ▼                                         │
│                         ┌───────────────────────┐                            │
│                         │ PRINT RECEIPT         │                            │
│                         │ "Thank You!"          │                            │
│                         └───────────────────────┘                            │
│                                                                               │
└──────────────────────────────────────────────────────────────────────────────┘


════════════════════════════════════════════════════════════════════════════════
                        DATA FLOW THROUGH TABLES
════════════════════════════════════════════════════════════════════════════════

SALE ORDER STAGE:
┌──────────────────────────────────────────────────────────────────────────────┐
│ SALES TABLE                                                                   │
│ ┌──────────────────────────────────────────────────────────────────────────┐ │
│ │ ID: 1                              │ ID: 999                             │ │
│ │ transaction_type: sale_order       │ transaction_type: invoice           │ │
│ │ order_number: SO-2025-0001         │ invoice_no: INV-2025-0042           │ │
│ │ order_status: pending              │ sales_date: 2025-10-23              │ │
│ │ order_date: 2025-10-22             │ payment_status: Due                 │ │
│ │ sales_rep_id: 5                    │ sales_rep_id: 5 (same!)             │ │
│ │ customer_id: 42                    │ customer_id: 42 (same!)             │ │
│ │ final_total: 5000                  │ final_total: 5000 (same!)           │ │
│ │ invoice_no: NULL                   │ order_number: NULL                  │ │
│ │ converted_to_sale_id: NULL  ───────┼─→ converted_to_sale_id: 999         │ │
│ │                                    │                                     │ │
│ │ After conversion:                  │                                     │ │
│ │ order_status: completed ✅         │                                     │ │
│ │ converted_to_sale_id: 999 ──────────→                                    │ │
│ └──────────────────────────────────────────────────────────────────────────┘ │
│                                                                               │
│ SALES_PRODUCTS TABLE                                                          │
│ ┌────────────────────────────────┐  ┌────────────────────────────────┐      │
│ │ sale_id: 1 (Sale Order)        │  │ sale_id: 999 (Invoice)         │      │
│ │ product_id: 10                 │  │ product_id: 10                 │      │
│ │ quantity: 10                   │  │ quantity: 10 (copied!)         │      │
│ │ price: 400                     │  │ price: 400 (copied!)           │      │
│ └────────────────────────────────┘  └────────────────────────────────┘      │
│                                                                               │
│ STOCK UPDATES (on conversion)                                                │
│ ┌──────────────────────────────────────────────────────────────────────────┐ │
│ │ LOCATION_BATCHES                 │ PRODUCTS                              │ │
│ │ batch_id: 123, qty: 50 → 40 ✅   │ product_id: 10, stock: 100 → 90 ✅   │ │
│ └──────────────────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────────────────┘


════════════════════════════════════════════════════════════════════════════════
                            STATUS TIMELINE
════════════════════════════════════════════════════════════════════════════════

Day 1: 10:00 AM
├─ draft          [Sales Rep creating order]
│
Day 1: 10:15 AM
├─ pending        [Submitted for approval]
│
Day 1: 02:00 PM
├─ confirmed      [Manager approved]
│
Day 1: 03:00 PM
├─ processing     [Warehouse started picking]
│
Day 1: 04:30 PM
├─ ready          [Items packed and ready]
│
Day 2: 09:00 AM
├─ delivered      [Delivered to customer]
│
Day 2: 09:15 AM
├─ [CONVERTED]    [Invoice created - stock reduced]
│
Day 2: 09:20 AM
└─ completed ✅   [Original SO marked complete]

    New Invoice: payment_status = 'Due'
    ↓
    Payment collected
    ↓
    payment_status = 'Paid' ✅


════════════════════════════════════════════════════════════════════════════════
                        ALTERNATIVE PATHS
════════════════════════════════════════════════════════════════════════════════

Path A: CANCELLATION
pending → confirmed → cancelled ❌
(Stock never reduced, order not converted)

Path B: DIRECT CONVERSION
pending → confirmed → [CONVERTED] → completed ✅
(Skip processing/ready steps if not needed)

Path C: PARTIAL PAYMENT
Invoice created → Partial payment → payment_status: 'Partial'
→ Second payment → payment_status: 'Paid' ✅


════════════════════════════════════════════════════════════════════════════════
                        KEY DECISION POINTS
════════════════════════════════════════════════════════════════════════════════

┌─────────────────────────────────────────────────────────────────────────────┐
│ APPROVE ORDER?                                                               │
│                                                                              │
│ ✅ YES → order_status = 'confirmed'                                         │
│          Continue to warehouse                                               │
│                                                                              │
│ ❌ NO  → order_status = 'cancelled'                                         │
│          Notify sales rep                                                    │
│          End process                                                         │
└─────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│ CONVERT TO INVOICE?                                                          │
│                                                                              │
│ Prerequisites:                                                               │
│ ✓ Order status = 'ready' or 'delivered'                                     │
│ ✓ All items available                                                       │
│ ✓ Customer confirmed                                                         │
│                                                                              │
│ Action: Click "Convert to Invoice" button                                   │
│                                                                              │
│ Result:                                                                      │
│ ✓ New invoice created                                                       │
│ ✓ Stock reduced                                                             │
│ ✓ Original SO marked completed                                              │
│ ✓ Ready for payment                                                         │
└─────────────────────────────────────────────────────────────────────────────┘


════════════════════════════════════════════════════════════════════════════════
                        REPORTING QUERIES
════════════════════════════════════════════════════════════════════════════════

Sales Rep Performance:
┌──────────────────────────────────────────────────────────────────────────────┐
│ SELECT                                                                        │
│   sales_rep_id,                                                              │
│   COUNT(*) as total_orders,                                                  │
│   SUM(final_total) as order_value,                                           │
│   COUNT(CASE WHEN order_status = 'completed' THEN 1 END) as completed       │
│ FROM sales                                                                    │
│ WHERE transaction_type = 'sale_order'                                        │
│ GROUP BY sales_rep_id                                                        │
└──────────────────────────────────────────────────────────────────────────────┘

Pending Orders Dashboard:
┌──────────────────────────────────────────────────────────────────────────────┐
│ SELECT order_status, COUNT(*) as count, SUM(final_total) as value           │
│ FROM sales                                                                    │
│ WHERE transaction_type = 'sale_order'                                        │
│   AND order_status IN ('pending', 'confirmed', 'processing', 'ready')       │
│ GROUP BY order_status                                                        │
└──────────────────────────────────────────────────────────────────────────────┘


════════════════════════════════════════════════════════════════════════════════

Created: October 22, 2025
System: Marazin Ultimate POS
Version: 1.0
