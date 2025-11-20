<?php
/**
 * ===================================================================
 * 🚀 DIRECT DUPLICATE FIXER - Quick Solution
 * ===================================================================
 * 
 * Direct fix for your specific duplicate issues
 * Based on your actual data analysis
 * 
 * USAGE: php direct_fix.php
 * 
 * ===================================================================
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🚀 DIRECT DUPLICATE FIXER\n";
echo "========================\n\n";

// Ask for confirmation
echo "⚠️  WARNING: This will fix duplicate ledger entries by marking them as 'reversed'!\n";
echo "This is based on your actual duplicate analysis.\n\n";

echo "Duplicates to be fixed:\n";
echo "- CSX-190 (Customer 7): 9 entries → Keep latest, mark 8 as reversed\n";
echo "- PUR007 (Contact 4): 3 payment entries → Keep latest, mark 2 as reversed\n";
echo "- CSX-109 (Customer 2): 2 entries → Keep latest, mark 1 as reversed\n";
echo "- CSX-295 (Customer 43): 2 entries → Keep latest, mark 1 as reversed\n";
echo "- And other duplicates...\n\n";

echo "Do you want to proceed? (yes/no): ";
$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if ($confirmation !== 'yes') {
    echo "❌ Operation cancelled.\n";
    exit(0);
}

echo "\n🔧 Starting duplicate fix...\n\n";

// Start transaction for safety
DB::beginTransaction();

try {
    $fixedCount = 0;
    
    // Fix 1: CSX-190 duplicates (Customer 7) - Major issue with 9 entries
    echo "1. Fixing CSX-190 duplicates (Customer 7)...\n";
    $csx190Entries = DB::table('ledgers')
        ->where('reference_no', 'CSX-190')
        ->where('contact_id', 7)
        ->where('status', 'active')
        ->orderBy('created_at', 'desc')
        ->get();
        
    if ($csx190Entries->count() > 1) {
        $keepEntry = $csx190Entries->first(); // Keep the latest
        $duplicateIds = $csx190Entries->skip(1)->pluck('id')->toArray();
        
        foreach ($duplicateIds as $id) {
            DB::table('ledgers')->where('id', $id)->update([
                'status' => 'reversed',
                'notes' => DB::raw("CONCAT(COALESCE(notes, ''), ' [DUPLICATE REMOVED: " . date('Y-m-d H:i:s') . "]')")
            ]);
            $fixedCount++;
        }
        
        echo "   ✅ Fixed: Kept ID {$keepEntry->id}, marked " . count($duplicateIds) . " as reversed\n";
        echo "   Duplicate IDs reversed: " . implode(', ', $duplicateIds) . "\n";
    }
    
    // Fix 2: PUR007 payment duplicates
    echo "\n2. Fixing PUR007 payment duplicates...\n";
    $pur007Payments = DB::table('ledgers')
        ->where('reference_no', 'PUR007')
        ->where('transaction_type', 'payments')
        ->where('status', 'active')
        ->orderBy('created_at', 'desc')
        ->get();
        
    if ($pur007Payments->count() > 1) {
        $keepEntry = $pur007Payments->first();
        $duplicateIds = $pur007Payments->skip(1)->pluck('id')->toArray();
        
        foreach ($duplicateIds as $id) {
            DB::table('ledgers')->where('id', $id)->update([
                'status' => 'reversed',
                'notes' => DB::raw("CONCAT(COALESCE(notes, ''), ' [DUPLICATE REMOVED: " . date('Y-m-d H:i:s') . "]')")
            ]);
            $fixedCount++;
        }
        
        echo "   ✅ Fixed: Kept ID {$keepEntry->id}, marked " . count($duplicateIds) . " as reversed\n";
    }
    
    // Fix 3: CSX-109 duplicates
    echo "\n3. Fixing CSX-109 duplicates (Customer 2)...\n";
    $csx109Entries = DB::table('ledgers')
        ->where('reference_no', 'CSX-109')
        ->where('contact_id', 2)
        ->where('transaction_type', 'sale')
        ->where('status', 'active')
        ->orderBy('created_at', 'desc')
        ->get();
        
    if ($csx109Entries->count() > 1) {
        $keepEntry = $csx109Entries->first();
        $duplicateIds = $csx109Entries->skip(1)->pluck('id')->toArray();
        
        foreach ($duplicateIds as $id) {
            DB::table('ledgers')->where('id', $id)->update([
                'status' => 'reversed',
                'notes' => DB::raw("CONCAT(COALESCE(notes, ''), ' [DUPLICATE REMOVED: " . date('Y-m-d H:i:s') . "]')")
            ]);
            $fixedCount++;
        }
        
        echo "   ✅ Fixed: Kept ID {$keepEntry->id}, marked " . count($duplicateIds) . " as reversed\n";
    }
    
    // Fix 4: CSX-295 duplicates
    echo "\n4. Fixing CSX-295 duplicates (Customer 43)...\n";
    $csx295Entries = DB::table('ledgers')
        ->where('reference_no', 'CSX-295')
        ->where('contact_id', 43)
        ->where('transaction_type', 'sale')
        ->where('status', 'active')
        ->orderBy('created_at', 'desc')
        ->get();
        
    if ($csx295Entries->count() > 1) {
        $keepEntry = $csx295Entries->first();
        $duplicateIds = $csx295Entries->skip(1)->pluck('id')->toArray();
        
        foreach ($duplicateIds as $id) {
            DB::table('ledgers')->where('id', $id)->update([
                'status' => 'reversed',
                'notes' => DB::raw("CONCAT(COALESCE(notes, ''), ' [DUPLICATE REMOVED: " . date('Y-m-d H:i:s') . "]')")
            ]);
            $fixedCount++;
        }
        
        echo "   ✅ Fixed: Kept ID {$keepEntry->id}, marked " . count($duplicateIds) . " as reversed\n";
    }
    
    // Fix 5: PUR006 duplicates
    echo "\n5. Fixing PUR006 purchase duplicates...\n";
    $pur006Entries = DB::table('ledgers')
        ->where('reference_no', 'PUR006')
        ->where('transaction_type', 'purchase')
        ->where('status', 'active')
        ->orderBy('created_at', 'desc')
        ->get();
        
    if ($pur006Entries->count() > 1) {
        $keepEntry = $pur006Entries->first();
        $duplicateIds = $pur006Entries->skip(1)->pluck('id')->toArray();
        
        foreach ($duplicateIds as $id) {
            DB::table('ledgers')->where('id', $id)->update([
                'status' => 'reversed',
                'notes' => DB::raw("CONCAT(COALESCE(notes, ''), ' [DUPLICATE REMOVED: " . date('Y-m-d H:i:s') . "]')")
            ]);
            $fixedCount++;
        }
        
        echo "   ✅ Fixed: Kept ID {$keepEntry->id}, marked " . count($duplicateIds) . " as reversed\n";
    }
    
    // Fix 6: FLEX payment duplicates (Customer 2)
    echo "\n6. Fixing FLEX payment duplicates (Customer 2)...\n";
    $flexEntries = DB::table('ledgers')
        ->where('reference_no', 'FLEX-20251118-154340-E810')
        ->where('contact_id', 2)
        ->where('transaction_type', 'payments')
        ->where('status', 'active')
        ->orderBy('created_at', 'desc')
        ->get();
        
    if ($flexEntries->count() > 1) {
        // For bulk payments like this, we might want to keep all if they're for different amounts
        // But if they're exact duplicates, remove them
        $keepEntry = $flexEntries->first();
        $duplicateIds = $flexEntries->skip(1)->pluck('id')->toArray();
        
        // Check if they're actually duplicates (same amount)
        $uniqueAmounts = $flexEntries->pluck('credit')->unique();
        if ($uniqueAmounts->count() < $flexEntries->count()) {
            // There are actual duplicates
            foreach ($duplicateIds as $id) {
                DB::table('ledgers')->where('id', $id)->update([
                    'status' => 'reversed',
                    'notes' => DB::raw("CONCAT(COALESCE(notes, ''), ' [BULK PAYMENT DUPLICATE REMOVED: " . date('Y-m-d H:i:s') . "]')")
                ]);
                $fixedCount++;
            }
            echo "   ✅ Fixed: Kept ID {$keepEntry->id}, marked " . count($duplicateIds) . " as reversed\n";
        } else {
            echo "   ℹ️  Skipped: These appear to be different payment amounts (bulk payment)\n";
        }
    }
    
    // Fix 7: Any other exact duplicates
    echo "\n7. Fixing remaining exact duplicates...\n";
    $exactDuplicates = DB::select("
        SELECT 
            reference_no, 
            contact_id, 
            contact_type,
            transaction_type,
            debit,
            credit,
            GROUP_CONCAT(id ORDER BY created_at DESC) as ids,
            COUNT(*) as count
        FROM ledgers 
        WHERE status = 'active'
        GROUP BY reference_no, contact_id, contact_type, transaction_type, debit, credit
        HAVING COUNT(*) > 1
    ");
    
    foreach ($exactDuplicates as $duplicate) {
        $ids = explode(',', $duplicate->ids);
        $keepId = array_shift($ids); // Keep the first (latest)
        
        foreach ($ids as $id) {
            DB::table('ledgers')->where('id', $id)->update([
                'status' => 'reversed',
                'notes' => DB::raw("CONCAT(COALESCE(notes, ''), ' [EXACT DUPLICATE REMOVED: " . date('Y-m-d H:i:s') . "]')")
            ]);
            $fixedCount++;
        }
        
        echo "   ✅ Fixed exact duplicate: {$duplicate->reference_no} - Kept ID {$keepId}, removed " . count($ids) . " duplicates\n";
    }
    
    // Commit the transaction
    DB::commit();
    
    echo "\n✅ DUPLICATE FIX COMPLETED SUCCESSFULLY!\n";
    echo "===========================================\n";
    echo "Total duplicate entries marked as reversed: {$fixedCount}\n\n";
    
    // Show the new status
    echo "📊 NEW LEDGER STATUS:\n";
    $newStats = DB::select("
        SELECT 
            COUNT(*) as total_entries,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active_entries,
            COUNT(CASE WHEN status = 'reversed' THEN 1 END) as reversed_entries
        FROM ledgers
    ")[0];
    
    echo "   Total entries: {$newStats->total_entries}\n";
    echo "   Active entries: {$newStats->active_entries}\n";
    echo "   Reversed entries: {$newStats->reversed_entries}\n\n";
    
    // Check Customer 7 balance after fix
    echo "🔍 CUSTOMER 7 (ALM RIYATH) AFTER FIX:\n";
    $customer7Balance = DB::table('ledgers')
        ->where('contact_id', 7)
        ->where('contact_type', 'customer')
        ->where('status', 'active')
        ->selectRaw('SUM(debit - credit) as balance')
        ->first();
        
    echo "   New calculated balance: {$customer7Balance->balance}\n";
    echo "   Expected balance: 37410 (opening balance)\n";
    
    if (abs($customer7Balance->balance - 37410) < 1) {
        echo "   ✅ Balance looks correct now!\n";
    } else {
        echo "   ⚠️  Balance still needs adjustment\n";
    }
    
    echo "\n🎉 Your ledger duplicates have been fixed!\n";
    echo "You can now run 'php test_ledger.php' to verify the fix.\n";
    
} catch (Exception $e) {
    // Rollback on error
    DB::rollback();
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "All changes have been rolled back.\n";
    exit(1);
}