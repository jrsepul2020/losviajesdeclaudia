# Los Viajes de Claudia — rediseño

Rediseño ligero de [losviajesdeclaudia.com](https://losviajesdeclaudia.com): WordPress con un tema propio, sin constructores visuales ni temas comerciales.

## Qué hay aquí

| Carpeta / archivo | Contenido |
|---|---|
| `wp-content/themes/claudia/` | Tema a medida: CSS en línea, sin jQuery y con fuentes propias. |
| `migracion/` | Scripts de importación limpia del contenido, conversión de Elementor a bloques, inventario de URL y comprobaciones SEO. |
| `migracion/PUBLICACION.md` | Procedimiento paso a paso para publicar en producción. |
| `PLAN-REDISENO.md` | Plan general, decisiones y estado de cada fase. |

## Qué no hay (a propósito)

- El núcleo de WordPress.
- `wp-config.php`.
- La carpeta `uploads` (unos 5,5 GB de imágenes).
- Las copias de la base de datos.

## Requisitos del tema

- WordPress 6.4 o superior y PHP 8.1 o superior.
- Plugins: **Yoast SEO**, del que dependen la categoría principal y los permalinks, y **Redirection**.
