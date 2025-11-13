# Database Cleanup Runner for Windows PowerShell
# Run this script to safely fix your database issues

Write-Host "=== DATABASE CLEANUP SOLUTION ===" -ForegroundColor Cyan
Write-Host ""

# Check if we're in the correct directory
if (!(Test-Path "comprehensive_database_fix.php")) {
    Write-Host "❌ Error: comprehensive_database_fix.php not found in current directory" -ForegroundColor Red
    Write-Host "Please navigate to the Marazin-ultimate-pos directory first" -ForegroundColor Yellow
    exit 1
}

# Check PHP installation
try {
    $phpVersion = php -v 2>$null
    if ($LASTEXITCODE -ne 0) {
        throw "PHP not found"
    }
    Write-Host "✅ PHP is available" -ForegroundColor Green
} catch {
    Write-Host "❌ Error: PHP is not installed or not in PATH" -ForegroundColor Red
    Write-Host "Please install PHP and add it to your system PATH" -ForegroundColor Yellow
    exit 1
}

Write-Host ""
Write-Host "🔧 SAFE DATABASE CLEANUP OPTIONS:" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. 🔍 Analysis Only (Check for issues without fixing)" -ForegroundColor White
Write-Host "2. 🧪 Test Mode (Dry Run - See what would be fixed)" -ForegroundColor White
Write-Host "3. ⚡ Fix with Confirmation (Recommended)" -ForegroundColor White
Write-Host "4. 🚀 Fix Automatically (Advanced users)" -ForegroundColor White
Write-Host "5. 📄 View Latest Reports" -ForegroundColor White
Write-Host "6. 📖 Show Help" -ForegroundColor White
Write-Host "7. ❌ Exit" -ForegroundColor White
Write-Host ""

