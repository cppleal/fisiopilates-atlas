@echo off
REM ================================================================
REM SCRIPT DE CREACION DE VERSION - Fisiopilates Atlas
REM
REM Uso:
REM   crear-version.bat 1.1.0 nueva_pagina_blog
REM   crear-version.bat 1.0.1 correccion_formulario
REM
REM Pasos que realiza:
REM   1. Backup de base de datos (TEST)
REM   2. Revision y actualizacion de specs
REM   3. Crea carpeta en versiones/vX.Y.Z-descripcion/
REM   4. Genera plantilla de changelog.md y la abre para edicion
REM   5. Actualiza fichero VERSION
REM   6. Hace git commit + push
REM ================================================================

setlocal EnableDelayedExpansion
chcp 65001 >nul

set "CYAN=[96m"
set "GREEN=[92m"
set "YELLOW=[93m"
set "RED=[91m"
set "RESET=[0m"

REM --- Validar argumentos ---
if "%1"=="" (
    echo %RED%ERROR: Debes indicar el numero de version.%RESET%
    echo.
    echo Uso: crear-version.bat ^<version^> ^<descripcion^>
    echo Ejemplo: crear-version.bat 1.1.0 nueva_pagina_blog
    exit /b 1
)
if "%2"=="" (
    echo %RED%ERROR: Debes indicar una descripcion breve (sin espacios).%RESET%
    echo.
    echo Uso: crear-version.bat ^<version^> ^<descripcion^>
    echo Ejemplo: crear-version.bat 1.1.0 nueva_pagina_blog
    exit /b 1
)

set "VERSION=%1"
set "DESC=%2"
set "VERSION_TAG=v%VERSION%-%DESC%"
set "VERSION_DIR=%~dp0versiones\%VERSION_TAG%"
set "CHANGELOG=%VERSION_DIR%\changelog.md"
set "FECHA=%DATE:~6,4%-%DATE:~3,2%-%DATE:~0,2%"

echo %CYAN%============================================%RESET%
echo %CYAN%CREAR VERSION - Fisiopilates Atlas%RESET%
echo %CYAN%============================================%RESET%
echo.
echo  Version: v%VERSION%
echo  Tag:     %VERSION_TAG%
echo  Fecha:   %FECHA%
echo.

REM --- Verificar que no exista ya la version ---
if exist "%VERSION_DIR%" (
    echo %RED%ERROR: Ya existe la carpeta %VERSION_TAG%%RESET%
    exit /b 1
)

REM --- PASO 1: Backup de base de datos ---
echo %YELLOW%[1/6] Backup de base de datos (TEST + PROD)...%RESET%
php "%~dp0backup\backup-database.php" all v%VERSION%
if errorlevel 1 (
    echo %RED%ERROR en el backup. Abortando.%RESET%
    exit /b 1
)
echo %GREEN%Backup completado.%RESET%
echo.

REM --- PASO 2: Revision de specs ---
echo %YELLOW%[2/6] Revision de especificaciones tecnicas...%RESET%
echo.
echo  Revisa y actualiza los ficheros de specs que correspondan:
echo.
echo   specs\arquitectura.md  - Si cambiaron stack o estructura
echo   specs\backend.md       - Si cambiaron PHP, BD o endpoints
echo   specs\admin.md         - Si cambio el panel de administracion
echo   specs\cookies-rgpd.md  - Si cambio el sistema de cookies
echo   specs\paginas.md       - Si se anadieron o modificaron paginas
echo   specs\deploy.md        - Si cambio el proceso de deploy o versionado
echo.
echo  Abriendo carpeta specs...
explorer "%~dp0specs"
echo.
echo  Edita los specs necesarios, GUARDALOS y cuando termines
echo  pulsa cualquier tecla para continuar.
echo.
pause

REM --- PASO 3: Crear carpeta de version ---
echo %YELLOW%[3/6] Creando carpeta de version...%RESET%
mkdir "%VERSION_DIR%"

REM --- PASO 4: Generar plantilla de changelog y abrir para edicion ---
echo %YELLOW%[4/6] Generando changelog...%RESET%

(
echo # v%VERSION% — %DESC%
echo **Fecha:** %FECHA%
echo.
echo ## Resumen
echo ^< descripcion breve de los cambios ^>
echo.
echo ## Cambios incluidos
echo.
echo ### 1. Frontend
echo - `src/...` —
echo.
echo ### 2. Backend
echo - `php/...` —
echo.
echo ### 3. Especificaciones actualizadas
echo - `specs/...` — descripcion de que se actualizo
echo.
echo ## Notas de actualizacion
echo - ^< instrucciones especiales si aplica ^>
) > "%CHANGELOG%"

echo %GREEN%Plantilla creada en: versiones\%VERSION_TAG%\changelog.md%RESET%
echo.
echo  Edita el changelog, GUARDALO y CIERRA el editor.
echo  Cuando termines, pulsa cualquier tecla para continuar.
echo.
start /wait notepad.exe "%CHANGELOG%"
pause

REM --- PASO 5: Actualizar fichero VERSION ---
echo %YELLOW%[5/6] Actualizando VERSION...%RESET%
echo %VERSION%> "%~dp0VERSION"
echo %GREEN%VERSION actualizado a %VERSION%%RESET%
echo.

REM --- PASO 6: Git commit y push ---
echo %YELLOW%[6/6] Commit y push a GitHub...%RESET%
cd /d "%~dp0"
git add -A
git commit -m "version %VERSION_TAG%"
git push
if errorlevel 1 (
    echo %YELLOW%AVISO: Push fallido. Verifica la conexion con GitHub.%RESET%
    echo Puedes hacer el push manualmente: git push
) else (
    echo %GREEN%Push completado.%RESET%
)

echo.
echo %GREEN%============================================%RESET%
echo %GREEN%Version %VERSION_TAG% creada correctamente.%RESET%
echo %GREEN%============================================%RESET%
echo.
echo  Resumen:
echo   - Backup BD:   backup\test\v%VERSION%\ y backup\prod\v%VERSION%\
echo   - Specs:       specs\ (actualizadas manualmente)
echo   - Changelog:   versiones\%VERSION_TAG%\changelog.md
echo   - VERSION:     %VERSION%
echo   - Git commit:  version %VERSION_TAG%
echo.
