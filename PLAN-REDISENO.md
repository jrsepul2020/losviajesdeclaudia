# Plan de rediseño y migración: Los Viajes de Claudia

Fecha: 27/10/2026 · Origen: `claudiaold.local` (copia exacta de losviajesdeclaudia.com) · Destino: `claudia.local`

---

## 0. Lo que hay ahora (auditoría inicial)

| Elemento | Dato |
|---|---|
| Entradas | **649** (bloques de Gutenberg limpios: párrafos, imágenes, H2/H3, listas, tablas; sin Elementor) |
| Páginas | **106**, casi todas *hubs* de país/ciudad (`/chipre/`, `/italia/roma/`, `/viajar-a-egipto/`…) hechas con **Elementor + Penci + Content Views** |
| Categorías | **186**, jerárquicas (país › región/ciudad › subtema, p. ej. `/category/espana/sevilla/donde-comer/`) |
| Etiquetas | **1.672** |
| Medios | **6.937** adjuntos · `uploads` = **6,4 GB** (incluye carpetas residuales: `backup`, `js_composer`, `wpallexport`, `wp-file-manager-pro`, `wpaie_files`…) |
| Permalinks | **`/%category%/%postname%/`** → p. ej. `/italia/sicilia/que-ver-en-cefalu/` |
| Tema | Soledad (muy pesado) + Elementor + Elementor Pro |
| Plugins | Yoast SEO, Redirection, WP Rocket, Perfmatters, Imagify, Adsensei, GTM4WP, Insert Headers & Footers, Table of Contents Plus, Content Views (+Pro), Penci Review/Shortcodes, Enlazatom, Classic Editor, Public Post Preview, Regenerate Thumbnails |

**Conclusión:** el contenido de las entradas ya es limpio y portable. Lo pesado es la capa de presentación (Soledad + Elementor + Penci + Content Views), y está concentrada en la portada y en las ~106 páginas hub. Eso permite un rediseño muy ligero sin tocar las URLs.

---

## 1. Decisión de arquitectura

**WordPress + tema propio programado a medida (“claudia”), sin constructor visual.**

- **Por qué no un sitio estático/headless:** con 649 entradas posicionadas, el riesgo de romper URLs, metadatos de Yoast, comentarios y el flujo editorial de Claudia no compensa. WordPress bien servido con caché de página completa es igual de rápido para el usuario.
- **Por qué no otro tema comercial:** incluso los temas “ligeros” traen CSS y JS que no se usan. Un tema propio pesa solo lo que necesita.
- **Tipo de tema:** tema **clásico PHP híbrido** (plantillas PHP + `theme.json` para que el editor de bloques muestre los mismos estilos). Se programa con plantillas PHP para controlar al byte el HTML que sale, sin las capas extra de Full Site Editing.

### Presupuesto de rendimiento (objetivos que no se pueden saltar)

| Métrica | Objetivo (móvil, 4G) |
|---|---|
| LCP | < 1,8 s |
| CLS | 0 |
| INP | < 100 ms |
| CSS total | < 25 KB comprimido, crítico *inline* |
| JS propio | < 5 KB (menú móvil + índice); **sin jQuery** en el front |
| Fuentes | 2 familias como máximo, `woff2` alojadas en el propio servidor, `font-display: swap` y precarga de la principal |
| Peso de una entrada sin anuncios | < 350 KB en la carga inicial |
| Peticiones | < 20 sin contar anuncios |

---

## 2. Estrategia de migración: WordPress limpio + importación solo del contenido ✅ (hecho el 27/10/2026)

WordPress nuevo y vacío, **sin Soledad, Elementor ni plugins antiguos**. Solo se trae el contenido, con sus mismos IDs, mediante SQL directo (`migracion/importar-contenido.sql`). No se usa el importador WXR porque cambia los IDs y pierde metadatos.

| Se importa | Se deja fuera |
|---|---|
| 649 entradas, 106 páginas y 38 borradores o privadas | Revisiones, menús, plantillas de Elementor, vistas de Content Views, WPCode y estilos globales |
| 6.937 adjuntos con su metadata, textos alternativos y `_thumbnail_id` | Metadatos de Penci, Elementor, Imagify, SiteGround, Enlazatom, WPBakery… |
| Metadatos de Yoast (título, descripción, categoría principal, canonical, noindex, OG), `_wp_old_slug` y `_wp_old_date` | Opciones y tablas de los plugins eliminados |
| 186 categorías, 1.672 etiquetas y sus relaciones | — |
| 9.167 comentarios | — |
| 5 usuarios con su ID (autoría) | Tokens y preferencias del panel |
| Ajustes de lectura, permalinks, comentarios y medios; ajustes de Yoast y 154 redirecciones | — |
| Visitas (`penci_post_views_count` → `_lvc_views`) para mostrar "populares" | — |

