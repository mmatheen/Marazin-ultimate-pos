@echo off
echo ==========================================
echo PRODUCTION DATABASE LEDGER MANAGEMENT
echo ==========================================
echo.

:menu
echo Choose an option:
echo 1. Create Database Backup (RECOMMENDED FIRST)
echo 2. Check Database (Read-Only, Safe)
echo 3. Check and Fix Database (Will modify data)
echo 4. Exit
echo.
set /p choice="Enter your choice (1-4): "

if "%choice%"=="1" goto backup
if "%choice%"=="2" goto check
if "%choice%"=="3" goto fix
if "%choice%"=="4" goto exit
goto menu

:backup
echo.
echo Creating database backup...
php create_backup.php
echo.
pause
goto menu

:check
echo.
echo Checking database (read-only mode)...
php production_ledger_checker.php
echo.
pause
goto menu

:fix
echo.
echo WARNING: This will modify your database!
set /p confirm="Are you sure? Type 'yes' to continue: "
if not "%confirm%"=="yes" goto menu
echo.
echo Opening script to enable fix mode...
echo Please change $DRY_RUN = true to $DRY_RUN = false in the script
pause
notepad production_ledger_checker.php
goto menu

:exit
echo.
echo Goodbye!
exit