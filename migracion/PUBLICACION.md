# Procedimiento de publicación: nuevo diseño de Los Viajes de Claudia

Es la misma secuencia que se ha probado en local (`claudiaold.local` → `claudia.local`), pero aplicada a producción.

- **Hosting:** SiteGround.
- **Delante:** Cloudflare, que sirve `robots.txt`.
- **Principio:** nada se toca en producción hasta que la copia de *staging* pase todas las comprobaciones.

---

## 0. Antes de empezar

- [ ] Copia de seguridad completa de producción (base de datos + `wp-content`) en SiteGround, y otra descargada.
- [ ] **Congelar la edición:** Claudia no publica ni edita durante la ventana (de 2 a 4 h).
- [ ] Elegir una franja de poco tráfico. Mirar en Analytics el horario de menos visitas; suele ser de madrugada.
- [ ] Tener a mano los accesos: SiteGround, Cloudflare, Search Console, Tag Manager y AdSense.

## 1. Preparar staging con el contenido actual de producción

1. Crear un staging en SiteGround (copia exacta de producción).
2. Exportar desde **producción**:
   - Inventario de URLs: `wp eval-file herramientas/inventario-urls.php inventario-urls.csv`
   - Datos de Elementor de los hubs: `wp eval-file herramientas/exportar-elementor.php el.json`
   - Vistas de Content Views: `wp eval-file herramientas/exportar-content-views.php views.json`
3. Foto SEO de producción, la referencia que hay que igualar:
   `python3 -I herramientas/foto-seo.py inventario-urls.csv snapshot-antes.csv https://losviajesdeclaudia.com https://losviajesdeclaudia.com post,page,category,category-paged,post_tag,post_tag-paged,author`

## 2. Montar el WordPress limpio en staging

1. **WordPress vacío.** Instalar WordPress vacío en la base de datos de staging y cargar la de producción como base auxiliar `oldsrc`.
2. **Importar el contenido.** Ejecutar `importar-contenido.sql`, que trae solo el contenido y mantiene los IDs.
3. **Imágenes.** Copiar `wp-content/uploads` **solo con las carpetas de año** (2013–2026).
4. **Plugins.** Instalar y activar **Yoast SEO** y **Redirection**, y después `wp redirection database install`.
5. **Redirecciones.** Copiar las redirecciones (paso 8 del SQL).
   > ⚠️ **Repetir después el search-replace de dominio en todas las tablas.** Las redirecciones guardan URLs absolutas: en local se quedaron 126 apuntando al dominio antiguo.
6. **Tema.** Copiar el tema `wp-content/themes/claudia` y activarlo: `wp theme activate claudia`.
7. **Menús.** `wp eval-file crear-menus.php`.
8. **Hubs.** Convertirlos a bloques:
   - `python3 -I elementor-a-bloques.py el.json views.json hubs.json inicio-nueva`
   - `wp eval-file aplicar-hubs.php hubs.json losviajesdeclaudia.com`
9. **Enlace de Tailandia.** Corregir a mano el enlace interno que pasa por una redirección de WordPress:
   `wp search-replace '/viajar-a-tailandia-por-libre/' '/tailandia/viajar-a-tailandia-por-libre/' wp_posts --include-columns=post_content`
10. **Archivos de la raíz.** Comprobar que `ads.txt` sigue en la raíz con `google.com, pub-1305382296668483, DIRECT, f08c47fec0942fa0`.
11. **Optimizar las imágenes**, de unos 5,7 GB a unos 1,4 GB (probado en local el 28/10/2026):
    1. **Copia completa** de `wp-content/uploads` y de la base de datos **fuera del servidor** (disco externo o nube). No se sube.
    2. **Simulación**, que no borra nada: `wp eval-file herramientas/limpiar-tamanos.php jpg`
    3. **Aplicar**: `wp eval-file herramientas/limpiar-tamanos.php aplicar jpg`
       - Borra los tamaños del tema anterior (`penci-*`, Instagram, Elementor, Enlazatom): unos 3,6 GB.
       - Borra los JPG/PNG redimensionados que ya tienen `.webp`: unos 0,6 GB.
       - Conserva los originales JPG (los usan Yoast para compartir y Google Imágenes), los tamaños de WordPress con su `.webp` y todo archivo citado en el contenido.
    4. **ALT vacíos**: `wp eval-file herramientas/rellenar-alt.php aplicar alt-aplicados.csv`. Solo rellena los vacíos, con la leyenda, la biblioteca o el título de la imagen.
    5. **Comprobar**:
       - Ninguna imagen del HTML da error (en local: 17.376 de 17.376 correctas).
       - Las direcciones antiguas redirigen. El tema incluye una red de seguridad: una imagen que ya no existe se redirige (301) a su `.webp` o al original.
       - En SiteGround, comprobar que las imágenes inexistentes llegan a WordPress; con las reglas estándar del `.htaccess` lo hacen.