- **Plugins instalados:** solo **Yoast SEO** y **Redirection**.
- **Uploads:** se han copiado solo las carpetas por año (2013–2026, 5,5 GB). La basura (`backup`, `js_composer`, `wpallexport`, `elementor`…) se ha quedado fuera.
- **Verificación:** las **9.628 URLs** (entradas, páginas, categorías, etiquetas, paginaciones, autores y adjuntos) son **idénticas** a las del sitio original. Las 946 URLs principales responden 200, salvo las 12 redirecciones 301 que ya existían en el sitio original.

---

## 3. Blindaje SEO (la parte crítica)

### 3.1 Inventario de URLs “antes”
Generar un CSV maestro con **todas** las URLs indexables de la web actual:
- Entradas, páginas, categorías (+ paginación `/page/N/`), etiquetas, autor, feeds y `sitemap_index.xml` de Yoast.
- Todas las redirecciones existentes del plugin Redirection (exportar a CSV).
- Top 500 URLs por clics de **Google Search Console** (últimos 16 meses) y top URLs con **backlinks**.
- Por cada URL guardar: código HTTP, `<title>`, meta description, canonical, H1, `robots`, número de enlaces internos y hreflang si lo hubiera.

### 3.2 Riesgos detectados y cómo se resuelven

| Riesgo | Solución |
|---|---|
| **El permalink depende de la categoría principal de Yoast.** Si se quita Yoast, WordPress usa la categoría con ID más bajo y **cambian cientos de URLs** | Se mantiene **Yoast SEO** (o, si se cambia de plugin SEO, el tema replica el filtro `post_link_category` leyendo `_yoast_wpseo_primary_category`). Verificación automática URL a URL. |
| Páginas hub con el mismo slug que categorías (`/chipre/` página vs `/category/chipre/`) y páginas hijas que comparten ruta con entradas (`/italia/sicilia/`) | Hoy ya funciona y no se toca la estructura de permalinks. Se comprueba cada colisión en el test de regresión. |
| Hubs hechos con Elementor: al desactivarlo, su contenido queda vacío o roto | Se reconstruyen como **plantilla “Hub de destino”** (ver 4.3) conservando slug, padre, título, meta de Yoast e ID. |
| Shortcodes huérfanos (`[penci…]`, `[pt_view…]`, `[toc]`, `[adsensei…]`, Enlazatom) | Búsqueda en la base de datos antes de desactivar; los que existan se sustituyen por bloques nativos o se registran como shortcodes compatibles en el tema (*shims* mínimos) para que nunca aparezca texto `[...]` en pantalla. |
| Tamaños de imagen de Soledad referenciados en el contenido | No se borran. Se registran los tamaños nuevos y se regeneran solo esos (WP-CLI `media regenerate --only-missing`). |
| Enlaces internos con dominio antiguo, `http://` o URLs con redirección | Escaneo de todos los `href` internos del contenido; se corrigen directamente al destino final (menos saltos 301). |
| Datos estructurados | Yoast sigue generando Article/Breadcrumb/Organization. El tema añade migas de pan visibles coherentes con el schema. |
| Anuncios (Adsensei) = ingresos | Se sustituye por inserción de huecos de anuncio desde el propio tema (tras el párrafo N, antes del H2 N), con altura reservada (CLS 0) y carga diferida. Mismo ID de editor y mismos `ads.txt`. |

### 3.3 Test de regresión (puerta de salida)
Script que recorre el CSV maestro contra `claudia.local` y compara:
- **0 URLs** con 404 o 5xx, y **ningún 301 nuevo** no previsto.
- Mismo `title`, meta description, canonical y `robots` (diferencia = 0).
- H1 presente y único.
- Enlaces internos rotos = 0 (rastreo completo con Screaming Frog o script propio).
- Sitemap: mismo número de URLs por tipo.

**No se publica nada hasta que este informe salga limpio.**

---

## 4. Diseño (patrón de viviendodeviaje.com, identidad de Claudia)

Lo que se toma de la referencia: cabecera transparente sobre una foto a pantalla completa, logo centrado con el menú repartido a ambos lados, tarjetas de foto grandes con esquinas redondeadas y título en mayúsculas sobre la imagen, carruseles temáticos horizontales, bloque “quién soy”, rejilla de últimos artículos y tarjetas de afiliados (seguro, tarjeta, eSIM) muy visibles.
Lo que **no** se copia: logotipo, colores, textos ni fotos. La paleta y la tipografía se definen para Claudia.

