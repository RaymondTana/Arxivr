# Arxivr
We implement a simple internet archiver. This is a lightweight, self-hosted internet archiver inspired by the Internet Archive's Wayback Machine. The main actions permitted include **taking snapshots** of URLs with their linked assets, **browsing** a timeline of stored snapshots, and **revisiting** past versions of pages from the local archive.

The project is built using PHP (frontend and routing), Python (background worker), and PostgreSQL (data storage).

*Note*: this is a work-in-progress.

## General Architecture

1. A POST request is sent to `/archive.php` with the submitted URL.
2. The PHP script validates the URL, inserts it into the pages table (or fetches its existing `page_id`), and appends a new JSON job (`{"page_id": ..., "url": "..."}`) to the queue file at `/shared/queue/jobs`.
3. The fetcher, a long-running background process writte in Python, continuously monitors the queue. When it detects a new job:
    - It fetches the raw HTML of the URL using requests, and
    - It parses the HTML to discover linked static assets (`<img>`, `<script>`, `<link>`, etc.).
4. Each asset is downloaded and written into an in-memory ZIP archive, preserving relative file paths. We log any fetching failures.
5. A new row is inserted into the snapshots table containing:
   - `page_id` (foreign key to `pages`)
   - `html` (the full HTML source)
   - `assets_zip` (a `BYTEA` blob of the zipped assets)
   - `fetched_at` timestamp
   - `status` (e.g. `'ok'`, `'error'`, `'robots_blocked'`)
6. `/web/index.php` lists all known pages and their most recent snapshots. `/web/timeline.php` provides an interactive timeline of captures. Clicking any point navigates to `/web/snapshot.php?id=...` to view the full HTML snapshot.

## Run Arxivr locally

```
cp .env.example .env
docker compose up --build
```
And visit `http://localhost:8080`. 

To stop and remove the containers, run
```
docker compose down
```