12. **Últimos ajustes.** `wp rewrite flush` y `wp cache flush`.

## 3. Comprobaciones en staging (todas obligatorias)

| Comprobación | Herramienta | Criterio |
|---|---|---|
| URLs y SEO | `foto-seo.py` + `comparar-seo.py` contra `snapshot-antes.csv` | 0 diferencias de código, título, descripción, canonical y robots |
| H1 | la misma foto SEO | Todas las URLs con un único H1 |
| Enlaces internos de los hubs | `curl` sobre los `href` internos | 0 rotos y 0 que pasen por una redirección |
| Bloques | `node herramientas/validar-bloques.js` | Solo los 3 párrafos de Granada (ya conocidos) |
| Peso | `medir-peso.py` | Portada y entradas con 2 peticiones y menos de 35 KB gzip sin imágenes |
| Analítica | Vista previa de Tag Manager | Salta `GTM-MPG35LN` con `pagePostType` y `pageCategory` |
| Anuncios | Abrir una entrada y hacer scroll | 3 anuncios: tras el párrafo 3, tras el 8 y al final |
| Stay22 | Herramientas de red del navegador | Carga `letmeallez.js` tras la primera interacción |
| Búsqueda | `/?s=sevilla` | Responde 200 (sin bucle de Yoast) |
| Móvil | Teléfono real (80 % del tráfico) | Portada, carruseles, menú, entrada, hub y 404 |

## 4. Paso a producción

1. Volver a confirmar que nadie ha publicado nada desde la exportación. Si alguien lo ha hecho, repetir los pasos 1–2 con los datos nuevos.
2. Publicar el staging en producción (SiteGround › Staging › *Push to Live*, despliegue completo).
3. **Caché:**
   - Activar la caché dinámica de SiteGround (o WP Rocket, solo caché de página).
   - No activar «Retrasar JS» ni «combinar CSS/JS»: el tema ya lo resuelve y podrían romper el carrusel o los anuncios.
4. **Cloudflare:** *Purge everything*.
5. Repetir en producción la foto SEO de 50 URLs al azar y de las 20 con más tráfico.

## 5. Primeras 4 semanas

- **Search Console:**
  - Reenviar `sitemap_index.xml`.
  - Revisar a diario *Páginas › No encontradas (404)* y *Experiencia › Core Web Vitals*.
- **Redirection › 404:** revisar a diario la primera semana. Cualquier URL con visitas que dé 404 se redirige.
- **AdSense:** comparar los ingresos por cada 1.000 visitas con las 4 semanas anteriores. Si caen más de un 15 %, valorar cargar los anuncios antes de la primera interacción.
- **Analytics:** comparar las sesiones por día con el mismo periodo del año anterior.

## 6. Vuelta atrás

- La copia del paso 0 permite restaurar la web anterior desde SiteGround en unos 10 minutos.
- Mantener esa copia al menos 30 días.

---

## Pendientes conocidos (no bloquean la publicación)

- **Bloques del editor:**
  - 3 párrafos de la página de Granada salen como «contenido inesperado»; se arreglan con *Intentar recuperar*.
  - 316 bloques de imágenes de entradas antiguas ya estaban así en la web anterior. Solo afectan al editor, no a lo que ven los visitantes.
- **Formulario RGPD:** la página `solicitud-de-acceso-a-datos` usaba un plugin de formularios que ya no existe y el formulario no se muestra. Conviene sustituirlo por un correo de contacto.
- **Banner de cookies:** no se ha encontrado ninguno cargado en la web actual (había restos de iubenda sin usar). Con AdSense en la UE hace falta una CMP certificada por Google. Revisar en AdSense › *Privacidad y mensajes* que el mensaje de consentimiento esté activo.
- **Imágenes destacadas:** la mayoría miden 800 px de ancho. Las de las entradas más visitadas lucirían mejor en las cabeceras con versiones de 1600 px.
