# Permission Flow Diagram

## Before Update

```
┌─────────────────────────────────────────────────────────────┐
│                    USER ROLES                                │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                 CURRENT PERMISSIONS                          │
├─────────────────────────────────────────────────────────────┤
│  ✓ save draft                                               │
│  ✓ cheque payment                                           │
│  ✓ create sale                                              │
│  ✓ view all sales                                           │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│              FEATURE ACCESS (Mixed)                          │
├─────────────────────────────────────────────────────────────┤
│  "save draft" → Controls BOTH:                              │
│    • Draft functionality                                     │
│    • Sale Order functionality ❌ (Not specific!)            │
│                                                              │
│  "view all sales" → Controls BOTH:                          │
│    • View sales list                                         │
│    • Cheque management ❌ (Not specific!)                   │
└─────────────────────────────────────────────────────────────┘

❌ PROBLEM: No granular control
```

## After Update

```
┌─────────────────────────────────────────────────────────────┐
│                    USER ROLES                                │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│            SMART ASSIGNMENT LOGIC                            │
│                                                              │
│  IF has "save draft"      → ADD "create sale-order"         │
│  IF has "save draft"      → ADD "view sale-order"           │
│  IF has "cheque payment"  → ADD "manage cheque"             │
│  IF has "cheque payment"  → ADD "view cheque"               │
│  IF has "cheque payment"  → ADD "view cheque-management"    │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│         UPDATED PERMISSIONS (Additive)                       │
├─────────────────────────────────────────────────────────────┤
│  Original (Kept):                                           │
│  ✓ save draft                                               │
│  ✓ cheque payment                                           │
│  ✓ create sale                                              │
│  ✓ view all sales                                           │
│                                                              │
│  New (Added):                                                │
│  ✨ create sale-order                                       │
│  ✨ view sale-order                                         │
│  ✨ manage cheque                                           │
│  ✨ view cheque                                             │
│  ✨ approve cheque                                          │
│  ✨ reject cheque                                           │
│  ✨ view cheque-management                                  │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│         FEATURE ACCESS (Specific & Granular)                 │
├─────────────────────────────────────────────────────────────┤
│  "save draft"           → Draft functionality only           │
│  "create sale-order"    → Sale Order creation ✅            │
│  "view sale-order"      → Sale Order sidebar menu ✅        │
│                                                              │
│  "cheque payment"       → Cheque payment in POS              │
│  "view cheque-management" → Cheque management page ✅       │
│  "manage cheque"        → Full cheque management ✅         │
└─────────────────────────────────────────────────────────────┘

✅ SOLUTION: Granular control with backward compatibility
```

## Update Flow

```
START
  │
  ▼
┌─────────────────────────────────┐
│  Run: php artisan               │
│  permissions:update             │
└─────────────────────────────────┘
  │
  ▼
┌─────────────────────────────────┐
│  1. Create Database Backup      │
│     (Optional but recommended)  │
└─────────────────────────────────┘
  │
  ▼
┌─────────────────────────────────┐
│  2. Create New Permissions      │
│     • create sale-order         │
│     • view sale-order           │
│     • manage cheque             │
│     • view cheque               │
│     • approve cheque            │
│     • reject cheque             │
│     • view cheque-management    │
└─────────────────────────────────┘
  │
  ▼
┌─────────────────────────────────┐
│  3. Scan All Existing Roles     │
│     (Skip Master/Super Admin)   │
└─────────────────────────────────┘
  │
  ▼
┌─────────────────────────────────┐
│  4. For Each Role:              │
│     Check related permissions   │
│     ├─ Has "save draft"?        │
│     │  └─ Yes → Add sale-order  │
│     │          permissions       │
│     └─ Has "cheque payment"?    │
│        └─ Yes → Add cheque      │
│                 permissions      │
└─────────────────────────────────┘
  │
  ▼
┌─────────────────────────────────┐
│  5. Clear All Caches            │
│     • cache:clear               │
│     • config:clear              │
│     • permission:cache-reset    │
└─────────────────────────────────┘
  │
  ▼
┌─────────────────────────────────┐
│  6. Show Success Report         │
│     ✓ Permissions created       │
│     ✓ Roles updated             │
│     ✓ Caches cleared            │
└─────────────────────────────────┘
  │
  ▼
END (Success!)
```

