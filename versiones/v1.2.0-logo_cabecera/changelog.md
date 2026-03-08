# v1.2.0 — logo_cabecera
**Fecha:** 2026-03-08

## Resumen
Incorporación del logo real del centro en la cabecera, sustituyendo el
placeholder SVG. Se usa el logo horizontal con globo terráqueo (portada_v02.jpg)
con mix-blend-mode screen para fundir el fondo negro sobre el teal de la cabecera,
y bordes redondeados como máscara.

## Cambios incluidos

### 1. Frontend
- `src/components/Header.astro` — Logo placeholder SVG + texto sustituido por
  imagen real `portada_v02.jpg`. Aplicado `mix-blend-mode: screen` para eliminar
  fondo negro del JPG y `rounded-lg` para suavizar esquinas.
- `public/images/logo.jpg` — Añadido logo alternativo (versión tipográfica limpia),
  aunque finalmente se usa portada_v02.jpg.

### 2. Especificaciones actualizadas
- `specs/deploy.md` — Historial de versiones actualizado con v1.2.0.

## Notas de actualización
- Sin cambios en BD ni en backend PHP.
