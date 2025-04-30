CREATE TABLE pages (
    id          SERIAL PRIMARY KEY,
    url         TEXT NOT NULL,
    created_at  TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE snapshots (
    id          SERIAL PRIMARY KEY,
    page_id     INTEGER REFERENCES pages(id) ON DELETE CASCADE,
    fetched_at  TIMESTAMPTZ DEFAULT now(),
    status      TEXT DEFAULT 'ok',
    html        TEXT,
    assets_zip  BYTEA            -- zip of css/js/img referenced (optional)
);