@echo off
:: =========================================================================
:: Script Auto-Backup Database MySQL untuk XAMPP di Windows
:: Daijo MES Project
:: =========================================================================

setlocal enabledelayedexpansion

title Daijo MES - Database Auto Backup

:: 1. Tentukan Path Direktori Proyek
set "PROJECT_DIR=%~dp0.."
pushd "%PROJECT_DIR%"
set "PROJECT_DIR=%CD%"
popd
set "BACKUP_DIR=%PROJECT_DIR%\storage\app\backups"

if not exist "%BACKUP_DIR%" (
    mkdir "%BACKUP_DIR%"
)

echo =======================================================
echo          DAIJO MES - DATABASE BACKUP UTILITY
echo =======================================================
echo [INFO] Project Directory : %PROJECT_DIR%
echo [INFO] Backup Directory  : %BACKUP_DIR%
echo -------------------------------------------------------

:: 2. Cari executable PHP (di PATH atau di folder standar XAMPP)
set "PHP_BIN="
where php >nul 2>nul
if %ERRORLEVEL% EQU 0 (
    set "PHP_BIN=php"
) else if exist "C:\xampp\php\php.exe" (
    set "PHP_BIN=C:\xampp\php\php.exe"
) else if exist "D:\xampp\php\php.exe" (
    set "PHP_BIN=D:\xampp\php\php.exe"
) else if exist "E:\xampp\php\php.exe" (
    set "PHP_BIN=E:\xampp\php\php.exe"
)

:: 3. Jika PHP ditemukan, jalankan lewat Laravel Artisan (Paling Akurat & Membaca .env)
if defined PHP_BIN (
    echo [INFO] Menjalankan backup melalui Laravel Artisan (%PHP_BIN%)...
    cd /d "%PROJECT_DIR%"
    "%PHP_BIN%" artisan db:backup --keep-days=14
    set "EXIT_CODE=!ERRORLEVEL!"
    
    if !EXIT_CODE! EQU 0 (
        echo.
        echo [SUCCESS] Backup database selesai dengan sukses!
    ) else (
        echo.
        echo [ERROR] Backup melalui Artisan gagal dengan exit code !EXIT_CODE!.
        echo [INFO] Mencoba fallback backup manual via mysqldump...
        goto :FALLBACK_MYSQLDUMP
    )
    goto :FINISH
)

:FALLBACK_MYSQLDUMP
:: 4. Fallback Manual via mysqldump
echo [INFO] Mencari mysqldump.exe...
set "MYSQLDUMP_PATH=C:\xampp\mysql\bin\mysqldump.exe"
if not exist "%MYSQLDUMP_PATH%" set "MYSQLDUMP_PATH=D:\xampp\mysql\bin\mysqldump.exe"
if not exist "%MYSQLDUMP_PATH%" set "MYSQLDUMP_PATH=E:\xampp\mysql\bin\mysqldump.exe"

if not exist "%MYSQLDUMP_PATH%" (
    where mysqldump >nul 2>nul
    if %ERRORLEVEL% EQU 0 (
        set "MYSQLDUMP_PATH=mysqldump"
    ) else (
        echo [ERROR] mysqldump.exe tidak ditemukan di folder XAMPP atau PATH!
        goto :ERROR_EXIT
    )
)

echo [INFO] Menggunakan: %MYSQLDUMP_PATH%

:: Konfigurasi Database
set "DB_HOST=127.0.0.1"
set "DB_PORT=3306"
set "DB_USER=root"
set "DB_PASS=andre123"
set "DB_NAME=DAIJO_MES"

:: Generate Timestamp yang aman di semua versi Windows (menggunakan PowerShell)
for /f "usebackq tokens=*" %%a in (`powershell -Command "Get-Date -Format 'yyyy-MM-dd_HHmmss'" 2^>nul`) do set "TIMESTAMP=%%a"
if "%TIMESTAMP%"=="" (
    for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value 2^>nul') do set datetime=%%I
    set "TIMESTAMP=!datetime:~0,4!-!datetime:~4,2!-!datetime:~6,2!_!datetime:~8,2!!datetime:~10,2!!datetime:~12,2!"
)
if "%TIMESTAMP%"=="" set "TIMESTAMP=%date:~-4,4%-%date:~-7,2%-%date:~-10,2%_manual"

set "BACKUP_FILE=%BACKUP_DIR%\backup_%DB_NAME%_%TIMESTAMP%.sql"

echo [INFO] Memulai export database %DB_NAME% ke:
echo        %BACKUP_FILE%

if "%DB_PASS%"=="" (
    "%MYSQLDUMP_PATH%" --host=%DB_HOST% --port=%DB_PORT% --user=%DB_USER% --routines --triggers --single-transaction --quick --lock-tables=false %DB_NAME% > "%BACKUP_FILE%"
) else (
    "%MYSQLDUMP_PATH%" --host=%DB_HOST% --port=%DB_PORT% --user=%DB_USER% --password=%DB_PASS% --routines --triggers --single-transaction --quick --lock-tables=false %DB_NAME% > "%BACKUP_FILE%"
)

if exist "%BACKUP_FILE%" (
    for %%A in ("%BACKUP_FILE%") do set "FILE_SIZE=%%~zA"
    if !FILE_SIZE! GTR 0 (
        echo [SUCCESS] Backup BERHASIL dibuat!
        echo           Ukuran File: !FILE_SIZE! bytes
        
        :: Bersihkan file lama (> 14 hari)
        forfiles /p "%BACKUP_DIR%" /s /m *.sql /d -14 /c "cmd /c del @path" 2>nul
        goto :FINISH
    ) else (
        echo [ERROR] File backup terbuat tetapi berukuran 0 byte (Kosong)!
        del "%BACKUP_FILE%" 2>nul
        goto :ERROR_EXIT
    )
) else (
    echo [ERROR] Gagal membuat file backup .sql!
    goto :ERROR_EXIT
)

:FINISH
echo -------------------------------------------------------
echo [DAFTAR FILE BACKUP SAAT INI]:
dir /b /o-d "%BACKUP_DIR%\*.sql" 2>nul
echo =======================================================
echo Proses selesai.
goto :END

:ERROR_EXIT
echo =======================================================
echo [ERROR] Proses backup mengalami kegagalan!
echo =======================================================

:END
:: Jika dijalankan secara manual (double click), pause agar output terbaca
echo %cmdcmdline% | find /i "%~0" >nul
if %ERRORLEVEL% EQU 0 (
    echo.
    echo Tekan sembarang tombol untuk keluar...
    pause >nul
)