### 4.1 Sistema de diseño
- Tokens en `theme.json` + variables CSS: 1 color de marca, 1 de acento y neutros cálidos; escala tipográfica fluida (`clamp()`); espaciados de 4/8 px.
- Tipografía: una sans para el texto (estilo Montserrat/Lato, como la referencia) y, opcionalmente, una display para titulares. Solo los pesos que se usen.
- Componentes: tarjeta de foto (con título superpuesto), tarjeta de artículo, carrusel nativo con `scroll-snap` (**0 JS**), caja de afiliado, caja de consejo/aviso, índice de contenidos, caja de autora y migas de pan.

### 4.2 Plantillas
1. **Portada**: héroe con foto y lema → tarjetas de afiliados → carrusel temático (p. ej. “Navidad”, configurable) → “Guías de viaje” (rejilla de destinos que enlaza a los hubs) → sobre Claudia → últimos artículos → bloque de Instagram (imágenes estáticas, sin widget de terceros).
2. **Entrada**: imagen destacada en el héroe (LCP precargado, `fetchpriority="high"`), migas, fecha de actualización, índice generado por el tema (sustituye a TOC+), imágenes anchas, huecos de anuncio reservados, caja de autora y relacionados de la misma categoría (consulta cacheada en *transient*).
3. **Hub de destino** (sustituye a las 106 páginas Elementor): intro editable con bloques + rejilla automática de entradas de la categoría vinculada (campo en la página) + subdestinos + bloque de “planifica tu viaje”. Al publicar una entrada nueva, el hub se actualiza solo.
4. **Categoría / etiqueta / autor / búsqueda / 404**: rejilla de tarjetas con paginación numérica; la 404 incluye un buscador y destinos populares.
5. **Menú “Destinos”**: mega menú por continentes en HTML/CSS (`<details>` en móvil), sin JS pesado.

### 4.2c Portada v2, anchos y preparación para publicar ✅ (hecho el 27/10/2026)

**Portada (móvil primero)**
- Foto en tarjeta redondeada con el H1 de siempre, buscador visible y accesos rápidos a destinos.
- Carruseles «Viajes de Navidad» y «Destinos favoritos»: tarjetas tipo Apple con la parte baja oscurecida y texto blanco. Flechas en ordenador, sin barra de scroll; en móvil se desliza y lleva puntos.
- Banners a sangre y en pareja, alternando con secciones de ancho normal.
- Texto SEO al final, con enlaces a los continentes.

**Anchos y diseño**
- Ancho estándar de 1400 px. Entradas con 880 px de contenido y 340 px de barra lateral.
- En los hubs, textos e imágenes en una misma columna de 820 px.

**Rendimiento y monetización**
- Se sirven los WebP que ya existían (un 26 % menos de peso). Las tarjetas se limitan a 800 px.
- GTM, los 3 anuncios de AdSense y Stay22 replicados con la misma configuración de la web anterior.
- `ads.txt` en la raíz.

**Otros**
- Menús creados en Apariencia › Menús.
- Procedimiento de publicación en `migracion/PUBLICACION.md`.

### 4.2b Plantillas de archivo, búsqueda y 404 ✅ (hecho el 27/10/2026)
- **Categorías:** foto de cabecera, píldoras de subdestinos, botón “Guía completa” hacia su hub y la primera guía destacada.
- **Etiquetas:** cabecera sencilla. La paginación sigue con 10 por página, así que las URL `/page/N/` son las mismas.
- **Búsqueda:** buscador grande y sugerencias cuando no hay resultados.
- **404:** devuelve el código 404 y ofrece buscador, destinos con más guías y últimas guías.
- **Arreglo:** Yoast entraba en bucle de redirecciones con `?s=` en local. Se añade `s` a sus parámetros permitidos.
- **SEO:** comparación de las 2.691 URL contra la web original con 1 sola diferencia (`/espana/` gana su H1). Todas tienen un único H1.

### 4.3 Reconstrucción de los hubs ✅ (hecho el 27/10/2026)

**Resultado:** 98 páginas Elementor convertidas a bloques nativos con `migracion/elementor-a-bloques.py` y aplicadas con `migracion/aplicar-hubs.php`.

**Conversión del contenido**
- Las rejillas de Content Views pasan al shortcode del tema `[claudia_guias]`, con las mismas categorías, IDs y límites que tenían.
- 92 hubs quedan vinculados a su categoría (`_claudia_hub_category`). Esa categoría da la foto de cabecera y el contador de guías.
- No se pierde ni una palabra (comprobado página a página).
- Limpieza: fuera estilos en línea, títulos vacíos y una rejilla duplicada en España.