## Verification Flow

```
START
  │
  ▼
┌─────────────────────────────────┐
│  Run: php artisan               │
│  permissions:verify             │
└─────────────────────────────────┘
  │
  ▼
┌─────────────────────────────────┐
│  Check New Permissions Exist    │
│  ├─ create sale-order ✅        │
│  ├─ view sale-order ✅          │
│  ├─ manage cheque ✅            │
│  ├─ view cheque ✅              │
│  ├─ approve cheque ✅           │
│  ├─ reject cheque ✅            │
│  └─ view cheque-management ✅   │
└─────────────────────────────────┘
  │
  ▼
┌─────────────────────────────────┐
│  Generate Role Report           │
│  ┌────────────────────────────┐ │
│  │ Role    │ Sale │ Cheque    │ │
│  ├────────────────────────────┤ │
│  │ Admin   │ 2/2  │ 5/5      │ │
│  │ Manager │ 2/2  │ 3/5      │ │
│  │ Cashier │ 0/2  │ 2/5      │ │
│  └────────────────────────────┘ │
└─────────────────────────────────┘
  │
  ▼
┌─────────────────────────────────┐
│  Show Detailed Breakdown        │
│  • Which roles have which perms │
│  • Permission coverage          │
│  • Summary statistics           │
└─────────────────────────────────┘
  │
  ▼
END (Verification Complete!)
```

## Permission Hierarchy

```
┌─────────────────────────────────────────────────────────────┐
│                    MASTER SUPER ADMIN                        │
│                    (All Permissions)                         │
└─────────────────────────────────────────────────────────────┘
                            │
        ┌───────────────────┴───────────────────┐
        ▼                                       ▼
┌──────────────────┐                  ┌──────────────────┐
│   SUPER ADMIN    │                  │  CUSTOM ROLES    │
│ (All except      │                  │  (Selective)     │
│  Master perms)   │                  │                  │
└──────────────────┘                  └──────────────────┘
        │                                       │
        │                                       │
        └───────────────────┬───────────────────┘
                            ▼
        ┌───────────────────────────────────────┐
        │         PERMISSION GROUPS             │
        ├───────────────────────────────────────┤
        │  17. POS Management                   │
        │  ├─ access pos                        │
        │  ├─ create quotation                  │
        │  ├─ save draft                        │
        │  ├─ create sale-order ✨ NEW         │
        │  ├─ view sale-order ✨ NEW           │
        │  ├─ suspend sale                      │
        │  ├─ credit sale                       │
        │  ├─ card payment                      │
        │  ├─ cheque payment                    │
        │  └─ multiple payment methods          │
        │                                       │
        │  20. Payment Management               │
        │  ├─ view payments                     │
        │  ├─ create payment                    │
        │  ├─ edit payment                      │
        │  ├─ delete payment                    │
        │  ├─ manage cheque ✨ NEW             │
        │  ├─ view cheque ✨ NEW               │
        │  ├─ approve cheque ✨ NEW            │
        │  ├─ reject cheque ✨ NEW             │
        │  └─ view cheque-management ✨ NEW    │
        └───────────────────────────────────────┘
```

## User Experience Flow

```
USER LOGS IN
     │
     ▼
┌─────────────────┐
│  Check Role     │
│  & Permissions  │
└─────────────────┘
     │
     ├─────────────────────┬──────────────────┐
     ▼                     ▼                  ▼
┌──────────┐      ┌──────────────┐    ┌──────────────┐
│ Sidebar  │      │  POS Page    │    │  Features    │
└──────────┘      └──────────────┘    └──────────────┘
     │                     │                  │
     │                     │                  │
     ▼                     ▼                  ▼
Has "view         Has "create        Has related
sale-order"?      sale-order"?       permissions?
     │                     │                  │
     ├─ Yes               ├─ Yes             ├─ Yes
     │  Show Menu         │  Show Button     │  Access Feature
     │                    │                  │
     └─ No                └─ No              └─ No
        Hide Menu            Hide Button         Deny Access
```

## Legend

```
✅ = Specific, granular permission
❌ = Mixed, non-specific permission
✨ = New permission added
✓  = Existing permission kept
```
