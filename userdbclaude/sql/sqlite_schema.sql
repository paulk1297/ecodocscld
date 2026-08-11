-- =====================================================================
--  ECOWASmail Admin — Schéma SQLite (TEST LOCAL UNIQUEMENT)
--  Schéma "final" déjà à jour (pwd_hash, codeUser ; ni pwd ni status).
--  La production reste sous MySQL (voir sql/01_init.sql).
-- =====================================================================

CREATE TABLE IF NOT EXISTS admins (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    username   TEXT NOT NULL UNIQUE,
    pwd_hash   TEXT NOT NULL,
    role       TEXT NOT NULL DEFAULT 'reader' CHECK (role IN ('admin', 'reader')),
    last_login TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS user (
    userid     INTEGER PRIMARY KEY AUTOINCREMENT,
    codeUser   TEXT UNIQUE,
    instidu    TEXT    DEFAULT '',
    nom        TEXT    DEFAULT '',
    poste      TEXT    DEFAULT '',
    uname      TEXT    DEFAULT '',
    pwd_hash   TEXT    DEFAULT '',
    otheremail TEXT    DEFAULT '',
    cit300     INTEGER DEFAULT 0,
    cit400     INTEGER DEFAULT 0,
    usb        INTEGER DEFAULT 0,
    wifi       INTEGER DEFAULT 0,
    intercom   TEXT    DEFAULT '',
    obs        TEXT    DEFAULT '',
    maj        INTEGER DEFAULT 0,
    dmaj       TEXT    DEFAULT '',
    created_at TEXT,
    position   TEXT    DEFAULT '',
    commission TEXT    DEFAULT '',
    dept       TEXT    DEFAULT '',
    sap        INTEGER DEFAULT 0
);

CREATE INDEX IF NOT EXISTS idx_user_nom   ON user (nom);
CREATE INDEX IF NOT EXISTS idx_user_uname ON user (uname);
