@echo off
setlocal

REM =============================================
REM DESCARGA DE IMAGENES DE PRODUCCION (Joomla)
REM Solo lectura - no modifica nada en PROD
REM Descarga a: ..\public\images\
REM =============================================

REM Cargar configuracion
call "%~dp0deploy-config.bat"

echo.
echo === Fisiopilates Atlas - Descarga de imagenes de PROD ===
echo Host PROD: %FTP_HOST_PROD%
echo Destino local: %~dp0..\public\images\
echo.
echo Solo se descargan archivos de imagen (jpg, png, gif, webp, svg).
echo No se modifica nada en el servidor de produccion.
echo.

REM Crear directorio local si no existe
if not exist "%~dp0..\public\images\" mkdir "%~dp0..\public\images\"

REM Script WinSCP para listar y descargar imagenes
set WINSCP_SCRIPT=%TEMP%\fisio_images_%RANDOM%.txt
set LOCAL_IMAGES=%~dp0..\public\images

echo open ftp://%FTP_USER_PROD%:%FTP_PASS_PROD%@%FTP_HOST_PROD%/ > "%WINSCP_SCRIPT%"
echo option batch abort >> "%WINSCP_SCRIPT%"
echo option confirm off >> "%WINSCP_SCRIPT%"
echo option transfer binary >> "%WINSCP_SCRIPT%"
echo cd %FTP_PATH_PROD% >> "%WINSCP_SCRIPT%"
echo lcd "%LOCAL_IMAGES%" >> "%WINSCP_SCRIPT%"

REM Sincronizar carpetas de imágenes típicas de Joomla
echo synchronize local -filemask="*.jpg;*.jpeg;*.png;*.gif;*.webp;*.svg;*.ico" >> "%WINSCP_SCRIPT%"

echo exit >> "%WINSCP_SCRIPT%"

REM Verificar WinSCP
if not exist "%WINSCP_PATH%" (
    echo ERROR: WinSCP no encontrado en: %WINSCP_PATH%
    del "%WINSCP_SCRIPT%" 2>nul
    exit /b 1
)

echo Conectando a PROD (solo lectura)...
"%WINSCP_PATH%" /script="%WINSCP_SCRIPT%" /log="%TEMP%\fisio_images.log"

if %ERRORLEVEL% equ 0 (
    echo.
    echo === Imagenes descargadas correctamente ===
    echo Ubicacion: %LOCAL_IMAGES%
) else (
    echo.
    echo ERROR al descargar imagenes.
    echo Log en: %TEMP%\fisio_images.log
)

del "%WINSCP_SCRIPT%" 2>nul
endlocal