**Comprobaciones**
- Las 755 entradas y páginas mantienen código HTTP, título, descripción, canonical y robots, con un único H1.
- Enlaces internos de los hubs: 0 rotos y 0 redirecciones encadenadas.

**Pendiente:** la portada antigua (`inicio-nueva`) no se ha convertido, porque la portada usa su propia plantilla `front-page.php`.

#### Plan original
- Plantilla única + campo “categoría vinculada”; el texto intro de cada hub se extrae del HTML actual de Elementor de forma semiautomática y se revisa a mano.
- Mismo ID, slug, padre, título SEO y description. Primero los 20 hubs con más tráfico (según Search Console).

---

## 5. Pila de plugins final (de ~20 a 4–5)

| Se queda | Motivo |
|---|---|
| **Yoast SEO** | Metadatos, categoría principal (permalinks), sitemaps, schema. Cambiarlo es riesgo sin beneficio. |
| **Redirection** | Conserva todas las redirecciones históricas. |
| **Caché**: WP Rocket **o** la caché del servidor (SiteGround SuperCacher/NGINX) | Caché de página completa. Con el tema ligero, sin minificación agresiva ni “delay JS”, que dan problemas. |
| **Imagify** (o conversión en servidor) | WebP/AVIF. |

| Se elimina | Sustituto |
|---|---|
| Soledad, Elementor, Elementor Pro, Penci Review, Penci Shortcodes, Content Views (+Pro) | Tema propio |
| Table of Contents Plus | Índice generado en el tema (PHP, sin JS) |
| Adsensei | Inserción de anuncios en el tema |
| GTM4WP, Insert Headers & Footers | Fragmentos en `functions.php`/ajustes del tema |
| Perfmatters | Innecesario: el tema no carga nada que haya que desactivar |
| Classic Editor | Las entradas ya están en bloques |
| Regenerate Thumbnails | WP-CLI |
| Enlazatom, Public Post Preview | Revisar con Claudia si los sigue usando |

Además: limpiar `uploads` de carpetas residuales (`backup`, `js_composer`, `wpallexport`, `wp-file-manager-pro`, `wpaie_files`, `ai1wm-backups`), opciones *autoload* huérfanas de los plugins eliminados, revisiones antiguas y *transients*. Esto se hace **después** de confirmar que nada las referencia.

---

## 6. Fases y calendario orientativo

| Fase | Contenido | Estimación |
|---|---|---|
| **1. Inventario SEO** | CSV maestro, exportar Redirection, GSC y backlinks, escaneo de shortcodes y tamaños de imagen | 1–2 días |
| **2. Clonado** | BD + uploads → `claudia.local`, search-replace y comprobación de que todo funciona igual con el tema antiguo | 0,5 días |
| **3. Diseño** | Maqueta de portada, entrada y hub (HTML estático) y aprobación de Claudia | 3–4 días |
| **4. Tema** | Plantillas, `theme.json`, componentes, índice, anuncios, mega menú, accesibilidad (AA) | 5–7 días |
| **5. Hubs** | Plantilla hub y migración de las 106 páginas | 3–4 días |
| **6. Limpieza** | Desactivar y eliminar plugins, limpiar BD y uploads | 1 día |
| **7. QA** | Test de regresión SEO, Lighthouse/PageSpeed de 20 URLs tipo, prueba en móviles reales, comprobación de anuncios y analítica | 2 días |
| **8. Publicación** | Ventana de poco tráfico, copia completa previa, subir el tema y la BD limpia (o aplicar los cambios sobre producción), vaciar cachés y CDN, enviar el sitemap | 0,5 días |
| **9. Vigilancia** | 4 semanas: cobertura y 404 en GSC, Core Web Vitals, posiciones top 100, ingresos de anuncios | Continuo |

**Plan de vuelta atrás:** copia completa de producción + Soledad sin borrar del servidor durante 30 días; volver = reactivar tema y plugins (unos 5 minutos).

---

## 7. Decisiones pendientes de Claudia

1. ¿Paleta y logo actuales o renovación de marca?
2. ¿Qué afiliados destacar en la portada (seguro, eSIM, tarjeta, reservas)?
3. ¿Se mantiene la red de anuncios actual y su densidad?
4. ¿Hosting de producción (SiteGround, según el log de migración)? Eso decide WP Rocket o la caché del servidor.
5. ¿Se usan aún Enlazatom y Public Post Preview?
6. Las 1.672 etiquetas: **no tocar en esta fase** (cambiar la indexación es otra decisión SEO, posterior y medible).
