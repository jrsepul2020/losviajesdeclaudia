import csv,sys,re,html,ssl,urllib.request,concurrent.futures as cf
src,dst,host_from,host_to=sys.argv[1:5]; types=set(sys.argv[5].split(','))
ctx=ssl._create_unverified_context()
class NoRedir(urllib.request.HTTPRedirectHandler):
    def redirect_request(self,*a,**k): return None
op=urllib.request.build_opener(NoRedir,urllib.request.HTTPSHandler(context=ctx))
def g(rx,s):
    m=re.search(rx,s,re.I|re.S); return html.unescape(re.sub(r'\s+',' ',m.group(1))).strip() if m else ''
def fetch(row):
    url=row['url'].replace(host_from,host_to)
    try:
        r=op.open(urllib.request.Request(url,headers={'User-Agent':'inventario'}),timeout=60); code=r.status; body=r.read().decode('utf-8','replace'); loc=''
    except urllib.error.HTTPError as e:
        code=e.code; loc=e.headers.get('Location',''); body=e.read().decode('utf-8','replace') if code>=400 else ''
    except Exception as e: return dict(row,url=url,code='ERR',location=str(e)[:80])
    return dict(row,url=url,code=code,location=loc.replace(host_to,'HOST'),
      title=g(r'<title[^>]*>(.*?)</title>',body),
      description=g(r'<meta[^>]+name="description"[^>]+content="([^"]*)"',body),
      canonical=g(r'<link[^>]+rel="canonical"[^>]+href="([^"]*)"',body).replace(host_to,'HOST'),
      robots=g(r'<meta[^>]+name="robots"[^>]+content="([^"]*)"',body),
      h1=g(r'<h1[^>]*>(.*?)</h1>',re.sub(r'<(?!/?h1)[^>]+>','',body))[:150],
      h1count=len(re.findall(r'<h1[\s>]',body,re.I)))
rows=[r for r in csv.DictReader(open(src)) if r['tipo'] in types]
cols=['tipo','id','url','code','location','title','description','canonical','robots','h1','h1count']
with cf.ThreadPoolExecutor(8) as ex, open(dst,'w',newline='') as f:
    w=csv.DictWriter(f,cols,extrasaction='ignore'); w.writeheader()
    for i,res in enumerate(ex.map(fetch,rows)):
        w.writerow(res)
        if i%300==0: print(i,file=sys.stderr,flush=True)
