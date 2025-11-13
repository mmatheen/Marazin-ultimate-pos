@echo off
title Database Cleanup Solution
color 0B

echo.
echo ========================================
echo    DATABASE CLEANUP SOLUTION
echo ========================================
echo.

:: Check if PHP is available
php -v >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] PHP is not installed or not in PATH
    echo Please install PHP and add it to your system PATH
    pause
    exit /b 1
)

:: Check if we're in the correct directory
if not exist "comprehensive_database_fix.php" (
    echo [ERROR] comprehensive_database_fix.php not found
    echo Please run this from the Marazin-ultimate-pos directory
    pause
    exit /b 1
)

echo [OK] PHP is available
echo [OK] Scripts found in current directory
echo.

:MENU
echo ========================================
echo    CHOOSE AN OPTION
echo ========================================
echo.
echo 1. Analysis Only (Safe - Check issues)
echo 2. Test Mode (Dry run - See what would be fixed)
echo 3. Fix with Confirmation (Recommended)
echo 4. Fix Automatically (Advanced users)
echo 5. View Latest Reports
echo 6. Exit
echo.
set /p choice="Enter your choice (1-6): "

if "%choice%"=="1" goto ANALYSIS
if "%choice%"=="2" goto DRYRUN
if "%choice%"=="3" goto FIXCONFIRM
if "%choice%"=="4" goto FIXAUTO
if "%choice%"=="5" goto REPORTS
if "%choice%"=="6" goto EXIT
echo Invalid choice. Please try again.
goto MENU

:ANALYSIS
echo.
echo [INFO] Running analysis only...
echo This will check for issues without making changes.
echo.
php production_safe_analysis.php
pause
goto MENU

:DRYRUN
echo.
echo [INFO] Running test mode (dry run)...
echo This shows what would be fixed without making changes.
echo.
php comprehensive_database_fix.php --dry-run
pause
goto MENU

:FIXCONFIRM
echo.
echo [WARNING] This will make REAL changes to your database!
echo Automatic backups will be created before changes.
echo.
set /p confirm="Are you sure you want to proceed? (yes/no): "
if /i "%confirm%"=="yes" (
    echo.
    echo [INFO] Running comprehensive fix with confirmations...
    php comprehensive_database_fix.php
) else (
    echo Operation cancelled.
)
pause
goto MENU

:FIXAUTO
echo.
echo [WARNING] This will make REAL changes WITHOUT confirmation!
echo This is for advanced users only.
echo.
set /p confirm="Are you absolutely sure? Type YES to proceed: "
if "%confirm%"=="YES" (
    echo.
    echo [INFO] Running comprehensive fix automatically...
    php comprehensive_database_fix.php --no-confirm
) else (
    echo Operation cancelled.
)
pause
goto MENU

:REPORTS
echo.
echo [INFO] Looking for latest reports...
echo.
if exist "comprehensive_fix_report_*.json" (
    echo Latest Fix Reports:
    dir /b comprehensive_fix_report_*.json
    echo.
)
if exist "ledger_analysis_*.json" (
    echo Latest Analysis Reports:
    dir /b ledger_analysis_*.json
    echo.
)
if not exist "comprehensive_fix_report_*.json" if not exist "ledger_analysis_*.json" (
    echo No reports found. Run an analysis first.
)
pause
goto MENU

:EXIT
echo.
echo Goodbye!
pause
exit /b 0