# Arxivr
We implement a simple internet archiver. A user can interact with a form to either instruct the tool to take a snapshot of a given URL and all its assets, or request to rebuild a given site from one of its snapshots in the archive.

The tool is built using a combination of PHP, Python, and PostgreSQL. 

This is a major work-in-progress. 

## General Structure

**When the user submits a URL for archiving**:
1. A POST request is sent through `/archive.php` passing that URL.
2. This PHP script validates the string, retrieves any pre-existing page ID, inserts a row into the database `pages` with a new `page_id`, and appends a JSON job to `/shared/queue/jobs`, so the user will see that the request is pending.
3. In the backend, the Python fetcher notices the new line in the queue file. The raw HTML is requests for this URL, and the other static assets are discovered. 
4. Each asset is then fetched, written into an in-memory ZIP archive, which preserves the relative paths. Any failures here are logged. 
5. A row is added into the database `snapshots` including the `page_id` (a foreign key), `html` (raw page source), `assets_zip` (BYTEA of the ZIP file), `fetched_at`, and `status`. 
6. The PHP script `web/index.php` will join the latest snapshots to `pages`. 

**When the user requests to view a snapshot**
1. The PHP script `web/snapshot.php` looks up the `html` by `id`, and will stream the saved HTML back to the user. The browser should be able to download it from the live origin for fast replay.
2. We could instead unzip the assets and set up points to a local handler of these assets, using some `assets.php` to stream the requested file from storage to local... 

## Run Arxivr locally

```
cp .env.example .env
docker compose up --build
```
And visit `http://localhost:8080`. 