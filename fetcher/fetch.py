import os, json, time, io, zipfile, urllib.parse as up, urllib.robotparser as rp
from datetime import datetime
import requests, psycopg2, bs4

DB = {
    'host': os.getenv('POSTGRES_HOST','db'),
    'port': os.getenv('POSTGRES_PORT',5432),
    'dbname': os.getenv('POSTGRES_DB','archive'),
    'user': os.getenv('POSTGRES_USER'),
    'password': os.getenv('POSTGRES_PASSWORD'),
}

# Retry connection logic
for _ in range(10):
    try:
        conn = psycopg2.connect(
            host=os.getenv("POSTGRES_HOST", "db"),
            port=os.getenv("POSTGRES_PORT", 5432),
            dbname=os.getenv("POSTGRES_DB", "archive"),
            user=os.getenv("POSTGRES_USER"),
            password=os.getenv("POSTGRES_PASSWORD"),
        )
        conn.autocommit = True
        cur = conn.cursor()
        break
    except psycopg2.OperationalError as e:
        print("Waiting for database to be ready...")
        time.sleep(2)
else:
    raise RuntimeError("Failed to connect to database after multiple attempts")

queue_file='/queue/jobs'
USER_AGENT='mini-archive-bot'

def allowed_by_robots(target_url):
    try:
        base=up.urljoin(target_url,'/')
        robots_url=up.urljoin(base,'robots.txt')
        rp_obj=rp.RobotFileParser(); rp_obj.set_url(robots_url); rp_obj.read()
        return rp_obj.can_fetch(USER_AGENT, target_url)
    except Exception:
        return True  # fail‑open

while True:
    if not os.path.exists(queue_file): time.sleep(2); continue
    with open(queue_file,'r+') as f:
        lines=f.readlines(); f.seek(0); f.truncate()
    if not lines: time.sleep(2); continue
    for line in lines:
        job=json.loads(line); url=job['url']; page_id=job['page_id']
        if not allowed_by_robots(url):
            cur.execute("INSERT INTO snapshots(page_id,status,message) VALUES (%s,'robots_blocked','Disallowed by robots.txt')",(page_id,))
            print('Blocked by robots.txt',url); continue
        try:
            r=requests.get(url,timeout=15,headers={'User-Agent':USER_AGENT}); r.raise_for_status()
            soup=bs4.BeautifulSoup(r.text,'html.parser')
            assets=[up.urljoin(url,t.get(a)) for t,a in [(t,a) for t,a in [('img','src'),('link','href'),('script','src')]] for t in soup.find_all(t)]
            buf=io.BytesIO(); zf=zipfile.ZipFile(buf,'w',zipfile.ZIP_DEFLATED)
            for a in assets:
                try:
                    ar=requests.get(a,timeout=10,headers={'User-Agent':USER_AGENT}); ar.raise_for_status()
                    zf.writestr(up.urlparse(a).path.lstrip('/'), ar.content)
                except Exception as e:
                    pass
            zf.close()
            cur.execute("INSERT INTO snapshots(page_id,html,assets_zip) VALUES(%s,%s,%s)",(page_id,r.text,buf.getvalue()))
            print('Stored snapshot of',url)
        except Exception as e:
            cur.execute("INSERT INTO snapshots(page_id,status,message) VALUES(%s,'error',%s)",(page_id,str(e)))