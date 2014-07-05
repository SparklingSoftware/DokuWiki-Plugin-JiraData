CREATE TABLE jiradata (key TEXT PRIMARY KEY, summary TEXT, description TEXT, timestamp INTEGER);
CREATE UNIQUE INDEX idx_jiradata ON jiradata(key);




