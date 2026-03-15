# Páginas — Fisiopilates Atlas

8 páginas generadas por Astro como HTML estático.

---

## `/` — Inicio (`index.astro`)

**Meta:** "Centro de Fisioterapia y Pilates Atlas en Carabanchel Alto, Madrid."

### Secciones
1. **Hero** — "Tu salud, en buenas manos"
   - Subtítulo: Centro especializado en Fisioterapia, masajes y rehabilitación en Carabanchel Alto.
   - CTA principal: "Pedir cita" → `/contacto`
   - CTA secundario: "Ver servicios" → `/fisioterapia`
   - **Imagen fondo: `DSC_0040.jpg`**

2. **¿Qué ofrecemos?** — 3 tarjetas enlazadas:
   - Fisioterapia → `/fisioterapia`
   - Pilates → `/pilates`
   - Atención personalizada (mutuas/seguros)

3. **¿Por qué elegirnos?** — 4 características:
   - Fisioterapeutas expertos
   - Grupos reducidos (máx. 5)
   - Instalaciones equipadas
   - Colaboramos con mutuas

4. **Google Maps** — embed:
   ```
   https://maps.google.com/maps?q=Fisiopilates+Atlas+Carabanchel+Alto+Madrid&output=embed&hl=es&z=17
   ```

5. **Nuestro centro** — galería de instalaciones (2 fotos, grid 50/50):
   - `DSC_0009.jpg` — Sala de fisioterapia
   - `DSC_0020.jpg` — Sala de Pilates

6. **CTA final** — "¿Listo para empezar?" → "Pedir cita"

---

## `/fisioterapia` — Fisioterapia (`fisioterapia.astro`)

**Meta:** Servicios de fisioterapia, masajes y rehabilitación.

### Secciones
1. **Hero** — "Fisioterapia profesional y personalizada"
   - Imagen fondo: `portada_v02.jpg`

2. **Artículo principal** — imagen `fisio_masaje.webp` + texto del enfoque personalizado

3. **Nuestros tratamientos** — tarjetas:
   - Fisioterapia manual, Electroterapia, Vendaje neuromuscular, Punción seca, Drenaje linfático, Rehabilitación post-quirúrgica

4. **Patologías frecuentes** — lista con iconos check

5. **Aseguradoras** — Mapfre, Asefa

6. **CTA** → "Pedir cita"

---

## `/pilates` — Pilates (`pilates.astro`)

**Meta:** Clases de Pilates en grupos reducidos con fisioterapeutas.

### Secciones
1. **Hero** — "Pilates terapéutico con fisioterapeutas"
   - Imagen fondo: `pilates_clase.webp`

2. **Clases de Pilates** — descripción: grupos máx. 5, impartido por fisioterapeutas

3. **Tipos de clases:**
   - Pilates suelo, Pilates embarazadas, Pilates terapéutico

4. **Primera clase gratis** — destacado con fondo accent

5. **CTA** → "Reservar primera clase gratuita"

---

## `/precios` — Precios (`precios.astro`)

**Meta:** Tarifas de fisioterapia y Pilates.

### Secciones
1. **Hero** — "Precios y tarifas"
   - **Imagen fondo: `pilates_clase.webp`**

2. **Fisioterapia** — tabla de precios

3. **Pilates** — tabla de precios

4. **Nota:** "Primera clase de Pilates gratuita y sin compromiso"

5. **Aseguradoras** — Mapfre, Asefa

6. **CTA** → "Consultar disponibilidad"

---

## `/contacto` — Contacto (`contacto.astro`)

**Meta:** Formulario de contacto y datos del centro.

### Secciones
1. **Formulario** de contacto:
   - Campos: Nombre, Email, Teléfono (opcional), Motivo (select), Mensaje
   - hCaptcha → Submit → `POST /api/contacto.php`
   - En TEST: email a `cppleal@gmail.com` con prefijo `[TEST]`
   - En PROD: email a `fisiopilates.atlas@gmail.com`

2. **Info de contacto:**
   - Tel: 691 487 526
   - Email: fisiopilates.atlas@gmail.com
   - Dirección: c/Travesía de Alfredo Aleix, 1, Carabanchel Alto, 28044 Madrid
   - Horario: Lunes-Viernes 10:00-21:00
   - Transporte: Metro La Peseta | Bus 35 · 47

3. **Mapa** Google Maps embed

---

## `/privacidad` — Política de Privacidad (`privacidad.astro`)

Contenido RGPD estático: responsable del tratamiento, datos recogidos, base jurídica, derechos del usuario.

---

## `/cookies` — Política de Cookies (`cookies.astro`)

Tipos de cookies utilizadas (necesarias, analíticas, funcionales), tabla de cookies, gestión del consentimiento.

---

## `/404` — Página de error (`404.astro`)

- Texto: "Página no encontrada"
- Botón: "Volver al inicio"
- Configurado en `.htaccess`: `ErrorDocument 404 /404.html`

---

## Imágenes utilizadas

| Archivo | Uso |
|---------|-----|
| `DSC_0040.jpg` | Hero inicio (home) |
| `pilates_clase.webp` | Hero pilates + Hero precios |
| `portada_v02.jpg` | Hero fisioterapia |
| `fisio_masaje.webp` | Artículo fisioterapia |
| `DSC_0009.jpg` | Galería "Nuestro Centro" — sala fisioterapia |
| `DSC_0020.jpg` | Galería "Nuestro Centro" — sala Pilates |
| `atlas_embarazada_02.jpg` | Pilates embarazadas |
| `precios_pilates_02.jpg` | Disponible (no en uso activo) |
| `logo.jpg` | Logo cabecera (mix-blend-mode screen) |
