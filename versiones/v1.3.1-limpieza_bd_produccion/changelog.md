# v1.3.1 — limpieza_bd_produccion
**Fecha:** 2026-03-15

## Resumen
Limpieza de la base de datos de producción: eliminación de las 106 tablas del Joomla legacy que permanecían tras el deploy de la web nueva. La BD queda exclusivamente con las tablas de la nueva aplicación.

---

## Cambios incluidos

### 1. Base de datos PROD — Eliminación tablas Joomla

Eliminadas las 106 tablas del Joomla legacy (`j2n4k_*` y `bf_*`) de la BD `9702349_fisio`.

**Tablas eliminadas (106):**
- 4 tablas `bf_*`: activitylog, core_hashes, files, files_last, folders, folders_to_scan
- 102 tablas `j2n4k_*`: action_log_config, action_logs, akeeba_common, akeebabackup_*, assets, associations, b2jcontact_*, banner_*, categories, contact_*, content_*, contentitem_*, core_log_searches, creative_*, extensions, fields_*, finder_*, guidedtour_*, history, languages, mail_templates, menu_*, messages_*, modules_*, newsfeeds, overrider, plg_system_*, postinstall_messages, privacy_*, redirect_links, scheduler_tasks, schemas, session, slideshowck_styles, spmedia, sppagebuilder_*, tags, template_*, ucm_*, update_sites_*, updates, user_*, usergroups, users, viewlevels, webauthn_credentials, weblinks, wf_profiles, workflow_*

**Estado de la BD PROD tras la limpieza:**
| Tabla | Descripción |
|-------|-------------|
| `admins` | Usuarios del panel de administración |
| `admin_ips` | IPs autorizadas para el panel admin |
| `contacto` | Mensajes del formulario de contacto |
| `cookie_consent_logs` | Registros de consentimiento RGPD |

---

## Backups previos a los cambios
- `backup/prod/backup_prod_2026-03-15_221417.sql` (3 KB — BD limpia, solo tablas nuevas)
- `backup/test/backup_test_2026-03-15_221417.sql` (7.3 KB)

> El backup completo del Joomla (24 MB con todas las tablas) está en `backup/prod/backup_prod_2026-03-15_211535.sql`, generado antes del deploy inicial.

---

## Notas de actualización
- No hay cambios en código ni en ficheros desplegados.
- No es necesario re-deploy.
