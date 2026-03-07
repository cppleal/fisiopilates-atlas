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
   - Imagen fondo: `pilates_clase.webp`

2. **¿Qué ofrecemos?** — 3 tarjetas enlazadas:
   - Fisioterapia → `/fisioterapia`
   - Pilates → `/pilates`
   - Atención personalizada (mutuas/seguros, sin enlace)

3. **¿Por qué elegirnos?** — 4 características:
   - Fisioterapeutas expertos
   - Grupos reducidos (máx. 5)
   - Horario amplio (L-V 10:00-21:00)
   - Seguro y aseguradoras

4. **Google Maps** — embed business name:
   ```
   https://maps.google.com/maps?q=Fisiopilates+Atlas+Carabanchel+Alto+Madrid&output=embed&hl=es&z=17
   ```
   - Datos visibles: teléfono, dirección, transporte (Metro La Peseta · Bus 35/47)

5. **CTA final** — "¿Listo para sentirte mejor?" → botón "Pedir cita"

---

## `/fisioterapia` — Fisioterapia (`fisioterapia.astro`)

**Meta:** Servicios de fisioterapia, masajes y rehabilitación.

### Secciones
1. **Hero** — "Fisioterapia profesional y personalizada"
   - Imagen fondo: `portada_v02.jpg`

2. **Artículo principal** — "Centro especializado en Fisioterapia y rehabilitación"
   - Imagen: `fisio_masaje.webp` (comprimida de 7.6MB PNG → 121KB WebP)
   - Texto sobre el enfoque personalizado del centro

3. **Nuestros tratamientos** — grid de tarjetas:
   - Fisioterapia manual
   - Electroterapia / ultrasonidos
   - Vendaje neuromuscular (kinesiotaping)
   - Punción seca
   - Drenaje linfático manual
   - Rehabilitación post-quirúrgica

4. **Patologías frecuentes** — lista con iconos check:
   - Lumbalgias, cervicalgias, hernias discales
   - Lesiones deportivas, esguinces, tendinitis
   - Artrosis, fibromialgia, contracturas
   - Post-operatorios (cadera, rodilla, hombro)

5. **Aseguradoras** — Mapfre, Asefa (colaboración con mutuas)

6. **CTA** → "Pedir cita"

---

## `/pilates` — Pilates (`pilates.astro`)

**Meta:** Clases de Pilates en grupos reducidos con fisioterapeutas.

### Secciones
1. **Hero** — "Pilates terapéutico con fisioterapeutas"
   - Imagen fondo: `pilates_clase.webp`

2. **Clases de Pilates** — descripción del método
   - Grupos reducidos (máx. 5 personas)
   - Impartido por fisioterapeutas
   - Adaptado a cada nivel

3. **Tipos de clases**:
   - Pilates suelo — principiantes a avanzado
   - Pilates embarazadas — desde 2º trimestre
   - Pilates terapéutico — lesiones y recuperación

4. **Primera clase gratis** — destacado con fondo accent

5. **CTA** → "Reservar primera clase gratuita"

---

## `/precios` — Precios (`precios.astro`)

**Meta:** Tarifas de fisioterapia y Pilates.

### Secciones
1. **Fisioterapia** — tabla de precios:
   - Sesión individual: precio real del centro
   - Bonos (5, 10 sesiones) con descuento

2. **Pilates** — tabla de precios:
   - Clase suelta
   - Mensualidad (1 día/sem, 2 días/sem)
   - Bono 10 clases

3. **Nota**: "Primera clase de Pilates gratuita y sin compromiso"

4. **Aseguradoras** — Mapfre, Asefa

5. **CTA** → "Consultar disponibilidad"

---

## `/contacto` — Contacto (`contacto.astro`)

**Meta:** Formulario de contacto y datos del centro.

### Secciones
1. **Formulario** de contacto:
   - Campos: Nombre, Email, Teléfono (opcional), Motivo (select), Mensaje
   - hCaptcha (site key: `1d9426de-c448-442c-bc7e-4fe2f87c4ea2`)
   - Submit → `POST /api/contacto.php`
   - En TEST: email a `cppleal@gmail.com` con prefijo `[TEST]`
   - En PROD: email a `fisiopilates.atlas@gmail.com`

2. **Info de contacto**:
   - Tel: 691 487 526
   - Email: fisiopilates.atlas@gmail.com
   - Dirección: c/Travesía de Alfredo Aleix, 1 (local), Carabanchel Alto, 28044 Madrid
   - Horario: Lunes-Viernes 10:00-21:00
   - Transporte: Metro La Peseta · Carabanchel Alto | Bus 35 · 47

3. **Mapa** Google Maps embed (igual que index)

---

## `/privacidad` — Política de Privacidad (`privacidad.astro`)

**Meta:** Política de privacidad y protección de datos RGPD.

### Contenido
- Responsable del tratamiento
- Datos recogidos y finalidad
- Base jurídica del tratamiento
- Conservación de datos
- Derechos del usuario (ARCO + portabilidad + olvido)
- Contacto DPD: fisiopilates.atlas@gmail.com

---

## `/cookies` — Política de Cookies (`cookies.astro`)

**Meta:** Política de cookies LSSI-CE.

### Contenido
- Qué son las cookies
- Tipos utilizadas:
  - **Necesarias**: sesión, CSRF
  - **Analíticas**: comportamiento de visita (si se aceptan)
  - **Funcionales**: preferencias de usuario (si se aceptan)
- Tabla de cookies con nombre, tipo, duración y propósito
- Gestión del consentimiento (enlace para abrir panel de preferencias)
- Terceros (hCaptcha)

---

## `/404` — Página de error (`404.astro`)

- Texto: "Página no encontrada"
- Botón: "Volver al inicio"
- Configurado en `.htaccess`: `ErrorDocument 404 /404.html`

---

## Imágenes utilizadas

| Archivo | Uso | Tamaño |
|---------|-----|--------|
| `pilates_clase.webp` | Hero index + hero pilates | — |
| `portada_v02.jpg` | Hero fisioterapia | — |
| `fisio_masaje.webp` | Artículo fisioterapia | 121 KB |
| `precios_pilates_02.jpg` | Sección precios | — |
| `atlas_embarazada_02.jpg` | Pilates embarazadas | — |
| `fisio_atlas_02.jpg` | Sección "por qué elegirnos" | — |
| `DSC_0052-1-2_v02.jpg` | Galería / uso diverso | — |
