# v1.1.0 — sistema_versionado
**Fecha:** 2026-03-07

## Resumen
Incorporación del sistema de versionado completo: backup de base de datos,
repositorio GitHub privado, script automatizado de creación de versión y
procedimiento documentado (incluyendo revisión de specs) en CLAUDE.md.

## Cambios incluidos

### 1. Sistema de backup de base de datos
- `backup/backup-database.php` — Script PHP que genera volcados SQL (estructura + datos)
  para los entornos TEST y PROD. Genera `estructura.sql` y `completo.sql` por versión.
- `backup/backup.bat` — Wrapper Windows para ejecutar el backup desde línea de comandos.

### 2. Script automatizado de versión
- `crear-version.bat` — Script que guía paso a paso la creación de versión:
  backup BD → revisión de specs (abre carpeta specs/) → generación de changelog
  (plantilla + Notepad) → actualización de VERSION → git commit + push.

### 3. Repositorio GitHub
- `.gitignore` — Excluye node_modules/, dist/, .env, deploy-config.bat,
  php/config.php y las carpetas de backup (backup/test/, backup/prod/).
- `php/config.template.php` — Plantilla de configuración PHP sin credenciales reales,
  para que el fichero de referencia esté en el repositorio.
- Repositorio creado en GitHub: `cppleal/fisiopilates-atlas` (privado).
- Commit inicial `v1.0.0-primera_version_completa` con todos los ficheros del proyecto.

### 4. Especificaciones actualizadas
- `specs/deploy.md` — Añadidas secciones: backup de BD, repositorio GitHub
  (ficheros excluidos, plantillas), procedimiento de creación de versión
  y tabla de historial de versiones.

### 5. Documentación del procedimiento
- `CLAUDE.md` — Sección de versionado ampliada con:
  - Repositorio GitHub y convención de commits.
  - Procedimiento de creación de versión (script y manual).
  - Tabla de specs con criterio de cuándo actualizar cada una.
  - Formato de changelog.md.
- `versiones/README.md` — Índice de versiones del proyecto.

## Notas de actualización
- Los backups de BD no se suben a git (están en `.gitignore`).
- `php/config.php` tampoco va en git — usar `php/config.template.php` como referencia.
- Credenciales de BD de PROD pendientes de completar en `backup/backup-database.php`.