do {
    $choice = Read-Host "Choose an option (1-7)"
    
    switch ($choice) {
        "1" {
            Write-Host ""
            Write-Host "🔍 Running analysis only..." -ForegroundColor Cyan
            Write-Host "This will check for issues without making any changes to your database." -ForegroundColor Yellow
            Write-Host ""
            php production_safe_analysis.php
            break
        }
        
        "2" {
            Write-Host ""
            Write-Host "🧪 Running comprehensive fix in TEST MODE..." -ForegroundColor Cyan
            Write-Host "This will show you what would be fixed without making actual changes." -ForegroundColor Yellow
            Write-Host ""
            php comprehensive_database_fix.php --dry-run
            break
        }
        
        "3" {
            Write-Host ""
            Write-Host "⚠️  This will make REAL changes to your database!" -ForegroundColor Red
            Write-Host "Automatic backups will be created before any changes." -ForegroundColor Yellow
            $confirm = Read-Host "Are you sure you want to proceed? (yes/no)"
            
            if ($confirm.ToLower() -eq "yes") {
                Write-Host ""
                Write-Host "🔧 Running comprehensive fix with confirmation prompts..." -ForegroundColor Cyan
                php comprehensive_database_fix.php
            } else {
                Write-Host "❌ Operation cancelled." -ForegroundColor Yellow
            }
            break
        }
        
        "4" {
            Write-Host ""
            Write-Host "⚠️  This will make REAL changes to your database WITHOUT confirmation!" -ForegroundColor Red
            Write-Host "This is for advanced users only." -ForegroundColor Yellow
            $confirm = Read-Host "Are you absolutely sure? Type 'YES' to proceed"
            
            if ($confirm -eq "YES") {
                Write-Host ""
                Write-Host "🔧 Running comprehensive fix automatically..." -ForegroundColor Cyan
                php comprehensive_database_fix.php --no-confirm
            } else {
                Write-Host "❌ Operation cancelled." -ForegroundColor Yellow
            }
            break
        }
        
        "5" {
            Write-Host ""
            Write-Host "📄 Looking for latest reports..." -ForegroundColor Cyan
            
            $analysisFiles = Get-ChildItem -Name "ledger_analysis_*.json" -ErrorAction SilentlyContinue
            $fixReports = Get-ChildItem -Name "comprehensive_fix_report_*.json" -ErrorAction SilentlyContinue
            
            if ($analysisFiles.Count -eq 0 -and $fixReports.Count -eq 0) {
                Write-Host "❌ No reports found. Run an analysis first." -ForegroundColor Red
            } else {
                if ($fixReports.Count -gt 0) {
                    $latestFix = $fixReports | Sort-Object | Select-Object -Last 1
                    Write-Host "📊 Latest Fix Report: $latestFix" -ForegroundColor Green
                    
                    $data = Get-Content $latestFix | ConvertFrom-Json
                    Write-Host "   Timestamp: $($data.timestamp)" -ForegroundColor White
                    Write-Host "   Mode: $($data.mode)" -ForegroundColor White
                    Write-Host "   Total Fixes: $($data.total_fixes_applied)" -ForegroundColor White
                    Write-Host "   Issues Found: $($data.summary.total_issues)" -ForegroundColor White
                    Write-Host ""
                }
                
                if ($analysisFiles.Count -gt 0) {
                    $latestAnalysis = $analysisFiles | Sort-Object | Select-Object -Last 1
                    Write-Host "📊 Latest Analysis Report: $latestAnalysis" -ForegroundColor Green
                    
                    $data = Get-Content $latestAnalysis | ConvertFrom-Json
                    Write-Host "   Timestamp: $($data.timestamp)" -ForegroundColor White
                    Write-Host "   Customer Issues: $($data.summary.customers_with_issues)" -ForegroundColor White
                    Write-Host "   Supplier Issues: $($data.summary.suppliers_with_issues)" -ForegroundColor White
                    Write-Host "   Total Issues: $($data.summary.total_issues_found)" -ForegroundColor White
                    Write-Host ""
                }
            }
            break
        }
        
        "6" {
            Write-Host ""
            Write-Host "📖 HELP - HOW TO USE THIS TOOL:" -ForegroundColor Yellow
            Write-Host ""
            Write-Host "🔍 Option 1 - Analysis Only:" -ForegroundColor Cyan
            Write-Host "   - Safe to run anytime" -ForegroundColor White
            Write-Host "   - Only reads your database" -ForegroundColor White
            Write-Host "   - Shows what issues exist" -ForegroundColor White
            Write-Host ""
            Write-Host "🧪 Option 2 - Test Mode:" -ForegroundColor Cyan
            Write-Host "   - Shows exactly what would be fixed" -ForegroundColor White
            Write-Host "   - No changes made to database" -ForegroundColor White
            Write-Host "   - Perfect for planning" -ForegroundColor White
            Write-Host ""
            Write-Host "⚡ Option 3 - Fix with Confirmation:" -ForegroundColor Cyan
            Write-Host "   - Creates automatic backups" -ForegroundColor White
            Write-Host "   - Asks permission for each major step" -ForegroundColor White
            Write-Host "   - Recommended for most users" -ForegroundColor White
            Write-Host ""
            Write-Host "🚀 Option 4 - Fix Automatically:" -ForegroundColor Cyan
            Write-Host "   - For advanced users only" -ForegroundColor White
            Write-Host "   - Runs without prompts" -ForegroundColor White
            Write-Host "   - Still creates backups" -ForegroundColor White
            Write-Host ""
            Write-Host "💾 SAFETY FEATURES:" -ForegroundColor Yellow
            Write-Host "   ✅ Automatic database backups" -ForegroundColor Green
            Write-Host "   ✅ Transaction rollback on errors" -ForegroundColor Green
            Write-Host "   ✅ Comprehensive logging" -ForegroundColor Green
            Write-Host "   ✅ Multiple validation checks" -ForegroundColor Green
            Write-Host ""
            break
        }
        
        "7" {
            Write-Host ""
            Write-Host "👋 Goodbye!" -ForegroundColor Green
            exit 0
        }
        
        default {
            Write-Host "❌ Invalid choice. Please choose 1-7." -ForegroundColor Red
        }
    }
    
    Write-Host ""
    Write-Host "Press any key to continue..."
    $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
    Write-Host ""
    
} while ($choice -ne "7")

Write-Host "✅ Operation completed." -ForegroundColor Green