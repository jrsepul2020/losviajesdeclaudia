import re,sys,gzip,ssl,urllib.request
ctx=ssl._create_unverified_context()
def get(u):
    r=urllib.request.urlopen(urllib.request.Request(u,headers={'User-Agent':'medidor'}),context=ctx,timeout=120); return r.read()
for base in ('http://claudiaold.local','http://claudia.local'):
    for path in ('/','/chipre/que-ver-en-chipre-en-10-dias-ruta-en-coche-por-la-isla/'):
        html=get(base+path); t=html.decode('utf-8','replace')
        css=re.findall(r'<link[^>]+rel=["\']stylesheet["\'][^>]*href=["\']([^"\']+)',t)+re.findall(r'<link[^>]+href=["\']([^"\']+)["\'][^>]*rel=["\']stylesheet',t)
        js=re.findall(r'<script[^>]+src=["\']([^"\']+)',t)
        tot=len(gzip.compress(html)); n=1
        for u in set(css+js):
            if u.startswith('//'): u='http:'+u
            if not u.startswith('http'): u=base+u
            try: tot+=len(gzip.compress(get(u))); n+=1
            except Exception: pass
        print(f"{base:26} {path[:22]:24} HTML {len(html)//1024:4}KB | CSS {len(set(css)):2} | JS {len(set(js)):2} | peticiones {n:2} | total gzip (sin imágenes) {tot//1024} KB")
