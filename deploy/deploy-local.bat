@echo off
setlocal enabledelayedexpansion

REM =============================================
REM DEPLOY FISIOPILATES ATLAS via WinSCP
REM Uso: deploy-local.bat [test|prod] [archivo1] [archivo2] ...
REM Si no se especifican archivos, sincroniza todo el dist/
REM =============================================

REM Cargar configuracion
call "%~dp0deploy-config.bat"

REM Validar entorno
set ENV=%1
if "%ENV%"=="" (
    echo.
    echo USO: deploy-local.bat [test^|prod] [archivo1] [archivo2] ...
    echo.
    echo Ejemplos:
    echo   deploy-local.bat test                          - Sube todo dist/ a TEST
    echo   deploy-local.bat test index.html               - Sube solo index.html
    echo   deploy-local.bat test api/contacto.php         - Sube contacto.php
    echo   deploy-local.bat test api/contacto.php admin/index.php
    echo   deploy-local.bat prod                          - REQUIERE confirmacion
    echo.
    exit /b 1
)

REM Configurar segun entorno
if /i "%ENV%"=="test" (
    set FTP_HOST=%FTP_HOST_TEST%
    set FTP_USER=%FTP_USER_TEST%
    set FTP_PASS=%FTP_PASS_TEST%
    set FTP_PATH=%FTP_PATH_TEST%
    set ENV_NAME=TEST
) else if /i "%ENV%"=="prod" (
    echo.
    echo ========================================
    echo  !!! ATENCION: DEPLOY A PRODUCCION !!!
    echo ========================================
    echo  Host: %FTP_HOST_PROD%
    echo  La web en PRODUCCION tiene Joomla legacy activo.
    echo  Este deploy SOBREESCRIBIRA el contenido existente.
    echo ========================================
    echo.
    set /p CONFIRM="Escribe SI para confirmar el deploy a PRODUCCION: "
    if /i not "!CONFIRM!"=="SI" (
        echo.
        echo Deploy cancelado.
        exit /b 0
    )
    set FTP_HOST=%FTP_HOST_PROD%
    set FTP_USER=%FTP_USER_PROD%
    set FTP_PASS=%FTP_PASS_PROD%
    set FTP_PATH=%FTP_PATH_PROD%
    set ENV_NAME=PRODUCCION
) else (
    echo ERROR: Entorno no valido. Usa "test" o "prod".
    exit /b 1
)

echo.
echo === Fisiopilates Atlas - Deploy a %ENV_NAME% ===
echo Host: %FTP_HOST%
echo Ruta: %FTP_PATH%
echo.

REM Crear script temporal WinSCP
set WINSCP_SCRIPT=%TEMP%\fisio_deploy_%RANDOM%.txt

echo open ftp://%FTP_USER%:%FTP_PASS%@%FTP_HOST%/ > "%WINSCP_SCRIPT%"
echo option batch abort >> "%WINSCP_SCRIPT%"
echo option confirm off >> "%WINSCP_SCRIPT%"
echo cd %FTP_PATH% >> "%WINSCP_SCRIPT%"
echo lcd "%LOCAL_PATH%" >> "%WINSCP_SCRIPT%"

REM Determinar si hay archivos especificos
shift
if "%1"=="" (
    REM Subir todo sincronizando
    echo synchronize remote -delete >> "%WINSCP_SCRIPT%"
    echo Modo: Sincronizacion completa de dist/
) else (
    REM Subir archivos especificos
    echo Modo: Archivos especificos:
    :loop
    if not "%1"=="" (
        echo put "%1" >> "%WINSCP_SCRIPT%"
        echo   - %1
        shift
        goto :loop
    )
)

echo exit >> "%WINSCP_SCRIPT%"

REM Verificar WinSCP
if not exist "%WINSCP_PATH%" (
    echo.
    echo ERROR: WinSCP no encontrado en: %WINSCP_PATH%
    echo Instala WinSCP o ajusta WINSCP_PATH en deploy-config.bat
    del "%WINSCP_SCRIPT%" 2>nul
    exit /b 1
)

REM Ejecutar WinSCP
echo.
echo Conectando a %FTP_HOST%...
"%WINSCP_PATH%" /script="%WINSCP_SCRIPT%" /log="%TEMP%\fisio_deploy.log"

if %ERRORLEVEL% equ 0 (
    echo.
    echo === Deploy a %ENV_NAME% completado con exito ===
    if /i "%ENV_NAME%"=="TEST" echo URL: https://%FTP_HOST%
) else (
    echo.
    echo ERROR: El deploy ha fallado.
    echo Revisa el log en: %TEMP%\fisio_deploy.log
)

REM Limpiar script temporal
del "%WINSCP_SCRIPT%" 2>nul

endlocal
