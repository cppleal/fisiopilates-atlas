@echo off
REM =============================================
REM PLANTILLA DE CONFIGURACION DE DEPLOY
REM Copiar como deploy-config.bat y rellenar
REM deploy-config.bat NO se sube a git
REM =============================================

REM --- TEST (FTP puerto 21) ---
set FTP_HOST_TEST=40749769.servicio-online.net
set FTP_USER_TEST=user-10067489
set FTP_PASS_TEST=TU_PASS_TEST
set FTP_PATH_TEST=/httpdocs

REM --- PRODUCCION (FTP puerto 21, Joomla legacy - NO SOBRESCRIBIR) ---
set FTP_HOST_PROD=40546259.servicio-online.net
set FTP_USER_PROD=user-9702349
set FTP_PASS_PROD=TU_PASS_PROD
set FTP_PATH_PROD=/httpdocs

REM Ruta local al dist/ del proyecto
set LOCAL_PATH=C:\Users\Cesar\9-Fisiopilates Atlas\new_fisio\dist

REM Ruta de WinSCP
set WINSCP_PATH=C:\Program Files (x86)\WinSCP\WinSCP.com
