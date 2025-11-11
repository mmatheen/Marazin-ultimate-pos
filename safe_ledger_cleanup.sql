-- SAFE LEDGER CLEANUP SQL
-- Generated on: 2025-11-11 11:29:32
-- Purpose: Fix orphaned ledger entries from deleted sales

-- Step 1: Backup current state
CREATE TABLE ledgers_backup_20251111_112932 AS SELECT * FROM ledgers;

-- Fixing orphaned entries for: ATF-017
-- Reversal for Customer 3 (Customer 3)
INSERT INTO ledgers (transaction_date, reference_no, transaction_type, debit, credit, balance, contact_type, user_id, notes, created_at, updated_at) VALUES (
  NOW(),
  'CLEANUP-REV-ATF-017-366',
  'adjustment_credit',
  0.00,
  1125.00,
  0,
  'customer',
  3,
  'CLEANUP: Reversal of orphaned entry for deleted sale ATF-017',
  NOW(),
  NOW()
);

-- Reversal for Customer 916 (Customer 916)
INSERT INTO ledgers (transaction_date, reference_no, transaction_type, debit, credit, balance, contact_type, user_id, notes, created_at, updated_at) VALUES (
  NOW(),
  'CLEANUP-REV-ATF-017-433',
  'adjustment_credit',
  0.00,
  1125.00,
  0,
  'customer',
  916,
  'CLEANUP: Reversal of orphaned entry for deleted sale ATF-017',
  NOW(),
  NOW()
);

-- Fixing orphaned entries for: ATF-020
-- Reversal for Customer 3 (Customer 3)
INSERT INTO ledgers (transaction_date, reference_no, transaction_type, debit, credit, balance, contact_type, user_id, notes, created_at, updated_at) VALUES (
  NOW(),
  'CLEANUP-REV-ATF-020-367',
  'adjustment_credit',
  0.00,
  800.00,
  0,
  'customer',
  3,
  'CLEANUP: Reversal of orphaned entry for deleted sale ATF-020',
  NOW(),
  NOW()
);

-- Reversal for Customer 921 (Customer 921)
INSERT INTO ledgers (transaction_date, reference_no, transaction_type, debit, credit, balance, contact_type, user_id, notes, created_at, updated_at) VALUES (
  NOW(),
  'CLEANUP-REV-ATF-020-432',
  'adjustment_credit',
  0.00,
  800.00,
  0,
  'customer',
  921,
  'CLEANUP: Reversal of orphaned entry for deleted sale ATF-020',
  NOW(),
  NOW()
);

-- Fixing orphaned entries for: ATF-027
-- Reversal for Customer 3 (Customer 3)
INSERT INTO ledgers (transaction_date, reference_no, transaction_type, debit, credit, balance, contact_type, user_id, notes, created_at, updated_at) VALUES (
  NOW(),
  'CLEANUP-REV-ATF-027-370',
  'adjustment_credit',
  0.00,
  8010.00,
  0,
  'customer',
  3,
  'CLEANUP: Reversal of orphaned entry for deleted sale ATF-027',
  NOW(),
  NOW()
);

-- Reversal for Customer 146 (Customer 146)
INSERT INTO ledgers (transaction_date, reference_no, transaction_type, debit, credit, balance, contact_type, user_id, notes, created_at, updated_at) VALUES (
  NOW(),
  'CLEANUP-REV-ATF-027-431',
  'adjustment_credit',
  0.00,
  7380.00,
  0,
  'customer',
  146,
  'CLEANUP: Reversal of orphaned entry for deleted sale ATF-027',
  NOW(),
  NOW()
);

-- Fixing orphaned entries for: MLX-050
-- Reversal for Customer 871 (Customer 871)
INSERT INTO ledgers (transaction_date, reference_no, transaction_type, debit, credit, balance, contact_type, user_id, notes, created_at, updated_at) VALUES (
  NOW(),
  'CLEANUP-REV-MLX-050-329',
  'adjustment_credit',
  0.00,
  125000.00,
  0,
  'customer',
  871,
  'CLEANUP: Reversal of orphaned entry for deleted sale MLX-050',
  NOW(),
  NOW()
);

-- Reversal for Customer 935 (Customer 935)
INSERT INTO ledgers (transaction_date, reference_no, transaction_type, debit, credit, balance, contact_type, user_id, notes, created_at, updated_at) VALUES (
  NOW(),
  'CLEANUP-REV-MLX-050-383',
  'adjustment_credit',
  0.00,
  125000.00,
  0,
  'customer',
  935,
  'CLEANUP: Reversal of orphaned entry for deleted sale MLX-050',
  NOW(),
  NOW()
);

-- Step 2: Recalculate balances for affected customers
-- You'll need to run: Ledger::recalculateAllBalances(3, 'customer');
-- You'll need to run: Ledger::recalculateAllBalances(916, 'customer');
-- You'll need to run: Ledger::recalculateAllBalances(921, 'customer');
-- You'll need to run: Ledger::recalculateAllBalances(146, 'customer');
-- You'll need to run: Ledger::recalculateAllBalances(871, 'customer');
-- You'll need to run: Ledger::recalculateAllBalances(935, 'customer');

-- IMPACT SUMMARY:
-- Total correction amount: Rs 269,240.00
-- Affected customers: 6

-- Customer 3 (Customer 3):
-- Current (incorrect) balance: Rs 9,935.00
-- Corrected balance will be: Rs 0.00

-- Customer 916 (Customer 916):
-- Current (incorrect) balance: Rs 3,262.50
-- Corrected balance will be: Rs 2,137.50

-- Customer 921 (Customer 921):
-- Current (incorrect) balance: Rs 1,880.00
-- Corrected balance will be: Rs 1,080.00

-- Customer 146 (Customer 146):
-- Current (incorrect) balance: Rs 44,807.50
-- Corrected balance will be: Rs 37,427.50

-- Customer 871 (Customer 871):
-- Current (incorrect) balance: Rs 9,142,975.80
-- Corrected balance will be: Rs 9,017,975.80

-- Customer 935 (Customer 935):
-- Current (incorrect) balance: Rs 3,924,710.00
-- Corrected balance will be: Rs 3,799,710.00

