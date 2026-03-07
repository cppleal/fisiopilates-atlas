@echo off
REM ================================================================
REM SCRIPT DE BACKUP DE BASE DE DATOS - Fisiopilates Atlas
REM Uso: backup.bat [test|prod|all] [version]
REM Ejemplos:
REM   backup.bat test v1.0.0
REM   backup.bat all  v1.0.0
REM   backup.bat test
REM ================================================================

setlocal EnableDelayedExpansion
chcp 65001 >nul

set "CYAN=[96m"
set "GREEN=[92m"
set "RESET=[0m"

echo %CYAN%============================================%RESET%
echo %CYAN%BACKUP BD - Fisiopilates Atlas%RESET%
echo %CYAN%============================================%RESET%

set ENV=%1
if "%ENV%"=="" set ENV=test

echo.
echo Ejecutando backup de: %ENV%
echo.

php "%~dp0backup-database.php" %*

echo.
echo %GREEN%Proceso finalizado.%RESET%
