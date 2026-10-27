"""
Convierte las páginas hub de Elementor (+ Content Views) en contenido de bloques nativo.

Entrada:  el.json    -> [{id, slug, data: _elementor_data}]
          views.json -> {view_id: ajustes de Content Views}
Salida:   hubs.json  -> [{id, slug, hub_category, content}]

Reglas:
- El primer widget de título de la página se omite: la plantilla pinta el título de la página como H1.
- text-editor -> párrafos, encabezados y listas como bloques; figuras, imágenes y lo demás como bloque HTML.
- [pt_view id="…"] -> [claudia_guias …] con las categorías, IDs y límite de la vista original.
- Botones -> párrafo con enlace con estilo de botón. Separadores y espaciadores se descartan.

Uso: python3 -I elementor-a-bloques.py el.json views.json hubs.json [slug_a_omitir …]
"""
import html
import json
import re
import sys
from html.parser import HTMLParser

VOID = {"br", "img", "hr", "input", "meta", "link", "source", "wbr"}
BLOCK_TAGS = {"p", "h1", "h2", "h3", "h4", "h5", "h6", "ul", "ol", "figure", "div", "table", "blockquote", "img", "iframe"}


class TopLevel(HTMLParser):
    """Parte un fragmento HTML en sus nodos de primer nivel (etiqueta, html bruto)."""

    def __init__(self, src):
        super().__init__(convert_charrefs=False)
        self.src, self.depth, self.start, self.tag, self.parts, self.cursor = src, 0, None, None, [], 0
        self.lines = [0]
        for line in src.splitlines(keepends=True):
            self.lines.append(self.lines[-1] + len(line))
        self.feed(src)
        self.close()
        tail = src[self.cursor:]
        if tail.strip():
            self.parts.append((None, tail))

    def _abs_pos(self):
        line, col = self.getpos()
        return self.lines[line - 1] + col

    def handle_starttag(self, tag, attrs):
        pos = self._abs_pos()
        if self.depth == 0:
            if self.src[self.cursor:pos].strip():
                self.parts.append((None, self.src[self.cursor:pos]))
            self.start, self.tag = pos, tag
        if tag in VOID:
            if self.depth == 0:
                end = pos + len(self.get_starttag_text())
                self.parts.append((tag, self.src[pos:end]))
                self.cursor = end
            return
        self.depth += 1

    def handle_startendtag(self, tag, attrs):
        pos = self._abs_pos()
        if self.depth == 0:
            end = pos + len(self.get_starttag_text())
            self.parts.append((tag, self.src[pos:end]))
            self.cursor = end

    def handle_endtag(self, tag):
        if tag in VOID:
            return
        self.depth = max(0, self.depth - 1)
        if self.depth == 0 and self.start is not None:
            end = self.src.find(">", self._abs_pos()) + 1
            self.parts.append((self.tag, self.src[self.start:end]))
            self.cursor, self.start = end, None


def inner(tag, raw):
    m = re.match(r"<%s\b[^>]*>(.*)</%s>\s*$" % (tag, tag), raw.strip(), re.S | re.I)
    return m.group(1).strip() if m else raw.strip()


def paragraph(text):
    text = text.strip()
    if not text or re.fullmatch(r"(&nbsp;|\s|<br\s*/?>)*", text):
        return ""
    return f"<!-- wp:paragraph -->\n<p>{text}</p>\n<!-- /wp:paragraph -->"


def heading(text, level):
    if not re.sub(r"<[^>]+>|&nbsp;|\s", "", text):
        return ""  # títulos vacíos de Elementor
    level = max(2, min(int(level), 4))
    attrs = "" if level == 2 else ' {"level":%d}' % level
    return f'<!-- wp:heading{attrs} -->\n<h{level} class="wp-block-heading">{text.strip()}</h{level}>\n<!-- /wp:heading -->'


def html_block(raw):
    return f"<!-- wp:html -->\n{raw.strip()}\n<!-- /wp:html -->"


def list_block(tag, raw):
    items = re.findall(r"<li\b[^>]*>(.*?)</li>", raw, re.S | re.I)
    if not items or re.search(r"<(ul|ol)\b", inner(tag, raw), re.I):
        return html_block(raw)  # listas anidadas: se conservan tal cual
    attrs = ' {"ordered":true}' if tag == "ol" else ""
    lis = "\n".join(f"<!-- wp:list-item -->\n<li>{i.strip()}</li>\n<!-- /wp:list-item -->" for i in items)
    return f'<!-- wp:list{attrs} -->\n<{tag} class="wp-block-list">{lis}</{tag}>\n<!-- /wp:list -->'


