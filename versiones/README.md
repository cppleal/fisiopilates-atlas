# Versiones — Fisiopilates Atlas

Historial de versiones del proyecto. Cada versión tiene su propia carpeta con:
- `changelog.md`: cambios incluidos, ficheros modificados, notas de actualización
- Opcionalmente, backups de BD en `backup/test/vX.Y.Z/` y `backup/prod/vX.Y.Z/`

## Historial

| Versión | Fecha | Descripción |
|---------|-------|-------------|
| [v0.1.0](v0.1.0-scaffolding_inicial/) | 2026-02 | Scaffolding inicial del proyecto |
| [v1.0.0](v1.0.0-primera_version_completa/) | 2026-03-06 | Primera versión completa: 8 páginas, contacto, admin, cookies RGPD |
| [v1.1.0](v1.1.0-sistema_versionado/) | 2026-03-07 | Sistema de backup BD, repositorio GitHub y procedimiento de versionado |

## Formato de versiones

```
vX.Y.Z-descripcion_breve
```

- **X** = Mayor (rediseño completo, cambio de arquitectura)
- **Y** = Menor (nueva página, nueva funcionalidad significativa)
- **Z** = Parche (correcciones, ajustes menores)
