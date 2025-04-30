import json, os, time, zipfile, io, re, urllib.parse as up
import psycopg2, requests, bs4
from datetime import datetime

DB = {
    "host": os.getenv("POSTGRES_HOST", "db"),
    "port": os.getenv("POSTGRES_PORT", 5432),
    "dbname": os.getenv("POSTGRES_DB", "archive"),
    "user": os.getenv("POSTGRES_USER"),
    "password": os.getenv("POSTGRES_PASSWORD"),
}

conn = psycopg2.connect(**DB)
conn.autocommit = True
cur = conn.cursor()
queue_file = "/queue/jobs"

print("Worker started – monitoring", queue_file)
while True:
    if not os.path.exists(queue_file):
        time.sleep(2); continue
    with open(queue_file, "r+") as f:
        lines = f.readlines()
        f.seek(0); f.truncate()
    if not lines:
        time.sleep(2); continue
    for line in lines:
        job = json.loads(line)
        url = job['url']; page_id = job['page_id']
        print("Fetching", url)
        try:
            resp = requests.get(url, timeout=15, headers={"User-Agent":"mini-archive-bot"})
            resp.raise_for_status()
            html = resp.text
            # collect static assets (very naive: css/img/js href/src)
            soup = bs4.BeautifulSoup(html, 'html.parser')
            assets = []
            for tag, attr in [('img','src'),('link','href'),('script','src')]:
                for t in soup.find_all(tag):
                    src = t.get(attr)
                    if src and not src.startswith('data:'):
                        assets.append(up.urljoin(url, src))
            buf = io.BytesIO()
            with zipfile.ZipFile(buf, 'w', zipfile.ZIP_DEFLATED) as zf:
                for a in assets:
                    try:
                        r = requests.get(a, timeout=10)
                        r.raise_for_status()
                        zf.writestr(up.urlparse(a).path.lstrip('/'), r.content)
                    except Exception as e:
                        print(' asset fail', a, e)
            cur.execute("INSERT INTO snapshots(page_id, fetched_at, html, assets_zip) VALUES (%s,%s,%s,%s)",
                (page_id, datetime.utcnow(), html, buf.getvalue()))
            print('Stored snapshot')
        except Exception as e:
            print('Error', e)