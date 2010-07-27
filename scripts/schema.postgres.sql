BEGIN;

---
--- users table
---
DROP TABLE IF EXISTS users CASCADE;
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    password CHAR(40) NOT NULL,
    pseudo VARCHAR(20) UNIQUE NOT NULL CHECK (pseudo ~ '^[a-zA-Z0-9_.]+$'),
    email VARCHAR(320) UNIQUE NOT NULL,
    lang VARCHAR(3),
    creation_time TIMESTAMP NOT NULL DEFAULT NOW(),
    creation_addr INET NOT NULL
);

---
--- pending_actions table
---
CREATE OR REPLACE FUNCTION randomchars(reslength INTEGER, letters TEXT = 'abcdefghijklmnopqrstuvwxyz0123456789')
    RETURNS TEXT AS
$$
DECLARE
    choices CHAR ARRAY;
    choices_length INTEGER;
    result TEXT := '';
BEGIN
    choices := regexp_split_to_array(letters, '');
    choices_length = array_length(choices, 1);

    WHILE length(result) < reslength LOOP
        result := result || choices[ceil(random() * choices_length)];
    END LOOP;

    return result;
END;
$$ LANGUAGE plpgsql;

DROP TYPE IF EXISTS ACTION CASCADE;
CREATE TYPE ACTION AS ENUM ('validate_creation', 'reset_password');

DROP TABLE IF EXISTS pending_actions CASCADE;
CREATE TABLE pending_actions (
    id SERIAL PRIMARY KEY,
    userid INTEGER NOT NULL REFERENCES users ON DELETE CASCADE,
    action ACTION NOT NULL,
    hash CHAR(40) NOT NULL UNIQUE CHECK (hash ~ '^[a-z0-9]+$') DEFAULT randomchars(40),
    notifications_number INTEGER DEFAULT 0,
    creation_time TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE (userid, action)
);

CREATE OR REPLACE FUNCTION tg_user_ai() RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO pending_actions (userid, action, creation_time) VALUES
                    (NEW.id, 'validate_creation', NEW.creation_time);
    RETURN NEW;
END;
$$ LANGUAGE PLPGSQL;

CREATE OR REPLACE FUNCTION tg_pending_bi() RETURNS TRIGGER AS $$
BEGIN
    IF NEW.action = 'reset_password' THEN
        DELETE FROM pending_actions WHERE action = 'reset_password' AND userid=NEW.userid;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE PLPGSQL;

DROP TRIGGER IF EXISTS tg_user_ai ON users;
CREATE TRIGGER tg_user_ai AFTER INSERT ON users FOR EACH ROW EXECUTE PROCEDURE tg_user_ai();

DROP TRIGGER IF EXISTS tg_pending_bi ON pending_actions;
CREATE TRIGGER tg_pending_bi BEFORE INSERT ON pending_actions FOR EACH ROW EXECUTE PROCEDURE tg_pending_bi();

---
--- paths table
---
DROP SEQUENCE IF EXISTS paths_id_seq CASCADE;
CREATE SEQUENCE paths_id_seq;

CREATE OR REPLACE FUNCTION seq_attained_value(seqname TEXT, idx INTEGER)
    RETURNS BOOLEAN AS
$$
DECLARE
    rec RECORD;
BEGIN
    EXECUTE 'SELECT * FROM ' || quote_ident (seqname) INTO rec;
    IF rec.is_called = FALSE
            OR idx < rec.min_value OR idx > rec.max_value
            OR (idx > rec.last_value AND rec.increment_by > 0)
            OR (idx < rec.last_value AND rec.increment_by < 0)
            THEN
        RETURN FALSE;
    END IF;
    return (((idx - rec.start_value) * abs(rec.increment_by) / rec.increment_by) % rec.increment_by) = 0;
END;
$$ LANGUAGE plpgsql;

DROP TABLE IF EXISTS paths CASCADE;
CREATE TABLE paths (
    id INTEGER PRIMARY KEY DEFAULT nextval('paths_id_seq'),
    geom GEOGRAPHY (LINESTRING, 4326) NOT NULL,
    owner INTEGER NOT NULL REFERENCES users ON DELETE CASCADE,
    title VARCHAR(40),
    urlcomp VARCHAR(20) UNIQUE CHECK (urlcomp ~ '^[a-z][a-zA-Z0-9_]*$') -- ~: matches regular expression; case sensitive
);

-- unique (geom, owner) constraint will not work: first because of postgis
-- #541; then because equality is checked by comparing bbox instead of real
-- geometries. So, we implement that constraint in a trigger
CREATE OR REPLACE FUNCTION tg_paths_bu() RETURNS TRIGGER AS $$
DECLARE res INTEGER;
BEGIN
    SELECT INTO res COUNT(*) FROM paths WHERE ST_AsBinary(geom) = ST_AsBinary(NEW.geom) AND owner = NEW.owner AND id != NEW.id;
    IF res >= 1 THEN
        RAISE 'duplicate key paths_geom_key' using ERRCODE = 'unique_violation';
    ELSE
        RETURN NEW;
    END IF;
END;
$$ LANGUAGE PLPGSQL;

DROP TRIGGER IF EXISTS tg_paths_bu ON paths;
CREATE TRIGGER tg_paths_bu BEFORE INSERT OR UPDATE ON paths FOR EACH ROW EXECUTE PROCEDURE tg_paths_bu();

COMMIT;
