<?php
/**
 * SAFE DATABASE CLEANUP RUNNER
 * 
 * This script provides an easy-to-use interface for running database cleanup operations
 */

echo "=== SAFE DATABASE CLEANUP RUNNER ===\n\n";

echo "Choose an option:\n";
echo "1. Run Analysis Only (Check for issues without fixing)\n";
echo "2. Run Analysis + Fix (Dry Run) - Test mode\n";
echo "3. Run Full Fix with Confirmation - Live mode\n";
echo "4. Run Full Fix Automatically - Live mode (Advanced)\n";
echo "5. View Latest Analysis Report\n";
echo "6. Exit\n\n";

echo "Enter your choice (1-6): ";
$handle = fopen("php://stdin", "r");
$choice = trim(fgets($handle));

switch ($choice) {
    case '1':
        echo "\n🔍 Running analysis only...\n";
        exec("php production_safe_analysis.php", $output, $return_var);
        foreach ($output as $line) {
            echo $line . "\n";
        }
        break;
        
    case '2':
        echo "\n🧪 Running comprehensive fix in DRY RUN mode...\n";
        exec("php comprehensive_database_fix.php --dry-run", $output, $return_var);
        foreach ($output as $line) {
            echo $line . "\n";
        }
        break;
        
    case '3':
        echo "\n⚠️  This will make REAL changes to your database!\n";
        echo "Are you sure you want to proceed? (yes/no): ";
        $confirm = trim(fgets($handle));
        
        if (strtolower($confirm) === 'yes') {
            echo "\n🔧 Running comprehensive fix with confirmation prompts...\n";
            exec("php comprehensive_database_fix.php", $output, $return_var);
            foreach ($output as $line) {
                echo $line . "\n";
            }
        } else {
            echo "❌ Operation cancelled.\n";
        }
        break;
        
    case '4':
        echo "\n⚠️  This will make REAL changes to your database WITHOUT confirmation!\n";
        echo "This is for advanced users only. Are you absolutely sure? (YES/NO): ";
        $confirm = trim(fgets($handle));
        
        if ($confirm === 'YES') {
            echo "\n🔧 Running comprehensive fix automatically...\n";
            exec("php comprehensive_database_fix.php --no-confirm", $output, $return_var);
            foreach ($output as $line) {
                echo $line . "\n";
            }
        } else {
            echo "❌ Operation cancelled.\n";
        }
        break;
        
    case '5':
        echo "\n📄 Looking for latest analysis report...\n";
        $analysisFiles = glob('ledger_analysis_*.json');
        $fixReports = glob('comprehensive_fix_report_*.json');
        
        if (empty($analysisFiles) && empty($fixReports)) {
            echo "❌ No reports found. Run an analysis first.\n";
        } else {
            $latestAnalysis = empty($analysisFiles) ? null : end($analysisFiles);
            $latestFix = empty($fixReports) ? null : end($fixReports);
            
            if ($latestFix) {
                echo "📊 Latest Fix Report: {$latestFix}\n";
                $data = json_decode(file_get_contents($latestFix), true);
                echo "   Timestamp: {$data['timestamp']}\n";
                echo "   Mode: {$data['mode']}\n";
                echo "   Total Fixes: {$data['total_fixes_applied']}\n";
                echo "   Issues Found: {$data['summary']['total_issues']}\n\n";
            }
            
            if ($latestAnalysis) {
                echo "📊 Latest Analysis Report: {$latestAnalysis}\n";
                $data = json_decode(file_get_contents($latestAnalysis), true);
                echo "   Timestamp: {$data['timestamp']}\n";
                echo "   Customer Issues: {$data['summary']['customers_with_issues']}\n";
                echo "   Supplier Issues: {$data['summary']['suppliers_with_issues']}\n";
                echo "   Total Issues: {$data['summary']['total_issues_found']}\n\n";
            }
        }
        break;
        
    case '6':
        echo "👋 Goodbye!\n";
        exit(0);
        
    default:
        echo "❌ Invalid choice. Please run the script again and choose 1-6.\n";
        exit(1);
}

fclose($handle);
echo "\n✅ Operation completed.\n";
?>