def convert_html(src):
    src = re.sub(r"<!--\s*/?wp:[^>]*-->", "", src)  # restos de bloques pegados dentro del editor de Elementor
    # Estilos en línea pegados en Elementor (tamaños, colores, familias): el tema define la tipografía.
    src = re.sub(r'\s(style|data-[\w-]+|contenteditable|role|aria-[\w-]+|tabindex)="[^"]*"', "", src)
    src = re.sub(r'\sclass="(block-editor|rich-text)[^"]*"', "", src)
    src = re.sub(r"<span>(.*?)</span>", r"\1", src, flags=re.S)
    src = re.sub(r"<h[1-6][^>]*>(\s|&nbsp;)*</h[1-6]>", "", src)
    src = re.sub(r"<p>\s*</p>", "", src)
    # Los [caption]…[/caption] se apartan antes de analizar el HTML para que no se partan en trozos.
    captions = []

    def keep_caption(m):
        captions.append(m.group(0).strip())
        return f'<div data-caption="{len(captions) - 1}"></div>'

    src = re.sub(r"(<p>\s*)?\[caption\b.*?\[/caption\](\s*</p>)?", keep_caption, src, flags=re.S)
    out = []
    for tag, raw in TopLevel(src).parts:
        tag = (tag or "").lower()
        placeholder = re.fullmatch(r'<div data-caption="(\d+)"></div>', raw.strip())
        if placeholder:
            out.append(f"<!-- wp:shortcode -->\n{captions[int(placeholder.group(1))]}\n<!-- /wp:shortcode -->")
        elif tag == "p":
            body = inner("p", raw)
            if re.fullmatch(r"(<a\b[^>]*>)?\s*<img\b[^>]*>\s*(</a>)?", body, re.I) or "[caption" in body:
                out.append(html_block(body))
            else:
                out.append(paragraph(body))
        elif re.fullmatch(r"h[1-6]", tag):
            out.append(heading(inner(tag, raw), tag[1]))
        elif tag in ("ul", "ol"):
            out.append(list_block(tag, raw))
        elif tag in BLOCK_TAGS:
            out.append(html_block(raw))
        else:  # texto o etiquetas en línea sueltas
            for chunk in re.split(r"\n\s*\n", raw):
                out.append(paragraph(chunk.replace("\n", " ")))
    # Un [caption] que quedó dentro de otro elemento (HTML mal cerrado) se restaura en su sitio.
    restore = lambda b: re.sub(r'<div data-caption="(\d+)"></div>', lambda m: captions[int(m.group(1))], b)
    return [restore(b) for b in out if b]


def guides_shortcode(view_id, views):
    v = views.get(view_id)
    if not isinstance(v, dict):
        return "", None
    attrs = {"tipo": "page" if v.get("content-type") == "page" else "post"}
    cats = v.get("category-terms") or []
    if cats and attrs["tipo"] == "post":
        attrs["categorias"] = ",".join(cats)
    if v.get("post__in"):
        attrs["ids"] = re.sub(r"\s", "", v["post__in"])
    if v.get("limit") and v["limit"] != "100":
        attrs["limite"] = v["limit"]
    if v.get("view-type") == "scrollable":
        attrs["estilo"] = "carrusel"
    sc = "[claudia_guias " + " ".join(f'{k}="{val}"' for k, val in attrs.items()) + "]"
    return f"<!-- wp:shortcode -->\n{sc}\n<!-- /wp:shortcode -->", (cats[0] if cats and attrs["tipo"] == "post" else None)


def widgets(nodes, acc):
    for e in nodes or []:
        if e.get("elType") == "widget":
            acc.append(e)
        widgets(e.get("elements"), acc)
    return acc


def convert_page(page, views):
    blocks, hub_cat, seen_title = [], None, False
    for w in widgets(page["data"], []):
        s, kind = w.get("settings", {}), w.get("widgetType")
        if kind == "global":  # los widgets globales guardan su copia de ajustes en la instancia
            kind = "heading" if "title" in s else "text-editor" if "editor" in s else "shortcode" if "shortcode" in s else kind
        if kind == "heading":
            if not seen_title and not blocks:
                seen_title = True
                continue
            size = s.get("header_size", "h2")
            blocks.append(heading(s.get("title", ""), size[1] if re.fullmatch(r"h[1-6]", size) else 2))
        elif kind == "text-editor":
            blocks.extend(convert_html(s.get("editor", "")))
        elif kind == "shortcode":
            sc = s.get("shortcode", "")
            ids = re.findall(r'pt_view id="([^"]+)"', sc)
            for vid in ids:
                block, cat = guides_shortcode(vid, views)
                if block:
                    blocks.append(block)
                    hub_cat = hub_cat or cat
            rest = re.sub(r'\[pt_view[^\]]*\]', "", sc).strip()
            if rest:
                blocks.append(f"<!-- wp:shortcode -->\n{rest}\n<!-- /wp:shortcode -->")
        elif kind == "button" and s.get("link", {}).get("url"):
            url = html.escape(s["link"]["url"], quote=True)
            blocks.append(html_block(f'<p class="hub-cta"><a class="button" href="{url}">{s.get("text", "Ver más")}</a></p>'))
    # Bloques repetidos tal cual (p. ej. una misma rejilla pegada dos veces en Elementor): se dejan una vez.
    unique = []
    for b in blocks:
        if b not in unique:
            unique.append(b)
    return {"id": page["id"], "slug": page["slug"], "hub_category": hub_cat, "content": "\n\n".join(unique)}


if __name__ == "__main__":
    pages = json.load(open(sys.argv[1], encoding="utf-8"))
    views = json.load(open(sys.argv[2], encoding="utf-8"))
    skip = set(sys.argv[4:])
    result = [convert_page(p, views) for p in pages if p["slug"] not in skip]
    json.dump(result, open(sys.argv[3], "w", encoding="utf-8"), ensure_ascii=False, indent=1)
    print(len(result), "páginas convertidas;", sum(1 for r in result if r["hub_category"]), "con categoría de hub")
