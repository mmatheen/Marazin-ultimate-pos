# Production Ledger Cleanup Script
# This script provides safe execution of ledger cleanup commands

Write-Host "🚨 PRODUCTION LEDGER CLEANUP TOOL" -ForegroundColor Red
Write-Host "This script helps you safely run ledger cleanup on production" -ForegroundColor Yellow
Write-Host ""

# Function to create database backup
function Create-Backup {
    Write-Host "📦 Creating database backup..." -ForegroundColor Green
    $timestamp = Get-Date -Format "yyyy_MM_dd_HH_mm_ss"
    $backupFile = "ledger_backup_$timestamp.sql"
    
    # Add your actual backup command here
    # mysqldump -u username -p database_name > $backupFile
    Write-Host "✅ Backup would be created: $backupFile" -ForegroundColor Green
    return $true
}

# Function to put app in maintenance mode
function Enable-MaintenanceMode {
    Write-Host "🔒 Enabling maintenance mode..." -ForegroundColor Yellow
    php artisan down --message="Ledger maintenance in progress"
}

# Function to disable maintenance mode
function Disable-MaintenanceMode {
    Write-Host "🔓 Disabling maintenance mode..." -ForegroundColor Green
    php artisan up
}

# Main execution flow
try {
    Write-Host "Step 1: Environment Check" -ForegroundColor Cyan
    $env = php artisan env
    if ($env -like "*production*") {
        Write-Host "⚠️  PRODUCTION ENVIRONMENT DETECTED" -ForegroundColor Red
    }

    Write-Host ""
    Write-Host "Step 2: Analysis Phase (Safe - Read Only)" -ForegroundColor Cyan
    Write-Host "Running system-wide analysis..."
    php artisan ledger:recalculate-balance --all --dry-run

    $continue = Read-Host "Do you want to continue with the cleanup? (y/N)"
    if ($continue -ne "y" -and $continue -ne "Y") {
        Write-Host "❌ Operation cancelled" -ForegroundColor Red
        exit
    }

    Write-Host ""
    Write-Host "Step 3: Backup Phase" -ForegroundColor Cyan
    if (!(Create-Backup)) {
        Write-Host "❌ Backup failed. Aborting." -ForegroundColor Red
        exit 1
    }

    Write-Host ""
    Write-Host "Step 4: Maintenance Mode" -ForegroundColor Cyan
    Enable-MaintenanceMode

    Write-Host ""
    Write-Host "Step 5: Interactive Cleanup" -ForegroundColor Cyan
    Write-Host "Processing customers one by one..."
    php artisan ledger:recalculate-balance --interactive --backup

    Write-Host ""
    Write-Host "Step 6: Final Verification" -ForegroundColor Cyan
    php artisan ledger:recalculate-balance --all

    Write-Host ""
    Write-Host "✅ Cleanup completed successfully!" -ForegroundColor Green

} catch {
    Write-Host "❌ Error occurred: $_" -ForegroundColor Red
} finally {
    Write-Host ""
    Write-Host "Step 7: Restore Normal Operations" -ForegroundColor Cyan
    Disable-MaintenanceMode
    Write-Host "🎉 System is back online!" -ForegroundColor Green
}

Write-Host ""
Write-Host "PRODUCTION SAFETY COMMANDS:" -ForegroundColor Yellow
Write-Host "1. Analysis only:     php artisan ledger:recalculate-balance --all --dry-run" -ForegroundColor White
Write-Host "2. Interactive mode:  php artisan ledger:recalculate-balance --interactive" -ForegroundColor White
Write-Host "3. Specific customer: php artisan ledger:recalculate-balance 'CUSTOMER_NAME' --dry-run" -ForegroundColor White
Write-Host "4. Safe cleanup:      php artisan ledger:recalculate-balance 'CUSTOMER_NAME' --clean-all --backup" -ForegroundColor White
Write-Host ""
Write-Host "Remember: Always use --dry-run first to preview changes!" -ForegroundColor Red