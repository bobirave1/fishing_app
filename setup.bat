@echo off
title FISHINGLORY - Database Setup
color 0A

echo ========================================
echo   FISHINGLORY Database Setup
echo ========================================
echo.

REM Check if XAMPP PHP exists
if exist "C:\xampp\php\php.exe" (
    set PHP_PATH=C:\xampp\php\php.exe
) else (
    echo [ERROR] PHP not found in C:\xampp\php\
    echo Please make sure XAMPP is installed.
    pause
    exit /b 1
)

echo Running database setup...
echo.

"%PHP_PATH%" "%~dp0setup.php"

echo.
echo ========================================
if %ERRORLEVEL% EQU 0 (
    echo Setup completed successfully!
    color 0A
) else (
    echo Setup failed. Please check the errors above.
    color 0C
)
echo ========================================
echo.

pause
