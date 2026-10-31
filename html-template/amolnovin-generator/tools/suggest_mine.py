"""Mine Google Suggest (fa/IR) for seed keywords:  python3 tools/suggest_mine.py "طراحی سایت" "پشتیبانی سایت" ...
Output: _assetsrc/keyword-data/suggest2_<ts>.json  (no volumes — Autocomplete only)"""
import requests, json, time, collections, sys, os
H = {"User-Agent":"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36"}
def sug(q):
    r = requests.get("https://suggestqueries.google.com/complete/search", params={"client":"firefox","hl":"fa","gl":"ir","q":q}, headers=H, timeout=15)
    try: return r.json()[1]
    except Exception: return []
def mine(seed, mods):
    score = collections.defaultdict(lambda:[0,99])
    for m in mods:
        for i,x in enumerate(sug(seed+m)):
            score[x][0]+=1; score[x][1]=min(score[x][1],i)
        time.sleep(0.12)
    ranked = sorted(score.items(), key=lambda kv:(-kv[1][0], kv[1][1]))
    return ranked
mods = ["", " ", " قیمت", " هزینه", " در", " با", " ۱۴۰۵", " چیست", " حرفه ای", " ارزان", " بهترین", " آموزش"]
seeds = sys.argv[1:]
out = {}
for s in seeds:
    r = mine(s, mods)
    out[s] = r
    print("##", s, "->", " | ".join(f"{k}[{v[0]}]" for k,v in r[:14]))
D = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), "_assetsrc", "keyword-data"); os.makedirs(D, exist_ok=True)
json.dump(out, open(os.path.join(D, "suggest2_%d.json" % int(time.time())), "w"), ensure_ascii=False)
