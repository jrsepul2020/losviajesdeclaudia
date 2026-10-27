import csv,sys
a={(r['tipo'],r['id']):r for r in csv.DictReader(open(sys.argv[1]))}
b={(r['tipo'],r['id']):r for r in csv.DictReader(open(sys.argv[2]))}
fields=['code','location','title','description','canonical','robots','h1']
diffs=[];
for k,ra in a.items():
    rb=b.get(k)
    if not rb: diffs.append((k,'falta','','')); continue
    for f in fields:
        va=ra[f].replace('claudiaold.local','HOST').replace('claudia.local','HOST'); vb=rb[f].replace('claudiaold.local','HOST').replace('claudia.local','HOST')
        if va!=vb: diffs.append((k,f,va[:90],vb[:90]))
with open(sys.argv[3],'w',newline='') as f:
    w=csv.writer(f); w.writerow(['tipo','id','campo','antes','despues']); [w.writerow([k[0],k[1],c,x,y]) for k,c,x,y in diffs]
from collections import Counter
print('URLs comparadas:',len(a),'| diferencias:',len(diffs)); print(Counter(d[1] for d in diffs))
codes=Counter(r['code'] for r in a.values()); print('códigos antes:',codes); print('códigos después:',Counter(r['code'] for r in b.values()))
